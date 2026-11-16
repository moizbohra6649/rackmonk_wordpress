<?php
/** Catalog-backed service cards for the existing Hostinkar carousel. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Hosting_Cards {
    const OPTION = 'rackmonk_hosting_cards';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_rackmonk_hosting_cards', [$this, 'save']);
        add_action('elementor/element/xhosting-service/__service__style__/before_section_end', [$this, 'controls']);
        add_filter('elementor/widget/render_content', [$this, 'render'], 20, 2);
    }

    public function controls($widget) {
        $widget->add_control('rackmonk_card_source', [
            'label' => 'Hosting cards source',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'catalog',
            'options' => ['catalog' => 'WHMCS Plans / Hosting Cards', 'manual' => 'Manual Elementor items'],
            'condition' => ['style' => '1'],
            'description' => 'Manage catalog cards in WordPress > WHMCS Plans > Hosting Cards. The section heading remains editable in Elementor.',
        ]);
    }

    /** Query real groups only: never expose the pricing manager's demo fallback. */
    public function categories() {
        $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
        if (!$pdo) return [];
        try {
            return $pdo->query("SELECT g.id, g.name, g.tagline, g.headline, COUNT(p.id) AS plan_count,
                MAX(p.is_featured) AS featured
                FROM tblproductgroups g JOIN tblproducts p ON p.gid = g.id
                WHERE (g.hidden IS NULL OR g.hidden IN ('', '0'))
                  AND (p.hidden IS NULL OR p.hidden IN ('', '0'))
                  AND (p.retired IS NULL OR p.retired IN ('', '0'))
                  AND p.paytype != 'onetime'
                GROUP BY g.id, g.name, g.tagline, g.headline, g.`order`
                ORDER BY g.`order`, g.id")->fetchAll();
        } catch (Throwable $error) {
            error_log('Rackmonk hosting cards: ' . $error->getMessage());
            return [];
        }
    }

    public function website_url($url) {
        $home = wp_parse_url(home_url('/'));
        $target = wp_parse_url($url);
        if (!$target || !in_array($target['scheme'] ?? '', ['http', 'https'], true)
            || strcasecmp($target['host'] ?? '', $home['host'] ?? '') !== 0
            || ($target['port'] ?? null) !== ($home['port'] ?? null)) return false;
        $base = trailingslashit($home['path'] ?? '/');
        $path = rawurldecode($target['path'] ?? '/');
        return strpos($path, $base) === 0 && !preg_match('~(?:^|/)(?:whmcs|\.\.?)(?:/|$)~i', $path);
    }

    public function category_defaults($category) {
        $name = $category['name'];
        $copy = [
            'shared-hosting' => 'Start your website without stretching your budget. Explore shared hosting for personal sites, portfolios and small businesses, and choose a plan that fits your next step.',
            'cloud-hosting' => 'Give your growing website room to move forward. Explore cloud hosting plans for business websites, online stores and projects with evolving hosting needs.',
            'vps-hosting' => 'Take greater control of your hosting environment. Explore VPS plans for custom applications, development projects and websites ready to move beyond shared hosting.',
            'wordpress-hosting' => 'Find the right home for your WordPress website. Explore hosting plans for blogs, business sites and content-rich projects, so you can focus on creating and growing your online presence.',
        ];
        $key = sanitize_title($name);
        $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'title' => $name, 'numberposts' => 1]);
        $page = $pages ? $pages[0] : null;
        $slugs = [$key];
        if ($key === 'wordpress-hosting') $slugs[] = 'wp-hosting';
        if ($key === 'shared-hosting') $slugs[] = 'web-hosting';
        foreach ($slugs as $slug) {
            if ($page) break;
            $candidate = get_page_by_path($slug);
            if ($candidate && $candidate->post_status === 'publish') $page = $candidate;
        }
        $pricing = get_page_by_path('pricing');
        $url = $page ? get_permalink($page) : ($pricing && $pricing->post_status === 'publish' ? get_permalink($pricing) : home_url('/'));
        return [
            'url' => $url,
            'description' => $copy[$key] ?? (trim(wp_strip_all_tags($category['tagline'] ?: $category['headline'])) ?: sprintf('Explore our %s plans and compare the options to find the right hosting for your website, project or business.', $name)),
        ];
    }

    public function cards() {
        $saved = get_option(self::OPTION, []);
        $cards = [];
        $colors = ['#eaf7ef', '#f5f7f9', '#eaf7ef', '#f5f7f9'];
        foreach ($this->categories() as $index => $category) {
            $id = (int) $category['id'];
            $custom = $saved[$id] ?? [];
            if (!empty($custom['hidden'])) continue;
            $category_defaults = $this->category_defaults($category);
            $defaults = [
                'id' => $id, 'title' => $category['name'],
                'description' => $category_defaults['description'],
                'button' => 'See More',
                'url' => $category_defaults['url'],
                'image' => '', 'background' => $colors[$index % count($colors)],
                'border' => '#d9dfe5', 'badge' => !empty($category['featured']) ? 'Popular' : '',
                'order' => $index * 10,
            ];
            foreach ($defaults as $key => $value) {
                if ($key === 'url' && !empty($custom[$key]) && !$this->website_url($custom[$key])) continue;
                if (isset($custom[$key]) && $custom[$key] !== '') $defaults[$key] = $custom[$key];
            }
            // A blank saved badge deliberately removes the automatic featured badge.
            if (array_key_exists('badge', $custom)) $defaults['badge'] = $custom['badge'];
            $cards[] = $defaults;
        }
        usort($cards, function ($a, $b) { return ($a['order'] <=> $b['order']) ?: ($a['id'] <=> $b['id']); });
        return $cards;
    }

    public function render($content, $widget) {
        if ($widget->get_name() !== 'xhosting-service') return $content;
        $settings = $widget->get_settings_for_display();
        if ((string) ($settings['style'] ?? '1') !== '1' || ($settings['rackmonk_card_source'] ?? 'catalog') === 'manual') return $content;
        $cards = $this->cards();
        if (!$cards) {
            return current_user_can('manage_options') ? '<p>No active hosting categories found. Check WHMCS Plans and your WHMCS connection.</p>' : '';
        }
        wp_enqueue_script('rackmonk-hosting-cards', RACKMONK_WHMCS_URL . 'assets/js/hosting-cards.js', ['jquery', 'swiper-bundle', 'elementor-frontend'], filemtime(RACKMONK_WHMCS_DIR . 'assets/js/hosting-cards.js'), true);
        ob_start();
        ?>
        <div class="hosting__item-wrap rackmonk-hosting-cards">
            <div class="swiper-container rackmonk-hosting-active fix"><div class="swiper-wrapper" style="align-items:stretch">
            <?php foreach ($cards as $card): ?>
                <div class="swiper-slide" style="height:auto">
                    <div class="hosting__item" style="height:100%;background:<?php echo esc_attr($card['background']); ?>;border:1px solid <?php echo esc_attr($card['border']); ?>;display:flex;flex-direction:column">
                        <div class="hosting__icon">
                        <?php if ($card['image']): ?>
                            <img src="<?php echo esc_url($card['image']); ?>" alt="" style="width:66px;height:66px;object-fit:contain" loading="lazy">
                        <?php else: ?>
                            <svg width="66" height="66" viewBox="0 0 66 66" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="7" y="7" width="52" height="15" rx="4"/><rect x="7" y="26" width="52" height="15" rx="4"/><rect x="7" y="45" width="52" height="15" rx="4"/><path d="M32 14h19M32 33h19M32 52h19"/><circle cx="16" cy="14.5" r="2"/><circle cx="16" cy="33.5" r="2"/><circle cx="16" cy="52.5" r="2"/></svg>
                        <?php endif; ?>
                        </div>
                        <div class="hosting__content" style="flex:1;display:flex;flex-direction:column">
                            <h3 class="title"><a href="<?php echo esc_url($card['url']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
                            <p style="flex:1"><?php echo esc_html($card['description']); ?></p>
                            <div class="hosting__btn"><a href="<?php echo esc_url($card['url']); ?>" class="tg-btn tg-btn-three"><?php echo esc_html($card['button']); ?><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
                            <?php if ($card['badge']): ?><span class="hosting__tag"><?php echo esc_html($card['badge']); ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function menu() {
        add_submenu_page('rackmonk-whmcs-plans', 'Hosting Cards', 'Hosting Cards', 'manage_options', 'rackmonk-hosting-cards', [$this, 'page']);
    }

    public function save() {
        if (!current_user_can('manage_options')) wp_die('You cannot manage hosting cards.', '', ['response' => 403]);
        check_admin_referer('rackmonk_hosting_cards');
        if (($_POST['cards_complete'] ?? '') !== '1' || !is_array($_POST['cards'] ?? null)) wp_die('Incomplete form. Nothing was saved.');
        $saved = get_option(self::OPTION, []);
        $input = wp_unslash($_POST['cards']);
        foreach ($this->categories() as $category) {
            $id = (int) $category['id'];
            if (!isset($input[$id]) || !is_array($input[$id])) continue;
            $row = $input[$id];
            foreach ($row as $value) if (!is_scalar($value)) wp_die('Invalid card value. Nothing was saved.');
            $card = ['hidden' => empty($row['visible']) ? 1 : 0, 'order' => (int) ($row['order'] ?? 0)];
            foreach (['title', 'button', 'badge'] as $key) $card[$key] = sanitize_text_field($row[$key] ?? '');
            $card['description'] = sanitize_textarea_field($row['description'] ?? '');
            foreach (['url', 'image'] as $key) $card[$key] = esc_url_raw($row[$key] ?? '', ['http', 'https']);
            if ($card['url'] !== '' && !$this->website_url($card['url'])) wp_die('Destination must be a page URL on this website, not WHMCS or an external site. Nothing was saved.');
            foreach (['background', 'border'] as $key) $card[$key] = sanitize_hex_color($row[$key] ?? '') ?: '';
            $saved[$id] = $card;
        }
        update_option(self::OPTION, $saved, false);
        // Elementor may otherwise continue serving the old, manually rendered cards.
        if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
        wp_safe_redirect(admin_url('admin.php?page=rackmonk-hosting-cards&saved=1'));
        exit;
    }

    public function page() {
        if (!current_user_can('manage_options')) return;
        $categories = $this->categories();
        $saved = get_option(self::OPTION, []);
        ?>
        <div class="wrap"><h1>Hosting Cards</h1>
        <p>The homepage hosting carousel automatically shows one card per category containing active recurring plans. New categories appear automatically; hidden, retired and internal one-time packages are excluded.</p>
        <p>Blank title, description, button, link or colors use catalog defaults. A blank badge hides the badge. Edit the section heading in Elementor. To keep a different carousel manual, select <strong>Manual Elementor items</strong> in its Service Style settings.</p>
        <p>Default links open matching published website pages, never WHMCS. Custom destinations must also stay on this website. New categories without a matching page open the website Pricing page until you set a destination.</p>
        <?php if (isset($_GET['saved'])): ?><div class="notice notice-success"><p>Hosting cards saved.</p></div><?php endif; ?>
        <?php if (!$categories): ?><div class="notice notice-warning"><p>No active categories found. Check your WHMCS connection and available plans.</p></div><?php else: ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="rackmonk_hosting_cards">
            <?php wp_nonce_field('rackmonk_hosting_cards'); ?>
            <?php foreach ($categories as $index => $category): $id = (int) $category['id']; $card = $saved[$id] ?? []; ?>
            <div class="postbox" style="padding:20px;max-width:950px"><h2><?php echo esc_html($category['name']); ?> <small>(<?php echo (int) $category['plan_count']; ?> plans)</small></h2>
                <label><input type="checkbox" name="cards[<?php echo $id; ?>][visible]" value="1" <?php checked(empty($card['hidden'])); ?>> Show this category</label>
                <table class="form-table" role="presentation">
                <?php foreach (['title' => 'Title', 'description' => 'Description', 'button' => 'Button label', 'url' => 'Destination URL', 'image' => 'Icon image URL', 'background' => 'Background color (#hex)', 'border' => 'Border color (#hex)', 'badge' => 'Badge text', 'order' => 'Display order (lowest first)'] as $key => $label):
                    $value = $card[$key] ?? ($key === 'order' ? $index * 10 : ($key === 'badge' && !empty($category['featured']) ? 'Popular' : ''));
                    $field = 'cards[' . $id . '][' . $key . ']'; ?>
                    <tr><th><label for="rm-card-<?php echo esc_attr($id . '-' . $key); ?>"><?php echo esc_html($label); ?></label></th><td>
                    <?php if ($key === 'description'): ?>
                        <textarea class="large-text" rows="3" id="rm-card-<?php echo esc_attr($id . '-' . $key); ?>" name="<?php echo esc_attr($field); ?>"><?php echo esc_textarea($value); ?></textarea>
                    <?php else: ?>
                        <input class="regular-text" type="<?php echo in_array($key, ['url', 'image'], true) ? 'url' : ($key === 'order' ? 'number' : 'text'); ?>" id="rm-card-<?php echo esc_attr($id . '-' . $key); ?>" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($key === 'title' ? $category['name'] : ($key === 'button' ? 'See More' : '')); ?>">
                    <?php endif; ?>
                    </td></tr>
                <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>
            <input type="hidden" name="cards_complete" value="1">
            <?php submit_button('Save Hosting Cards'); ?>
        </form><?php endif; ?></div>
        <?php
    }
}
