<?php
/** WordPress resource catalog and configurator. Checkout uses WooCommerce, not WHMCS. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/class-native-cart-pricing.php';

class Rackmonk_Website_Cart {
    const OPTION = 'rackmonk_website_cart_plans';
    public static function content_defaults() {
        return [
            'cart_plan_label' => 'Hosting plan',
            'cart_duration_label' => 'Billing duration',
            'cart_plan_placeholder' => 'Choose an available hosting plan',
            'cart_retry_cta' => 'Retry pricing',
            'cart_cancel_cta' => 'Cancel editing',
            'cart_return_cta' => 'Cancel and return to cart',
            'cart_refresh_cta' => 'Refresh prices',
            'cart_clear_cta' => 'Clear cart',
            'cart_edit_cta' => 'Edit plan',
            'cart_remove_cta' => 'Remove',
            'cart_base_term_label' => 'Base plan / term',
            'cart_resources_term_label' => 'Resources / term',
            'cart_setup_label' => 'Setup fees',
            'cart_base_month_label' => 'Base plan / month',
            'cart_resources_month_label' => 'Resources / month',
            'cart_monthly_label' => 'Monthly price',
            'cart_estimate_label' => 'Estimated monthly price',
            'cart_eyebrow' => 'HOSTING PLAN CONFIGURATOR',
            'cart_title' => 'Configure Your Hosting Plan',
            'cart_edit_title' => 'Customize your cart plan',
            'cart_intro' => 'Choose your ideal specifications, operating system, and security add-ons with live transparent pricing.',
            'cart_trust_activation' => 'Activation after order review',
            'cart_trust_resources' => 'Choose your resources',
            'cart_trust_pricing' => 'Review live plan pricing',
            'cart_trust_support' => 'Contact us for plan support',
            'cart_configure_tab' => '01 Configure',
            'cart_cart_tab' => 'Your cart',
            'cart_unavailable' => 'No hosting plans are available right now. Your saved cart can still be reviewed.',
            'cart_configure_heading' => 'Make it yours',
            'cart_resources_heading' => 'Choose your resources',
            'cart_summary_eyebrow' => 'YOUR CONFIGURATION',
            'cart_add_cta' => 'Add to your cart',
            'cart_save_cta' => 'Save cart changes',
            'cart_update_cta' => 'Update cart configuration',
            'cart_pricing_note_native' => 'Prices exclude taxes. Configurable terms and setup fees come from WHMCS.',
            'cart_pricing_note' => 'Prices exclude taxes. No discounts applied. Resource prices are added to the base monthly price.',
            'cart_checkout_badge' => 'Review your order before payment',
            'cart_activation_badge' => 'Server activation after order review',
            'cart_terms_badge' => 'Terms depend on your selected plan',
            'cart_lineup_heading' => 'Your hosting lineup',
            'cart_empty_title' => 'A fresh start for your next project',
            'cart_empty_intro' => 'Configure a hosting plan and add it here. Your choices stay in this browser.',
            'cart_empty_cta' => 'Configure a plan',
            'cart_totals_note' => 'Includes all selected terms and resources. Totals exclude taxes. Taxes and available payment methods are shown at checkout.',
            'cart_checkout_cta' => 'Proceed to checkout',
            'cart_noscript' => 'Enable JavaScript to configure your plan and use the website cart.',
        ];
    }

    const RESOURCES = [
        'os' => 'Operating System', 'ram' => 'Memory', 'storage' => 'Primary Disk',
        'secondary_disk' => 'Secondary Disk', 'backup_disk' => 'Backup Disk (Recommended)',
        'raid' => 'RAID Configuration', 'control_panel' => 'Control Panel', 'antivirus' => 'Premium Antivirus',
        'firewall' => 'Firewall', 'ip_addresses' => 'IP Addresses', 'port_speed' => 'Port Speed', 'data_transfer' => 'Data Transfer',
        'cpu' => 'CPU', 'websites' => 'Websites',
    ];

    public function __construct() {
        add_shortcode('rackmonk_cart', [$this, 'render']);
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('query_vars', function($vars) { $vars[] = 'rackmonk_cart'; return $vars; });
        add_action('template_redirect', [$this, 'virtual_page']);
        add_filter('body_class', function ($classes) {
            global $post;
            if (get_query_var('rackmonk_cart') || ($post && has_shortcode($post->post_content, 'rackmonk_cart'))) $classes[] = 'rm-hosting-configurator';
            return $classes;
        });
    }

    public static function get_config($pid) {
        $all = get_option(self::OPTION, []);
        return is_array($all) && isset($all[$pid]) && is_array($all[$pid]) ? $all[$pid] : null;
    }

    public static function runtime_config($pid) {
        $settings = self::get_config($pid);
        if (!$settings || empty($settings['enabled'])) return null;
        if (!class_exists('Rackmonk_Plans_Manager')) throw new RuntimeException('WHMCS pricing is unavailable.');
        return Rackmonk_Native_Cart_Pricing::config(Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), $pid, $settings);
    }

    public static function revision($config) {
        unset($config['revision']);
        return hash('sha256', serialize($config));
    }

    public static function enabled($pid) {
        $config = self::get_config($pid);
        return $config && !empty($config['enabled']);
    }

    public static function money($value) {
        if (!is_scalar($value) || !preg_match('/^\d{1,7}(\.\d{1,2})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Enter a price from 0 to 9999999.99, with up to two decimals.');
        }
        return number_format((float) $value, 2, '.', '');
    }

    /** Explicitly opted-in INR examples, never inferred from production WHMCS pricing. */
    public static function demo_resources($plan) {
        $server = preg_match('/\b(VPS|Cloud)\b/i', $plan['name'] ?? '') === 1;
        $examples = [
            'os' => [['Ubuntu 24.04 LTS', 0], ['Debian 12', 0], ['AlmaLinux 9', 0], ['Windows Server 2022', 1499]],
            'ram' => $server ? [['4 GB', 0], ['8 GB', 499], ['16 GB', 1299], ['32 GB', 2999]] : [['1 GB', 0], ['2 GB', 149], ['4 GB', 399], ['8 GB', 899]],
            'storage' => $server ? [['50 GB NVMe SSD', 0], ['100 GB NVMe SSD', 299], ['250 GB NVMe SSD', 699], ['500 GB NVMe SSD', 1299]] : [['10 GB NVMe SSD', 0], ['25 GB NVMe SSD', 149], ['50 GB NVMe SSD', 299], ['100 GB NVMe SSD', 599]],
            'secondary_disk' => [['No secondary disk', 0], ['250 GB SSD', 399], ['500 GB SSD', 799], ['1 TB SSD', 1499]],
            'backup_disk' => [['No backup disk', 0], ['100 GB backup storage', 199], ['250 GB backup storage', 399], ['500 GB backup storage', 699]],
            'raid' => [['No RAID', 0], ['RAID 1 - mirrored disks', 299], ['RAID 10 - striped mirrors', 599]],
            'control_panel' => [['No control panel', 0], ['DirectAdmin', 399], ['Plesk Web Admin', 699], ['cPanel Solo', 1499]],
            'antivirus' => [['Not enabled', 0], ['Premium server antivirus', 199]],
            'firewall' => [['Basic firewall', 0], ['Advanced firewall', 499], ['Managed firewall', 999]],
            'ip_addresses' => [['1 IPv4 address', 0], ['2 IPv4 addresses', 149], ['4 IPv4 addresses', 399], ['8 IPv4 addresses', 899]],
            'port_speed' => [['100 Mbps', 0], ['1 Gbps', 499], ['10 Gbps', 1999]],
            'data_transfer' => [['1 TB / month', 0], ['2 TB / month', 299], ['5 TB / month', 799], ['10 TB / month', 1499]],
        ];
        return array_map(function ($choices) {
            return array_map(function ($choice) { return ['id' => '0', 'label' => $choice[0], 'price' => self::money($choice[1])]; }, $choices);
        }, $examples);
    }

    public static function validate_config($input, $existing, $plan) {
        if (!is_array($input) || !is_array($input['resources'] ?? [])) throw new InvalidArgumentException('Invalid cart settings.');
        $currency = $input['currency'] ?? '';
        $symbol = $input['symbol'] ?? '';
        if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)) throw new InvalidArgumentException('Use a three-letter currency code, for example INR.');
        if (!is_string($symbol) || trim($symbol) === '' || strlen($symbol) > 16) throw new InvalidArgumentException('Enter a currency symbol of up to 16 bytes.');
        $config = [
            'pid' => (int) $plan['id'], 'name' => sanitize_text_field($plan['name']), 'enabled' => !empty($input['enabled']),
            'currency' => $currency, 'symbol' => sanitize_text_field($symbol), 'base_price' => self::money($input['base_price'] ?? ''), 'resources' => [],
            'demo_pricing' => !empty($input['demo_pricing']),
        ];
        foreach (self::RESOURCES as $key => $name) {
            $rows = $input['resources'][$key] ?? [];
            if (!is_array($rows) || count($rows) > 20) throw new InvalidArgumentException($name . ': maximum 20 choices.');
            $ids = array_column($existing['resources'][$key]['choices'] ?? [], 'id');
            $choices = [];
            $seen = [];
            $labels = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_string($row['label'] ?? '') || !is_scalar($row['price'] ?? '')) throw new InvalidArgumentException($name . ': invalid choice.');
                $id = $row['id'] ?? '0';
                if (!is_scalar($id)) throw new InvalidArgumentException($name . ': invalid choice ID.');
                $id = (string) $id;
                $label = sanitize_text_field($row['label'] ?? '');
                $price = trim((string) ($row['price'] ?? ''));
                if ($label === '' && $price === '' && ($id === '0' || $id === '')) continue;
                if ($label === '' || strlen($label) > 100 || isset($labels[strtolower($label)])) throw new InvalidArgumentException($name . ': choice labels must be unique and 1-100 characters long.');
                if ($id !== '0' && $id !== '' && (!in_array($id, $ids, true) || isset($seen[$id]))) throw new InvalidArgumentException($name . ': unknown or duplicated choice ID. Reload this editor.');
                if ($id === '0' || $id === '') $id = wp_generate_uuid4();
                $seen[$id] = true;
                $labels[strtolower($label)] = true;
                $choices[] = ['id' => $id, 'label' => $label, 'price' => self::money($price)];
            }
            if ($key === 'antivirus' && $choices && count($choices) !== 2) throw new InvalidArgumentException('Antivirus needs exactly two choices: unchecked (for example No) first, checked second.');
            if ($choices) $config['resources'][$key] = ['label' => $name, 'type' => $key === 'antivirus' ? 'checkbox' : 'select', 'choices' => $choices];
        }
        if (isset($existing['manual_48'])) $config['manual_48'] = $existing['manual_48'];
        return $config;
    }

    public static function save($pid, $input, $version, $plan) {
        global $wpdb;
        if ((int) $pid !== (int) $plan['id']) throw new InvalidArgumentException('Plan does not exist.');
        // Compare-and-swap the option so concurrent editors cannot overwrite each other's plans.
        // add_option() can upsert a raced first save; never replace an existing row here.
        $inserted = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", self::OPTION, maybe_serialize([])));
        if ($inserted === false) throw new RuntimeException('WordPress could not initialize the cart settings.');
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION));
            $all = maybe_unserialize($raw);
            if (!is_array($all)) throw new RuntimeException('Stored cart settings are invalid. No settings were overwritten.');
            $current = $all[$pid] ?? null;
            if (!is_string($version) || !hash_equals(self::revision($current ?? []), $version)) throw new RuntimeException('This plan changed since you opened it. Reload the editor before saving.');
            $config = self::validate_config($input, $current ?? [], $plan);
            if (isset($input['manual_48'])) {
                if (!is_array($input['manual_48']) || !class_exists('Rackmonk_Plans_Manager')) throw new RuntimeException('Manual pricing editor is unavailable. Reload before saving.');
                $native = Rackmonk_Native_Cart_Pricing::config(Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), $pid, $current ?? []);
                $manual = $input['manual_48'];
                if (!is_string($manual['version'] ?? null) || !hash_equals(self::revision($native), $manual['version'])) throw new RuntimeException('Native plan or prices changed. Reload the editor before saving.');
                $parse = function ($amount) {
                    if (!is_array($amount) || !isset($amount['recurring'], $amount['setup']) || !is_string($amount['recurring']) || !is_string($amount['setup'])) throw new InvalidArgumentException('Incomplete 48-month pricing. Reload the editor.');
                    $price = trim($amount['recurring']); $setup = trim($amount['setup']);
                    if ($price === '' && $setup === '') return null;
                    if ($price === '' || $setup === '') throw new InvalidArgumentException('Enter both the 48-month amount and setup fee, or leave both blank.');
                    return ['recurring' => self::money($price), 'setup' => self::money($setup)];
                };
                $previous = $current['manual_48'] ?? [];
                $preserved_choices = is_array($previous) && ($previous['currency'] ?? '') === $native['currency'] && is_array($previous['choices'] ?? null) ? $previous['choices'] : [];
                $saved = ['currency' => $native['currency'], 'base' => $parse($manual['base'] ?? null), 'choices' => $preserved_choices];
                foreach ($native['resources'] as $key => $resource) {
                    $option_id = substr($key, 7);
                    foreach ($resource['choices'] as $choice) $saved['choices'][$option_id][$choice['id']] = $parse($manual['choices'][$option_id][$choice['id']] ?? null);
                }
                $config['manual_48'] = $saved;
            }
            $all[$pid] = $config;
            $encoded = maybe_serialize($all);
            if ($encoded === $raw) return;
            $updated = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s", $encoded, self::OPTION, $raw));
            if ($updated === false) throw new RuntimeException('WordPress could not save the cart settings.');
            if ($updated) {
                wp_cache_delete(self::OPTION, 'options');
                wp_cache_delete('notoptions', 'options');
                return;
            }
        }
        throw new RuntimeException('Another editor is updating settings. Reload and try again.');
    }

    public static function price_config($config, $months, $choices, $revision) {
        if (!$config || empty($config['enabled'])) throw new InvalidArgumentException('This plan is not available in the website cart yet.');
        if (!is_int($months) || !in_array($months, [1, 12, 24, 36, 48], true)) throw new InvalidArgumentException('Choose a valid billing duration: 1, 12, 24, 36 or 48 months.');
        if (!is_array($choices)) throw new InvalidArgumentException('Invalid resource selection.');
        if (!is_string($revision) || !hash_equals(self::revision($config), $revision)) throw new RuntimeException('Plan settings changed. Reopen this configuration and review the current prices.');
        if (array_diff(array_keys($choices), array_keys($config['resources']))) throw new InvalidArgumentException('Unknown resource selection.');
        if (!empty($config['native_pricing'])) {
            $base = $config['prices'][$months] ?? null;
            if (!$base) throw new InvalidArgumentException('This billing duration is unavailable.');
            $base_cents = (int) round((float) $base['recurring'] * 100);
            $setup_cents = (int) round((float) $base['setup'] * 100);
            $resource_cents = 0;
            $selection = [];
            foreach ($config['resources'] as $key => $resource) {
                $id = $choices[$key] ?? null;
                if (!is_string($id)) throw new InvalidArgumentException('Choose an option for ' . $resource['label'] . '.');
                $selected = null;
                foreach ($resource['choices'] as $choice) if ($choice['id'] === $id) $selected = $choice;
                if (!$selected || empty($selected['prices'][$months])) throw new InvalidArgumentException('This option is unavailable for the selected billing duration.');
                $amount = $selected['prices'][$months];
                $cents = (int) round((float) $amount['recurring'] * 100);
                $resource_cents += $cents;
                $setup_cents += (int) round((float) $amount['setup'] * 100);
                $selection[] = ['key' => $key, 'label' => $resource['label'], 'choice' => $selected['label'], 'id' => $id, 'price' => $cents / 100 / $months, 'term_price' => $cents / 100, 'setup' => (float) $amount['setup']];
            }
            return ['pid' => $config['pid'], 'name' => $config['name'], 'currency' => $config['currency'], 'symbol' => $config['symbol'], 'months' => $months,
                'base_monthly' => $base_cents / 100 / $months, 'resources_monthly' => $resource_cents / 100 / $months, 'monthly' => ($base_cents + $resource_cents) / 100 / $months,
                'base_term' => $base_cents / 100, 'resources_term' => $resource_cents / 100, 'recurring' => ($base_cents + $resource_cents) / 100, 'setup' => $setup_cents / 100,
                'total' => ($base_cents + $resource_cents + $setup_cents) / 100, 'selection' => $selection, 'choices' => $choices, 'revision' => self::revision($config), 'native_pricing' => true, 'demo_pricing' => false];
        }
        $base_cents = (int) round((float) $config['base_price'] * 100);
        $resource_cents = 0;
        $selection = [];
        foreach ($config['resources'] as $key => $resource) {
            $id = $choices[$key] ?? null;
            if (!is_string($id)) throw new InvalidArgumentException('Choose an option for ' . $resource['label'] . '.');
            $selected = null;
            foreach ($resource['choices'] as $choice) if ($choice['id'] === $id) $selected = $choice;
            if (!$selected) throw new InvalidArgumentException('The selected ' . $resource['label'] . ' choice is no longer available.');
            $resource_cents += (int) round((float) $selected['price'] * 100);
            $selection[] = ['key' => $key, 'label' => $resource['label'], 'choice' => $selected['label'], 'price' => $selected['price'], 'id' => $id];
        }
        return [
            'pid' => $config['pid'], 'name' => $config['name'], 'currency' => $config['currency'], 'symbol' => $config['symbol'],
            'months' => $months, 'base_monthly' => $base_cents / 100, 'resources_monthly' => $resource_cents / 100,
            'monthly' => ($base_cents + $resource_cents) / 100, 'total' => ($base_cents + $resource_cents) * $months / 100,
            'selection' => $selection, 'choices' => $choices, 'revision' => self::revision($config),
            'demo_pricing' => !empty($config['demo_pricing']),
        ];
    }

    public function register_routes() {
        register_rest_route('rackmonk/v1', '/cart/quote', ['methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => [$this, 'quote']]);
    }

    public function quote($request) {
        $body = $request->get_json_params();
        if (!is_array($body) || !is_int($body['pid'] ?? null) || $body['pid'] < 1) return new WP_Error('rackmonk_invalid_plan', 'Choose a valid plan.', ['status' => 400]);
        try {
            $quote = self::price_config(self::runtime_config($body['pid']), $body['months'] ?? null, $body['choices'] ?? null, $body['revision'] ?? null);
            $response = rest_ensure_response($quote);
            $response->header('Cache-Control', 'no-store');
            return $response;
        } catch (InvalidArgumentException $error) {
            return new WP_Error('rackmonk_invalid_selection', $error->getMessage(), ['status' => 400]);
        } catch (RuntimeException $error) {
            return new WP_Error('rackmonk_plan_changed', $error->getMessage(), ['status' => 409]);
        }
    }

    public static function encode_plan_id($pid) {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt((string) (int) $pid, 'aes-256-gcm', hash('sha256', wp_salt('auth'), true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) throw new RuntimeException('Could not create the plan link.');
        return rtrim(strtr(base64_encode($iv . $tag . $encrypted), '+/', '-_'), '=');
    }

    public static function decode_plan_id($value) {
        if (!is_string($value) && !is_int($value)) return 0;
        $value = (string) $value;
        // Preserve previously shared numeric links.
        if (ctype_digit($value)) return (int) $value;
        if (!preg_match('/^[A-Za-z0-9_-]{39,128}$/D', $value)) return 0;
        $raw = base64_decode(strtr($value, '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 29) return 0;
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', wp_salt('auth'), true), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return is_string($plain) && ctype_digit($plain) && (int) $plain > 0 ? (int) $plain : 0;
    }

    public static function get_cart_url($pid = 0) {
        $page_id = (int) get_option('rackmonk_website_cart_page_id', 0);
        $page = $page_id ? get_post($page_id) : null;
        $url = $page && $page->post_type === 'page' && $page->post_status === 'publish' && has_shortcode($page->post_content, 'rackmonk_cart')
            ? get_permalink($page_id) : add_query_arg('rackmonk_cart', '1', home_url('/'));
        return $pid ? add_query_arg('plan', self::encode_plan_id($pid), $url) : $url;
    }

    public static function ensure_page() {
        $id = (int) get_option('rackmonk_website_cart_page_id', 0);
        if ($id && get_post_status($id) === 'publish' && has_shortcode((string) get_post_field('post_content', $id), 'rackmonk_cart')) return $id;
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Hosting Cart', 'post_name' => 'hosting-cart', 'post_content' => '[rackmonk_cart]', 'post_author' => get_current_user_id()], true);
        if (is_wp_error($id)) return $id;
        update_option('rackmonk_website_cart_page_id', $id, false);
        return $id;
    }

    public function enqueue_assets() {
        global $post;
        if (!get_query_var('rackmonk_cart') && !($post && has_shortcode($post->post_content, 'rackmonk_cart'))) return;
        wp_enqueue_style('rackmonk-website-cart', RACKMONK_WHMCS_URL . 'assets/css/website-cart.css', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/css/website-cart.css'));
        wp_enqueue_script('rackmonk-website-cart', RACKMONK_WHMCS_URL . 'assets/js/website-cart.js', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/js/website-cart.js'), true);
    }

    public function virtual_page() {
        if ((string) get_query_var('rackmonk_cart') !== '1') return;
        status_header(200);
        nocache_headers();
        get_header();
        echo $this->render();
        get_footer();
        exit;
    }

    public function render() {
        $all = get_option(self::OPTION, []);
        $catalog = [];
        foreach (is_array($all) ? $all : [] as $pid => $settings) {
            if (!is_array($settings) || empty($settings['enabled'])) continue;
            try {
                $config = self::runtime_config((int) $pid);
                $config['revision'] = self::revision($config);
                $catalog[] = $config;
            } catch (RuntimeException $error) { /* Unavailable native plans must not be sold using local fallback prices. */ }
        }
        $requested = $_GET['plan'] ?? 0;
        $term = $_GET['term'] ?? 1;
        $requested_plan = self::decode_plan_id($requested);
        $data = ['catalog' => $catalog, 'requestedPlan' => $requested_plan ?: (!empty($requested) ? -1 : 0), 'requestedTerm' => is_scalar($term) ? absint($term) : 1, 'quoteUrl' => rest_url('rackmonk/v1/cart/quote'), 'nonce' => wp_create_nonce('wp_rest'), 'homeUrl' => home_url('/'), 'content' => array_merge(self::content_defaults(), Rackmonk_Site_Content::all())];
        if (class_exists('WC_AJAX')) {
            $data['checkoutUrl'] = WC_AJAX::get_endpoint('rackmonk_checkout');
            $data['checkoutNonce'] = wp_create_nonce('rackmonk_checkout');
            $data['cartUrl'] = wc_get_cart_url();
            if (isset($_GET['edit_cart_item'])) {
                try {
                    $data['editItem'] = Rackmonk_Website_Checkout::editing_item(wp_unslash($_GET['edit_cart_item']));
                } catch (InvalidArgumentException $error) {
                    $data['editError'] = $error->getMessage();
                }
            }
        }
        return '<div class="rm-website-cart"><script type="application/json" data-rm-cart-data>' . wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script><noscript>' . esc_html($data['content']['cart_noscript']) . '</noscript></div>';
    }

    public static function render_editor($plans, $currency, $selected_pid = null) {
        $selection = $selected_pid ?? ($_GET['resource_plan'] ?? ($plans[0]['id'] ?? 0));
        $pid = is_scalar($selection) ? absint($selection) : 0;
        $plan = null;
        foreach ($plans as $item) if ((int) $item['id'] === $pid) $plan = $item;
        $config = self::get_config($pid) ?? [];
        $native = null;
        try {
            if ($plan && class_exists('Rackmonk_Plans_Manager')) $native = Rackmonk_Native_Cart_Pricing::config(Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), $pid, $config);
        } catch (RuntimeException $error) { /* Preserve manual settings when native data is unavailable. */ }
        ?>
        <section id="resource-builder" class="rm-panel rm-resource-panel" data-admin-section="resources" data-admin-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-admin-nonce="<?php echo esc_attr(wp_create_nonce('rackmonk_admin_section')); ?>">
            <div class="rm-section-heading"><div><p class="rm-eyebrow">WORDPRESS / WOOCOMMERCE</p><h2>Website plan configurator</h2><p>The enable setting controls availability. Choices and 1-36 month prices come from linked native WHMCS products and options. The 48-month prices are configured separately below.</p></div><a class="rm-button rm-button-secondary" href="<?php echo esc_url(self::get_cart_url()); ?>" target="_blank" rel="noopener noreferrer">Open website cart</a></div>
            <div class="rm-native-load-status" data-admin-status role="status" aria-live="polite" hidden><span data-admin-message></span><button type="button" class="rm-button rm-button-secondary" data-admin-retry hidden>Retry</button></div>
            <div class="rm-resource-picker"><label for="rm-resource-plan">Choose a catalog plan</label><select id="rm-resource-plan" name="resource_plan" data-admin-select><?php foreach ($plans as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($pid, $item['id']); ?>><?php echo esc_html($item['name'] . ' (#' . $item['id'] . ')'); ?></option><?php endforeach; ?></select></div>
            <form method="post" class="rm-resource-editor">
                <?php wp_nonce_field('rackmonk_save_resources_' . $pid); ?>
                <input type="hidden" name="rackmonk_action" value="save_resources"><input type="hidden" name="resource_pid" value="<?php echo esc_attr($pid); ?>"><input type="hidden" name="resource_version" value="<?php echo esc_attr(self::revision($config)); ?>">
                <div class="rm-form-section"><div class="rm-form-grid">
                    <div class="rm-field"><label for="rm-cart-base">Website base price / month *</label><input type="number" id="rm-cart-base" name="website_cart[base_price]" min="0" max="9999999.99" step="0.01" required value="<?php echo esc_attr($config['base_price'] ?? ''); ?>" placeholder="Set your website price"></div>
                    <div class="rm-field"><label for="rm-cart-currency">Currency code *</label><input type="text" id="rm-cart-currency" name="website_cart[currency]" required maxlength="3" pattern="[A-Z]{3}" value="<?php echo esc_attr($config['currency'] ?? ($currency['code'] ?? 'INR')); ?>"></div>
                    <div class="rm-field"><label for="rm-cart-symbol">Currency symbol *</label><input type="text" id="rm-cart-symbol" name="website_cart[symbol]" required maxlength="8" value="<?php echo esc_attr($config['symbol'] ?? ($currency['prefix'] ?? 'INR')); ?>"></div>
                    <label class="rm-featured-toggle"><input type="checkbox" name="website_cart[enabled]" value="1" <?php checked(!empty($config['enabled'])); ?>><span><strong>Enable this plan in website cart</strong><small>Only enabled, saved plans are available to clients.</small></span></label>
                    <label class="rm-featured-toggle"><input type="checkbox" name="website_cart[demo_pricing]" value="1" <?php checked(!empty($config['demo_pricing'])); ?>><span><strong>Demo resource pricing</strong><small>Shows a customer-facing demo warning. Clear this only after verifying all choices, compatibility and prices for production.</small></span></label>
                </div></div>
                <div class="rm-form-section">
                    <h3>Manual 48-month website pricing</h3>
                    <p>Enter the full 48-month amount and one-time setup fee in the WHMCS default currency. Enter 0 explicitly for a free amount. Leave both fields blank to make that item unavailable for 48 months. These prices are website-only; they do not configure WHMCS provisioning or automatic renewal.</p>
                    <?php if ($native): ?>
                        <p>Currency: <strong><?php echo esc_html($native['currency']); ?></strong></p>
                        <input type="hidden" name="website_cart[manual_48][version]" value="<?php echo esc_attr(self::revision($native)); ?>">
                        <?php
                        $manual_rows = [['Plan: ' . $native['name'], 'base', $native['prices'][48]]];
                        foreach ($native['resources'] as $key => $resource) {
                            $option_id = substr($key, 7);
                            foreach ($resource['choices'] as $choice) $manual_rows[] = [$resource['label'] . ' (#' . $option_id . ') / ' . $choice['label'] . ' (#' . $choice['id'] . ')', 'choices][' . $option_id . '][' . $choice['id'], $choice['prices'][48]];
                        }
                        foreach ($manual_rows as [$label, $field, $amount]): ?>
                            <fieldset class="rm-resource-card"><legend><?php echo esc_html($label); ?></legend><div class="rm-form-grid">
                                <div class="rm-field"><label>48-month amount <input type="number" min="0" max="9999999.99" step="0.01" name="website_cart[manual_48][<?php echo esc_attr($field); ?>][recurring]" value="<?php echo esc_attr($amount['recurring'] ?? ''); ?>"></label></div>
                                <div class="rm-field"><label>Setup fee <input type="number" min="0" max="9999999.99" step="0.01" name="website_cart[manual_48][<?php echo esc_attr($field); ?>][setup]" value="<?php echo esc_attr($amount['setup'] ?? ''); ?>"></label></div>
                            </div></fieldset>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Native plan data is unavailable. Manual 48-month settings are preserved but cannot be edited until it is available.</p>
                    <?php endif; ?>
                </div>
                <div class="rm-resource-explanation"><strong>Preserved legacy website-only settings.</strong> The amounts and choices below do not determine customer quotes. Edit 1-36 month product and linked option prices in WHMCS. WooCommerce calculates checkout taxes; the WHMCS default currency must match the store currency.</div>
                <div class="rm-resource-grid">
                <?php foreach (self::RESOURCES as $key => $name):
                    $choices = $config['resources'][$key]['choices'] ?? [['id' => '0', 'label' => '', 'price' => '']];
                ?>
                    <fieldset class="rm-resource-card" data-resource="<?php echo esc_attr($key); ?>" data-max-choices="<?php echo $key === 'antivirus' ? 2 : 20; ?>" data-next-index="<?php echo count($choices); ?>"><legend><?php echo esc_html($name); ?></legend><div class="rm-resource-column-head"><span>Client-visible choice</span><span>Extra / month</span><span></span></div><div data-resource-rows>
                    <?php foreach ($choices as $index => $choice): ?>
                        <div class="rm-resource-choice"><input type="hidden" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][id]" value="<?php echo esc_attr($choice['id']); ?>"><input type="text" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][label]" value="<?php echo esc_attr($choice['label']); ?>" maxlength="100" placeholder="<?php echo esc_attr($name . ' choice'); ?>" aria-label="<?php echo esc_attr($name . ' choice label'); ?>"><input type="number" min="0" max="9999999.99" step="0.01" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][price]" value="<?php echo esc_attr($choice['price']); ?>" placeholder="0.00" aria-label="<?php echo esc_attr($name . ' monthly price'); ?>"><button type="button" class="rm-remove-choice" data-remove-choice aria-label="<?php echo esc_attr('Remove ' . $name . ' choice'); ?>">&times;</button></div>
                    <?php endforeach; ?>
                    </div><button type="button" class="rm-add-choice" data-add-choice>+ Add choice</button></fieldset>
                <?php endforeach; ?>
                </div>
                <div class="rm-resource-explanation">Native WHMCS changes invalidate affected saved cart quotes. Checkout creates WooCommerce orders; it does not automatically provision WHMCS resources or set up recurring billing.</div>
                <input type="hidden" name="resource_complete" value="1">
                <div class="rm-form-footer"><span>WordPress settings &middot; No WHMCS checkout redirect</span><button class="rm-button rm-button-secondary" type="submit" name="rackmonk_apply_demo" value="1" formnovalidate <?php disabled(!$plan || ($config['currency'] ?? $currency['code'] ?? '') !== 'INR'); ?>>Fill missing demo options</button><button class="rm-button rm-button-primary" type="submit" <?php disabled(!$plan); ?>>Save website cart settings</button></div>
            </form>
        </section>
        <?php
    }
}

new Rackmonk_Website_Cart();
