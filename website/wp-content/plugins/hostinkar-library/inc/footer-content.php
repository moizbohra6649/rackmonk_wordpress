<?php
/**
 * Footer content sync between Rackmonk Options → Footer Options and the live Elementor footer template.
 *
 * - Saving Footer Options writes the text, links, logo, contact details and social links into the live
 *   footer template (only content settings change, so the layout and styling stay exactly the same).
 * - Saving the live footer in Elementor copies its content back into Footer Options.
 * - The first time wp-admin loads, Footer Options are filled from the current live footer.
 *
 * Widgets are recognised by the footer layout (offer banner, logo, social icons, link lists, payment bar,
 * copyright, bottom links), not by fixed IDs, so the sync keeps working after the footer is edited.
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Footer_Content {
    const OPTION = 'xhosting_theme_options';
    const SEEDED = 'rackmonk_footer_content_seeded';
    const COLUMNS = 3;
    const CONTACT_KEYS = ['footer_contact_title', 'footer_address', 'footer_phone', 'footer_email'];
    /** Option suffix => [label, Font Awesome brand icon used for a newly added link]. */
    const NETWORKS = [
        'facebook'  => ['Facebook', 'fab fa-facebook-f'],
        'twitter'   => ['X (Twitter)', 'fab fa-x-twitter'],
        'linkedin'  => ['LinkedIn', 'fab fa-linkedin-in'],
        'instagram' => ['Instagram', 'fab fa-instagram'],
        'youtube'   => ['YouTube', 'fab fa-youtube'],
        'whatsapp'  => ['WhatsApp', 'fab fa-whatsapp'],
        'telegram'  => ['Telegram', 'fab fa-telegram-plane'],
        'pinterest' => ['Pinterest', 'fab fa-pinterest-p'],
    ];

    private static $syncing = false;

    public static function boot() {
        add_action('admin_init', [__CLASS__, 'seed']);
        add_action('csf_' . self::OPTION . '_saved', [__CLASS__, 'push']);
        add_action('elementor/document/after_save', [__CLASS__, 'pull']);
    }

    /* ------------------------------------------------------------------ sync */

    /** Footer template the theme really renders (Rackmonk Options, else the homepage's page setting), or 0. */
    public static function live_footer_id() {
        $options = get_option(self::OPTION);
        $style = is_array($options) && !empty($options['footer_style']) ? $options['footer_style'] : '';
        if (!$style) {
            $meta = get_post_meta((int) get_option('page_on_front'), 'xhosting_page_meta', true);
            if (is_array($meta) && !empty($meta['meta_footer_type'])) $style = $meta['meta_footer_style'] ?? '';
        }
        return ($style && get_post_type((int) $style) === 'xhosting_template') ? (int) $style : 0;
    }

    private static function document($id) {
        if (!$id || !class_exists('\Elementor\Plugin') || empty(\Elementor\Plugin::$instance->documents)) return null;
        return \Elementor\Plugin::$instance->documents->get($id) ?: null;
    }

    /** One time: fill Footer Options with what the live footer shows today. */
    public static function seed() {
        if (get_option(self::SEEDED) || !current_user_can('manage_options')) return;
        $document = self::document(self::live_footer_id());
        if (!$document) return;
        $values = self::extract((array) $document->get_elements_data());
        if (!$values) return;
        $options = get_option(self::OPTION);
        update_option(self::OPTION, array_merge(is_array($options) ? $options : [], $values));
        update_option(self::SEEDED, (int) $document->get_main_id(), false);
    }

    /** Footer Options saved → update the live footer template. */
    public static function push() {
        if (self::$syncing) return;
        $document = self::document(self::live_footer_id());
        $options = get_option(self::OPTION);
        if (!$document || !is_array($options)) return;
        $data = (array) $document->get_elements_data();
        if (!$data) return;
        $updated = self::apply($data, $options);
        if (wp_json_encode($updated) === wp_json_encode($data)) return;
        self::$syncing = true;
        $document->save(['elements' => $updated]);
        self::$syncing = false;
    }

    /** Live footer saved in Elementor → refresh Footer Options. */
    public static function pull($document) {
        if (self::$syncing || !is_object($document) || !method_exists($document, 'get_main_id')) return;
        if (method_exists($document, 'is_autosave') && $document->is_autosave()) return;
        $id = (int) $document->get_main_id();
        if (!$id || $id !== self::live_footer_id() || (int) $document->get_post()->ID !== $id) return;
        $values = self::extract((array) $document->get_elements_data());
        if (!$values) return;
        $options = get_option(self::OPTION);
        update_option(self::OPTION, array_merge(is_array($options) ? $options : [], $values));
    }

    /* --------------------------------------------------------- footer layout */

    /** Widgets in page order: [path into the elements tree, widget type, settings]. */
    private static function widgets(array $elements, array $path = []) {
        $out = [];
        foreach ($elements as $i => $element) {
            if (!is_array($element)) continue;
            $here = array_merge($path, [$i]);
            if (($element['elType'] ?? '') === 'widget') $out[] = [$here, (string) ($element['widgetType'] ?? ''), (array) ($element['settings'] ?? [])];
            if (!empty($element['elements']) && is_array($element['elements'])) {
                $out = array_merge($out, self::widgets($element['elements'], array_merge($here, ['elements'])));
            }
        }
        return $out;
    }

    /** Role => position in widgets(), worked out from the footer layout. */
    private static function roles(array $widgets) {
        $roles = [];
        $lists = 0;
        foreach ($widgets as $n => [, $type, $settings]) {
            $previous = $n > 0 ? $widgets[$n - 1][1] : '';
            switch ($type) {
                case 'xhostint-cta': $roles += ['cta' => $n]; break;
                case 'xhostint-payment': $roles += ['payment' => $n]; break;
                case 'xh-footer-link': $roles += ['legal' => $n]; break;
                case 'image': $roles += ['logo' => $n]; break;
                case 'social-icons':
                    if (isset($roles['social'])) break;
                    $roles['social'] = $n;
                    if ($previous === 'heading') $roles['social_title'] = $n - 1;
                    break;
                case 'icon-list':
                    if ($lists >= self::COLUMNS) break;
                    $lists++;
                    $roles['col' . $lists] = $n;
                    if ($previous === 'heading') $roles['col' . $lists . '_title'] = $n - 1;
                    break;
                case 'text-editor':
                    $html = (string) ($settings['editor'] ?? '');
                    if (!isset($roles['contact']) && preg_match('/\b(tel|mailto):/i', $html)) $roles['contact'] = $n;
                    elseif (!isset($roles['copyright']) && preg_match('/©|&copy;|rackmonk_year|rights reserved/iu', $html)) $roles['copyright'] = $n;
                    break;
            }
        }
        // About text: the heading right after the logo, unless it is already a section heading.
        if (isset($roles['logo'])) {
            $n = $roles['logo'] + 1;
            if (($widgets[$n][1] ?? '') === 'heading' && !in_array($n, $roles, true)) $roles['about'] = $n;
        }
        return $roles;
    }

    /** Path of the container that holds the element at $path (drops the trailing "elements", index). */
    private static function parent_path(array $path) {
        return array_slice($path, 0, -2);
    }

    private static function node_copy(array $data, array $path) {
        foreach ($path as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) return [];
            $data = $data[$key];
        }
        return is_array($data) ? $data : [];
    }

    /** desktop + every active Elementor breakpoint (tablet, mobile, ...). */
    private static function breakpoint_keys() {
        $keys = ['desktop', 'tablet', 'mobile'];
        if (class_exists('\Elementor\Plugin') && !empty(\Elementor\Plugin::$instance->breakpoints)) {
            $keys = array_merge(['desktop'], array_keys((array) \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints()));
        }
        return array_values(array_unique($keys));
    }

    private static function &node(array &$data, array $path) {
        $ref = &$data;
        foreach ($path as $key) $ref = &$ref[$key];
        return $ref;
    }

    /* ------------------------------------------------------ Elementor → options */

    public static function extract(array $data) {
        $widgets = self::widgets($data);
        $roles = self::roles($widgets);
        $get = static function ($role) use ($widgets, $roles) {
            return isset($roles[$role]) ? $widgets[$roles[$role]][2] : null;
        };
        $v = [];
        if (null !== ($s = $get('cta'))) {
            $v['footer_cta_title'] = (string) ($s['title'] ?? '');
            $v['footer_cta_btn_label'] = (string) ($s['btn_label'] ?? '');
            $v['footer_cta_btn_link'] = self::relative($s['btn_link']['url'] ?? '');
        }
        if (null !== ($s = $get('logo'))) $v['footer_logo'] = self::media_value((array) ($s['image'] ?? []));
        if (null !== ($s = $get('about'))) $v['footer_about'] = (string) ($s['title'] ?? '');
        if (null !== ($s = $get('social_title'))) $v['footer_social_title'] = (string) ($s['title'] ?? '');
        if (null !== ($s = $get('social'))) {
            foreach (self::NETWORKS as $key => $_) $v['footer_social_' . $key] = '';
            foreach ((array) ($s['social_icon_list'] ?? []) as $item) {
                $key = self::network_of($item['social_icon']['value'] ?? '');
                if ($key && $v['footer_social_' . $key] === '') $v['footer_social_' . $key] = (string) ($item['link']['url'] ?? '');
            }
        }
        if (null !== ($s = $get('contact'))) $v += self::parse_contact((string) ($s['editor'] ?? ''));
        for ($c = 1; $c <= self::COLUMNS; $c++) {
            if (isset($roles["col{$c}"])) {
                $column = self::node_copy($data, self::parent_path($widgets[$roles["col{$c}"]][0]));
                $v["footer_col{$c}_show"] = empty($column['settings']['hide_desktop']);
            }
            if (null !== ($s = $get("col{$c}_title"))) $v["footer_col{$c}_title"] = (string) ($s['title'] ?? '');
            if (null !== ($s = $get("col{$c}"))) {
                $v["footer_col{$c}_links"] = array_map(static function ($item) {
                    return ['label' => (string) ($item['text'] ?? ''), 'url' => self::relative($item['link']['url'] ?? '')];
                }, array_values((array) ($s['icon_list'] ?? [])));
            }
        }
        if (null !== ($s = $get('payment'))) {
            $v['footer_payment_title'] = (string) ($s['title'] ?? '');
            $v['footer_payment_image'] = self::media_value((array) ($s['payment'] ?? []));
            $v['footer_chat_label'] = (string) ($s['btn_label'] ?? '');
            $v['footer_chat_link'] = self::relative($s['btn_link']['url'] ?? '');
            $v['footer_languages'] = implode("\n", array_filter(array_map(static function ($item) {
                return trim((string) ($item['title'] ?? ''));
            }, (array) ($s['languages'] ?? [])), 'strlen'));
        }
        if (null !== ($s = $get('copyright'))) $v['footer_copyright'] = (string) ($s['editor'] ?? '');
        if (null !== ($s = $get('legal'))) {
            $v['footer_legal_links'] = array_map(static function ($item) {
                return ['label' => (string) ($item['title'] ?? ''), 'url' => self::relative($item['link']['url'] ?? '')];
            }, array_values((array) ($s['links'] ?? [])));
        }
        return $v;
    }

    private static function parse_contact($html) {
        $v = [];
        if (preg_match('/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', $html, $m)) $v['footer_contact_title'] = trim(html_entity_decode(wp_strip_all_tags($m[1]), ENT_QUOTES, 'UTF-8'));
        $address = '';
        $phones = [];
        $emails = [];
        if (preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER)) {
            foreach ($links as $a) {
                $href = html_entity_decode($a[2], ENT_QUOTES, 'UTF-8');
                $text = trim(html_entity_decode(wp_strip_all_tags(preg_replace('/<br\s*\/?>/i', "\n", $a[3])), ENT_QUOTES, 'UTF-8'));
                if (stripos($href, 'tel:') === 0) $phones[] = $text;
                elseif (stripos($href, 'mailto:') === 0) $emails[] = $text;
                elseif ($address === '') $address = $text;
            }
        }
        if ($address === '' && preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $paragraphs)) {
            foreach ($paragraphs[1] as $p) {
                if (preg_match('/\b(tel|mailto):/i', $p)) continue;
                $address = trim(html_entity_decode(wp_strip_all_tags(preg_replace('/<br\s*\/?>/i', "\n", $p)), ENT_QUOTES, 'UTF-8'));
                if ($address !== '') break;
            }
        }
        $v['footer_address'] = $address;
        $v['footer_phone'] = implode(', ', $phones);
        $v['footer_email'] = implode(', ', $emails);
        return $v;
    }

    /* ------------------------------------------------------ options → Elementor */

    public static function apply(array $data, array $o) {
        $widgets = self::widgets($data);
        $roles = self::roles($widgets);
        $has = static function ($key) use ($o) { return array_key_exists($key, $o); };
        $text = static function ($key) use ($o) { return is_scalar($o[$key] ?? '') ? (string) ($o[$key] ?? '') : ''; };
        $edit = static function ($role, callable $change) use (&$data, $widgets, $roles) {
            if (!isset($roles[$role])) return;
            $node = &self::node($data, $widgets[$roles[$role]][0]);
            $node['settings'] = $change(is_array($node['settings'] ?? null) ? $node['settings'] : []);
        };

        $edit('cta', static function ($s) use ($has, $text) {
            if ($has('footer_cta_title')) $s['title'] = $text('footer_cta_title');
            if ($has('footer_cta_btn_label')) $s['btn_label'] = $text('footer_cta_btn_label');
            if ($has('footer_cta_btn_link')) $s['btn_link'] = self::link((array) ($s['btn_link'] ?? []), $text('footer_cta_btn_link'));
            return $s;
        });
        if ($has('footer_logo')) $edit('logo', static function ($s) use ($o) {
            $s['image'] = self::image_setting((array) ($s['image'] ?? []), $o['footer_logo']);
            return $s;
        });
        foreach (['about' => 'footer_about', 'social_title' => 'footer_social_title'] as $role => $key) {
            if ($has($key)) $edit($role, static function ($s) use ($key, $text) {
                $s['title'] = $text($key);
                return $s;
            });
        }
        $edit('social', static function ($s) use ($has, $text) {
            $keys = array_filter(array_keys(self::NETWORKS), static function ($key) use ($has) { return $has('footer_social_' . $key); });
            if (!$keys) return $s;
            $list = [];
            $seen = [];
            foreach ((array) ($s['social_icon_list'] ?? []) as $item) {
                $key = self::network_of($item['social_icon']['value'] ?? '');
                if (!$key || !in_array($key, $keys, true)) { $list[] = $item; continue; } // Icons not managed here stay.
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $url = trim($text('footer_social_' . $key));
                if ($url === '') continue;
                $item['link'] = self::link((array) ($item['link'] ?? []), $url);
                $list[] = $item;
            }
            foreach ($keys as $key) {
                $url = trim($text('footer_social_' . $key));
                if (isset($seen[$key]) || $url === '') continue;
                $list[] = [
                    'social_icon' => ['value' => self::NETWORKS[$key][1], 'library' => 'fa-brands'],
                    '_id'         => substr(md5('rackmonk-social-' . $key), 0, 7),
                    'link'        => self::link(['is_external' => 'on', 'nofollow' => '', 'custom_attributes' => ''], $url),
                ];
            }
            $s['social_icon_list'] = $list;
            return $s;
        });
        $edit('contact', static function ($s) use ($o) {
            $given = array_intersect_key($o, array_flip(self::CONTACT_KEYS));
            if (!$given) return $s;
            $contact = array_merge(self::parse_contact((string) ($s['editor'] ?? '')), $given);
            $s['editor'] = self::contact_html($contact);
            return $s;
        });
        for ($c = 1; $c <= self::COLUMNS; $c++) {
            // "Show this column" → Elementor's own Hide on desktop/tablet/mobile flags on the column container.
            if ($has("footer_col{$c}_show") && isset($roles["col{$c}"])) {
                $column = &self::node($data, self::parent_path($widgets[$roles["col{$c}"]][0]));
                if (is_array($column) && ($column['elType'] ?? '') === 'container') {
                    $settings = is_array($column['settings'] ?? null) ? $column['settings'] : [];
                    $show = !empty($o["footer_col{$c}_show"]);
                    if ($show && !empty($settings['hide_desktop'])) {
                        foreach (self::breakpoint_keys() as $key) unset($settings['hide_' . $key]);
                    } elseif (!$show && empty($settings['hide_desktop'])) {
                        foreach (self::breakpoint_keys() as $key) $settings['hide_' . $key] = 'hidden-' . $key;
                    }
                    $column['settings'] = $settings;
                }
                unset($column);
            }
            if ($has("footer_col{$c}_title")) $edit("col{$c}_title", static function ($s) use ($c, $text) {
                $s['title'] = $text("footer_col{$c}_title");
                return $s;
            });
            if ($has("footer_col{$c}_links")) $edit("col{$c}", static function ($s) use ($c, $o) {
                $old = array_values((array) ($s['icon_list'] ?? []));
                $list = [];
                foreach (self::rows($o["footer_col{$c}_links"]) as $i => $row) {
                    $item = $old[$i] ?? ['selected_icon' => ['value' => '', 'library' => ''], '_id' => substr(md5("rackmonk-col{$c}-{$i}"), 0, 7)];
                    $item['text'] = $row['label'];
                    $item['link'] = self::link((array) ($item['link'] ?? []), $row['url']);
                    $list[] = $item;
                }
                $s['icon_list'] = $list;
                return $s;
            });
        }
        $edit('payment', static function ($s) use ($o, $has, $text) {
            if ($has('footer_payment_title')) $s['title'] = $text('footer_payment_title');
            if ($has('footer_payment_image')) $s['payment'] = self::image_setting((array) ($s['payment'] ?? []), $o['footer_payment_image']);
            if ($has('footer_chat_label')) $s['btn_label'] = $text('footer_chat_label');
            if ($has('footer_chat_link')) $s['btn_link'] = self::link((array) ($s['btn_link'] ?? []), $text('footer_chat_link'));
            if ($has('footer_languages')) {
                $old = array_values((array) ($s['languages'] ?? []));
                $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text('footer_languages'))), 'strlen'));
                $s['languages'] = array_map(static function ($title, $i) use ($old) {
                    $item = is_array($old[$i] ?? null) ? $old[$i] : ['_id' => substr(md5('rackmonk-language-' . $i), 0, 7)];
                    $item['title'] = $title;
                    return $item;
                }, $lines, array_keys($lines));
            }
            return $s;
        });
        if ($has('footer_copyright')) $edit('copyright', static function ($s) use ($text) {
            $s['editor'] = $text('footer_copyright');
            return $s;
        });
        if ($has('footer_legal_links')) $edit('legal', static function ($s) use ($o) {
            $old = array_values((array) ($s['links'] ?? []));
            $list = [];
            foreach (self::rows($o['footer_legal_links']) as $i => $row) {
                $item = $old[$i] ?? ['_id' => substr(md5('rackmonk-legal-' . $i), 0, 7)];
                $item['title'] = $row['label'];
                $item['link'] = self::link((array) ($item['link'] ?? []), $row['url']);
                $list[] = $item;
            }
            $s['links'] = $list;
            return $s;
        });
        return $data;
    }

    private static function contact_html(array $c) {
        $html = '';
        $title = trim((string) ($c['footer_contact_title'] ?? ''));
        $address = trim((string) ($c['footer_address'] ?? ''));
        if ($title !== '') $html .= '<h4>' . esc_html($title) . '</h4>';
        if ($address !== '') {
            $map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(preg_replace('/\s+/', ' ', $address));
            $html .= '<p><a href="' . esc_url($map) . '" target="_blank" rel="noopener">' . nl2br(esc_html($address), false) . '</a></p>';
        }
        $lines = [];
        foreach (self::split($c['footer_phone'] ?? '') as $phone) {
            $tel = (strpos($phone, '+') === 0 ? '+' : '') . preg_replace('/\D/', '', $phone);
            $lines[] = '<a href="tel:' . esc_attr($tel) . '">' . esc_html($phone) . '</a>';
        }
        foreach (self::split($c['footer_email'] ?? '') as $email) {
            $lines[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($lines) $html .= '<p>' . implode('<br>', $lines) . '</p>';
        return $html;
    }

    /* ---------------------------------------------------------------- helpers */

    private static function split($value) {
        return array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', (string) $value)), 'strlen'));
    }

    /** Group field rows with a label (empty rows are skipped). */
    private static function rows($rows) {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $label = is_array($row) ? trim((string) ($row['label'] ?? '')) : '';
            if ($label !== '') $out[] = ['label' => $label, 'url' => trim((string) ($row['url'] ?? ''))];
        }
        return $out;
    }

    private static function network_of($icon) {
        $icon = strtolower((string) $icon);
        if ($icon === '') return '';
        if (strpos($icon, 'twitter') !== false || preg_match('/\bfa-x\b/', $icon)) return 'twitter';
        foreach (array_keys(self::NETWORKS) as $key) if (strpos($icon, $key) !== false) return $key;
        return '';
    }

    /** Keeps the link's other settings (new tab, nofollow); "/page/" becomes a full site address. */
    private static function link(array $link, $url) {
        $link += ['is_external' => '', 'nofollow' => '', 'custom_attributes' => ''];
        $link['url'] = self::absolute($url);
        return $link;
    }

    private static function absolute($url) {
        $url = trim((string) $url);
        if ($url !== '' && $url[0] === '/' && strpos($url, '//') !== 0) return home_url($url);
        return $url;
    }

    private static function relative($url) {
        $url = trim((string) $url);
        $home = untrailingslashit(home_url());
        if ($url !== '' && stripos($url, $home . '/') === 0) return substr($url, strlen($home));
        if ($url !== '' && strcasecmp($url, $home) === 0) return '/';
        return $url;
    }

    /** Elementor image setting → Codestar media field value. */
    private static function media_value(array $image) {
        $url = (string) ($image['url'] ?? '');
        return ['url' => $url, 'id' => $image['id'] ?? '', 'width' => '', 'height' => '', 'thumbnail' => $url, 'alt' => (string) ($image['alt'] ?? ''), 'title' => '', 'description' => ''];
    }

    /** Codestar media field value → Elementor image setting (keeps the old alt text when the new one has none). */
    private static function image_setting(array $image, $media) {
        $media = is_array($media) ? $media : [];
        $url = trim((string) ($media['url'] ?? ''));
        $id = absint($media['id'] ?? 0);
        $alt = trim((string) ($media['alt'] ?? ''));
        $image['url'] = $url;
        $image['id'] = $id ?: '';
        $image['alt'] = $alt !== '' ? $alt : (string) ($image['alt'] ?? '');
        $image['source'] = $image['source'] ?? 'library';
        return $image;
    }
}

Rackmonk_Footer_Content::boot();
