<?php
/** Connect server-validated hosting configurations to WooCommerce sessions and orders. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Website_Checkout {
    public static function content_defaults() {
        return [
            'commerce_cart_title' => 'Your hosting cart',
            'commerce_checkout_title' => 'Let\'s get you online',
            'commerce_order_title' => 'Thank you for your order',
            'commerce_cart_intro' => 'Review your plans and resources. Your next big idea starts here.',
            'commerce_checkout_intro' => 'Your plan is ready. Add your billing details and review your order below.',
            'commerce_order_intro' => 'Review your order details below. Server activation is handled after order review.',
            'commerce_eyebrow' => 'YOUR NEXT CHAPTER STARTS HERE',
            'commerce_configure_step' => '01 Configure',
            'commerce_cart_step' => '02 Cart',
            'commerce_checkout_step' => '03 Checkout',
            'commerce_explore_cta' => 'Explore hosting plans',
            'commerce_edit_cta' => 'Customize configuration',
            'commerce_pricing_note' => 'Hosting is billed for the selected term. Payment and taxes are shown in your order summary. Server activation is handled after order review.',
        ];
    }

    public function __construct() {
        add_action('wc_ajax_rackmonk_checkout', [$this, 'prepare_checkout']);
        add_action('woocommerce_before_calculate_totals', [$this, 'refresh_prices']);
        add_action('woocommerce_check_cart_items', [$this, 'validate_cart']);
        add_filter('woocommerce_get_item_data', [$this, 'item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'order_item'], 10, 4);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_filter('the_content', [$this, 'page_content'], 20);
        add_filter('woocommerce_return_to_shop_redirect', function ($url) { return Rackmonk_Website_Cart::get_cart_url(); });
        add_filter('woocommerce_cart_item_permalink', function ($url, $item, $key) {
            return isset($item['rackmonk_configuration']) ? self::configuration_url($key, $item) : $url;
        }, 10, 3);
        add_action('woocommerce_after_cart_item_name', function ($item, $key) {
            if (isset($item['rackmonk_configuration'])) echo '<a class="rm-edit-configuration" href="' . esc_url(self::configuration_url($key, $item)) . '">' . esc_html(Rackmonk_Site_Content::text('commerce_edit_cta', self::content_defaults()['commerce_edit_cta'])) . '</a>';
        }, 10, 2);
    }

    public static function configuration_url($key, $item) {
        return add_query_arg('edit_cart_item', $key, Rackmonk_Website_Cart::get_cart_url($item['rackmonk_configuration']['pid']));
    }

    public static function editing_item($key) {
        if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) throw new InvalidArgumentException('This cart item is unavailable. Open your cart and choose a current plan.');
        if (!WC()->cart) wc_load_cart();
        $item = WC()->cart->get_cart()[$key] ?? null;
        if (!$item || empty($item['rackmonk_configuration'])) throw new InvalidArgumentException('This plan is no longer in your cart. Open your cart and select another item.');
        $config = $item['rackmonk_configuration'];
        return [
            'key' => $key, 'pid' => $config['pid'], 'months' => $config['months'], 'choices' => $config['choices'], 'revision' => $config['revision'],
            'quantity' => $item['quantity'], 'token' => hash('sha256', wp_json_encode([$config, $item['quantity']])),
        ];
    }

    public static function price_item($item) {
        if (!is_array($item) || !is_int($item['pid'] ?? null) || $item['pid'] < 1) {
            throw new InvalidArgumentException('Choose a valid hosting plan.');
        }
        $quote = Rackmonk_Website_Cart::price_config(Rackmonk_Website_Cart::runtime_config($item['pid']), $item['months'] ?? null, $item['choices'] ?? null, $item['revision'] ?? null);
        if ($quote['currency'] !== get_woocommerce_currency()) {
            throw new InvalidArgumentException('This plan currency does not match the store currency. Please contact support.');
        }
        return $quote;
    }

    private static function product($quote) {
        $sku = 'rackmonk-hosting-' . $quote['pid'];
        $id = wc_get_product_id_by_sku($sku);
        $product = $id ? wc_get_product($id) : new WC_Product_Simple();
        if (!$product || ($id && !$product->get_meta('_rackmonk_hosting'))) {
            throw new RuntimeException('The hosting product could not be prepared. Contact support.');
        }
        $product->set_name($quote['name']);
        $product->set_sku($sku);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_regular_price((string) $quote['total']);
        $product->update_meta_data('_rackmonk_hosting', 1);
        $product->save();
        return $product->get_id();
    }

    public function prepare_checkout() {
        nocache_headers();
        if (!check_ajax_referer('rackmonk_checkout', 'nonce', false)) {
            wp_send_json_error(['message' => 'Your session expired. Refresh this page and try again.'], 403);
        }
        $raw = isset($_POST['items']) && is_string($_POST['items']) ? wp_unslash($_POST['items']) : '';
        $items = strlen($raw) <= 100000 ? json_decode($raw, true) : null;
        if (!is_array($items) || !array_is_list($items) || !$items || count($items) > 20) {
            wp_send_json_error(['message' => 'Add between 1 and 20 hosting plans before checkout.'], 400);
        }
        try {
            $target = null;
            if (isset($_POST['edit_key'])) {
                if (count($items) !== 1) throw new InvalidArgumentException('Update one cart configuration at a time.');
                $target = self::editing_item(wp_unslash($_POST['edit_key']));
                $token = isset($_POST['edit_token']) && is_string($_POST['edit_token']) ? wp_unslash($_POST['edit_token']) : '';
                if (!hash_equals($target['token'], $token)) throw new RuntimeException('This cart item changed in another tab. Reopen it from your cart before saving.');
            }
            // Validate the complete request before touching the customer's existing cart.
            $prepared = [];
            foreach ($items as $item) {
                $quote = self::price_item($item);
                $prepared[] = ['product_id' => self::product($quote), 'quote' => $quote];
            }
            if (!WC()->cart) wc_load_cart();
            $previous = WC()->cart->get_cart();
            $added = [];
            try {
                foreach ($prepared as $entry) {
                    $key = WC()->cart->add_to_cart($entry['product_id'], $target ? $target['quantity'] : 1, 0, [], [
                        'rackmonk_configuration' => $entry['quote'], 'rackmonk_line' => wp_generate_uuid4(),
                    ]);
                    if (!$key) throw new RuntimeException('A hosting plan could not be added. Your previous cart was preserved.');
                    $added[] = $key;
                }
            } catch (Throwable $error) {
                foreach ($added as $key) WC()->cart->remove_cart_item($key);
                throw $error;
            }
            // An edit replaces one owned line; a new configurator handoff replaces hosting lines only.
            foreach ($previous as $key => $item) {
                if (isset($item['rackmonk_configuration']) && (!$target || $key === $target['key'])) WC()->cart->remove_cart_item($key);
            }
            WC()->cart->calculate_totals();
            WC()->cart->set_session();
            WC()->session->set_customer_session_cookie(true);
            if ($target) wc_add_notice('Your hosting configuration has been updated.', 'success');
            wp_send_json_success(['redirect' => $target ? wc_get_cart_url() : wc_get_checkout_url(), 'cartUrl' => wc_get_cart_url()]);
        } catch (InvalidArgumentException | RuntimeException $error) {
            wp_send_json_error(['message' => $error->getMessage()], 409);
        }
    }

    public function refresh_prices($cart) {
        foreach ($cart->get_cart() as $key => $item) {
            if (!isset($item['rackmonk_configuration'])) continue;
            try {
                $quote = self::price_item($item['rackmonk_configuration']);
                $cart->cart_contents[$key]['rackmonk_configuration'] = $quote;
                // Each configuration needs its own product object, even for the same plan ID.
                $product = clone $item['data'];
                $product->set_price($quote['total']);
                $cart->cart_contents[$key]['data'] = $product;
            } catch (InvalidArgumentException | RuntimeException $error) {
                // Checkout validation blocks stale configurations instead of silently repricing them.
            }
        }
    }

    public function validate_cart() {
        foreach (WC()->cart->get_cart() as $item) {
            if (!isset($item['rackmonk_configuration'])) {
                if (!empty($item['data']) && $item['data']->get_meta('_rackmonk_hosting')) {
                    wc_add_notice('Configure your hosting plan before purchasing it.', 'error');
                }
                continue;
            }
            try {
                self::price_item($item['rackmonk_configuration']);
            } catch (InvalidArgumentException | RuntimeException $error) {
                $message = 'Hosting plan needs review: ' . $error->getMessage() . ' Remove it and configure it again.';
                if (!wc_has_notice($message, 'error')) wc_add_notice($message, 'error');
            }
        }
    }

    public function item_data($data, $item) {
        if (empty($item['rackmonk_configuration'])) return $data;
        $quote = $item['rackmonk_configuration'];
        $data[] = ['key' => 'Billing duration', 'value' => $quote['months'] . ' month(s)'];
        if (!empty($quote['demo_pricing'])) $data[] = ['key' => 'Pricing notice', 'value' => 'Demo upgrade prices - confirm before production use.'];
        foreach ($quote['selection'] as $selection) $data[] = ['key' => $selection['label'], 'value' => $selection['choice']];
        return $data;
    }

    public function order_item($line, $key, $values, $order) {
        if (empty($values['rackmonk_configuration'])) return;
        $quote = self::price_item($values['rackmonk_configuration']);
        $line->add_meta_data('_rackmonk_configuration', $quote, true);
        foreach ($this->item_data([], $values) as $field) $line->add_meta_data($field['key'], $field['value']);
    }

    public function assets() {
        if (!function_exists('is_cart') || (!is_cart() && !is_checkout())) return;
        wp_enqueue_style('rackmonk-checkout', RACKMONK_WHMCS_URL . 'assets/css/checkout.css', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/css/checkout.css'));
    }

    public function page_content($content) {
        if (!function_exists('is_cart') || (!is_cart() && !is_checkout()) || !in_the_loop() || !is_main_query()) return $content;
        $cart = is_cart();
        $copy = array_merge(self::content_defaults(), Rackmonk_Site_Content::all());
        $stage = $cart ? 'cart' : (is_order_received_page() ? 'order' : 'checkout');
        $title = $copy['commerce_' . $stage . '_title'];
        $intro = $copy['commerce_' . $stage . '_intro'];
        $steps = '<nav class="rm-commerce-steps" aria-label="Purchase steps"><a href="' . esc_url(Rackmonk_Website_Cart::get_cart_url()) . '">' . esc_html($copy['commerce_configure_step']) . '</a><a href="' . esc_url(wc_get_cart_url()) . '"' . ($cart ? ' aria-current="step"' : '') . '>' . esc_html($copy['commerce_cart_step']) . '</a><a href="' . esc_url(wc_get_checkout_url()) . '"' . (!$cart ? ' aria-current="step"' : '') . '>' . esc_html($copy['commerce_checkout_step']) . '</a></nav>';
        $brand = '<div class="rm-commerce-topbar"><a class="rm-commerce-brand" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name') ?: 'Rackmonk') . '<span>.</span></a><a href="' . esc_url(Rackmonk_Website_Cart::get_cart_url()) . '">' . esc_html($copy['commerce_explore_cta']) . ' &rarr;</a></div>';
        return '<section class="rm-commerce">' . $brand . '<header class="rm-commerce-header"><span>' . esc_html($copy['commerce_eyebrow']) . '</span><h1>' . esc_html($title) . '</h1><p>' . esc_html($intro) . '</p></header>' . $steps . $content . '<p class="rm-commerce-note">' . esc_html($copy['commerce_pricing_note']) . '</p></section>';
    }
}

new Rackmonk_Website_Checkout();
