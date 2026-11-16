<?php
/**
 * Rackmonk WHMCS plan management dashboard.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_Admin_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'handle_form_actions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_rackmonk_native_selection', [$this, 'load_native_selection']);
        add_action('wp_ajax_rackmonk_admin_section', [$this, 'load_admin_section']);
    }

    public function load_admin_section() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'You do not have permission to load these settings.'], 403);
        if (!check_ajax_referer('rackmonk_admin_section', 'nonce', false)) wp_send_json_error(['message' => 'Your session expired. Refresh this page before continuing.'], 403);
        $level = ob_get_level();
        try {
            $section = $_POST['section'] ?? '';
            if (!is_string($section) || !in_array($section, ['prices', 'resources', 'groups'], true)) throw new InvalidArgumentException('Unknown dashboard section.');
            $fields = $section === 'prices' ? ['pricing_plan'] : ($section === 'resources' ? ['resource_plan'] : ['config_category', 'config_plan', 'native_group']);
            $selection = [];
            foreach ($fields as $field) {
                $value = $_POST[$field] ?? '0';
                if (!is_scalar($value) || !ctype_digit((string) $value)) throw new InvalidArgumentException('Invalid dashboard selection.');
                $selection[$field] = (int) $value;
            }
            $manager = Rackmonk_Plans_Manager::get_instance();
            $pdo = $manager->get_whmcs_db();
            if (!$pdo) throw new RuntimeException('WHMCS is disconnected.');
            $plans = $manager->get_plans();
            $plan_ids = array_map('intval', array_column($plans, 'id'));
            if ($section !== 'groups') {
                $pid = $selection[$fields[0]];
                if (!$pid || !in_array($pid, $plan_ids, true)) throw new InvalidArgumentException('Choose an existing catalog plan.');
            } else {
                $categories = $manager->get_categories();
                $groups = Rackmonk_Configurable_Groups::overview($pdo);
                if ($selection['config_plan'] && !in_array($selection['config_plan'], $plan_ids, true)) throw new InvalidArgumentException('Choose an existing plan filter.');
                if ($selection['config_category'] && !in_array($selection['config_category'], array_map('intval', array_column($categories, 'id')), true)) throw new InvalidArgumentException('Choose an existing product category.');
                if ($selection['config_category'] && $selection['config_plan']) {
                    $matching_plans = array_filter($plans, function ($plan) use ($selection) { return (int) $plan['id'] === $selection['config_plan'] && (int) $plan['gid'] === $selection['config_category']; });
                    if (!$matching_plans) throw new InvalidArgumentException('The selected plan is not in this product category.');
                }
                if ($selection['native_group']) {
                    $matching_groups = array_filter($groups, function ($group) use ($selection) {
                        if ((int) $group['id'] !== $selection['native_group']) return false;
                        if (!$selection['config_category'] && !$selection['config_plan']) return true;
                        foreach ($group['products'] as $product) {
                            if ((!$selection['config_category'] || (int) $product['category_id'] === $selection['config_category']) && (!$selection['config_plan'] || (int) $product['id'] === $selection['config_plan'])) return true;
                        }
                        return false;
                    });
                    if (!$matching_groups) throw new InvalidArgumentException('The selected group does not match these filters.');
                }
            }
            ob_start();
            if ($section === 'prices') Rackmonk_Plan_Prices::render($plans, $pdo, $pid);
            elseif ($section === 'resources') Rackmonk_Website_Cart::render_editor($plans, $manager->get_default_currency(), $pid);
            else Rackmonk_Configurable_Groups::render($groups, $plans, $categories, $pdo, $manager->get_whmcs_system_url(), $selection);
            $html = ob_get_clean();
        } catch (Throwable $error) {
            while (ob_get_level() > $level) ob_end_clean();
            wp_send_json_error(['message' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Settings could not be loaded. Check the connection and retry.'], $error instanceof InvalidArgumentException ? 400 : 503);
        }
        wp_send_json_success(['section' => $section, 'html' => $html]);
    }

    public function load_native_selection() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'You do not have permission to load native settings.'], 403);
        if (!check_ajax_referer('rackmonk_native_selection', 'nonce', false)) wp_send_json_error(['message' => 'Your session expired. Refresh this page before continuing.'], 403);
        $level = ob_get_level();
        try {
            $ids = [];
            foreach (['native_group', 'native_option', 'native_choice'] as $key) {
                $value = $_POST[$key] ?? '0';
                if (!is_scalar($value) || !ctype_digit((string) $value)) throw new InvalidArgumentException('Invalid native selection.');
                $ids[$key] = (int) $value;
            }
            $manager = Rackmonk_Plans_Manager::get_instance();
            $state = Rackmonk_Configurable_Groups::group($manager->get_whmcs_db(), $ids['native_group']);
            $option = $state['options'][$ids['native_option']] ?? null;
            if (!$option) throw new InvalidArgumentException('This option is not in the selected group. Reload the group and try again.');
            $cid = $ids['native_choice'] ?: (int) (array_key_first($option['choices']) ?? 0);
            if ($cid && !isset($option['choices'][$cid])) throw new InvalidArgumentException('This choice is no longer available for the selected option.');
            ob_start();
            Rackmonk_Configurable_Groups::render_detail($state, $ids['native_option'], $cid, $manager->get_whmcs_system_url());
            $html = ob_get_clean();
        } catch (Throwable $error) {
            while (ob_get_level() > $level) ob_end_clean();
            wp_send_json_error(['message' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Native settings could not be loaded. Please try again.'], $error instanceof InvalidArgumentException ? 400 : 503);
        }
        wp_send_json_success(['html' => $html, 'group' => $ids['native_group'], 'option' => $ids['native_option'], 'choice' => $cid]);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_rackmonk-whmcs-plans') {
            return;
        }
        wp_enqueue_style('rackmonk-admin', RACKMONK_WHMCS_URL . 'assets/css/admin.css', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/css/admin.css'));
        wp_enqueue_script('rackmonk-admin', RACKMONK_WHMCS_URL . 'assets/js/admin.js', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/js/admin.js'), true);
    }

    public function register_menu() {
        add_menu_page(__('WHMCS Plans', 'rackmonk-whmcs'), __('WHMCS Plans', 'rackmonk-whmcs'), 'manage_options', 'rackmonk-whmcs-plans', [$this, 'render_page'], 'dashicons-cloud', 25);
    }

    public function handle_form_actions() {
        if (!current_user_can('manage_options') || ($_GET['page'] ?? '') !== 'rackmonk-whmcs-plans') {
            return;
        }

        if (($_POST['rackmonk_action'] ?? '') === 'save_native_choice') {
            $gid = isset($_POST['native_group']) && is_scalar($_POST['native_group']) ? absint($_POST['native_group']) : 0;
            $oid = isset($_POST['native_option']) && is_scalar($_POST['native_option']) ? absint($_POST['native_option']) : 0;
            $cid = isset($_POST['native_choice']) && is_scalar($_POST['native_choice']) ? absint($_POST['native_choice']) : 0;
            check_admin_referer('rackmonk_native_choice_' . $gid . '_' . $oid . '_' . $cid);
            try {
                if (($_POST['native_complete'] ?? '') !== '1') throw new RuntimeException('The native pricing form was incomplete. Nothing was saved.');
                Rackmonk_Configurable_Groups::save_choice(Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), $gid, $oid, $cid, wp_unslash($_POST['native_settings'] ?? []), wp_unslash($_POST['native_version'] ?? ''));
                $status = 'success';
                $message = 'Native WHMCS choice saved. Other choices, group links and website-cart settings were not changed.';
            } catch (Throwable $error) {
                $status = 'error';
                $message = $error instanceof PDOException ? 'Native settings could not be saved. Check the connection and retry.' : $error->getMessage();
            }
            wp_safe_redirect(add_query_arg(['page' => 'rackmonk-whmcs-plans', 'native_group' => $gid, 'native_option' => $oid, 'native_choice' => $cid, 'status' => $status, 'msg' => rawurlencode($message)], admin_url('admin.php')) . '#configuration-groups');
            exit;
        }

        if (($_POST['rackmonk_action'] ?? '') === 'save_plan_currency') {
            $pid = isset($_POST['pricing_plan']) && is_scalar($_POST['pricing_plan']) ? absint($_POST['pricing_plan']) : 0;
            $currency = isset($_POST['pricing_currency']) && is_scalar($_POST['pricing_currency']) ? absint($_POST['pricing_currency']) : 0;
            check_admin_referer('rackmonk_plan_currency_' . $pid . '_' . $currency);
            try {
                if (($_POST['price_complete'] ?? '') !== '1') throw new RuntimeException('The plan pricing form was incomplete. Nothing was saved.');
                Rackmonk_Plan_Prices::save_currency(Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), $pid, $currency, wp_unslash($_POST['plan_prices'] ?? []), wp_unslash($_POST['pricing_version'] ?? ''));
                $status = 'success';
                $message = 'Selected currency prices saved. Other currencies, resource choices and group links were not changed.';
            } catch (Throwable $error) {
                $status = 'error';
                $message = $error instanceof PDOException ? 'Plan prices could not be saved. Check the connection and retry.' : $error->getMessage();
            }
            wp_safe_redirect(add_query_arg(['page' => 'rackmonk-whmcs-plans', 'pricing_plan' => $pid, 'status' => $status, 'msg' => rawurlencode($message)], admin_url('admin.php')) . '#base-plan-prices');
            exit;
        }

        if (isset($_POST['rackmonk_action']) && $_POST['rackmonk_action'] === 'save_resources') {
            $pid = absint($_POST['resource_pid'] ?? 0);
            check_admin_referer('rackmonk_save_resources_' . $pid);
            try {
                if (($_POST['resource_complete'] ?? '') !== '1') throw new RuntimeException('The submitted form was incomplete. No settings were saved. Reduce the number of choices or increase PHP max_input_vars.');
                $plan = null;
                foreach (Rackmonk_Plans_Manager::get_instance()->get_plans() as $candidate) if ((int) $candidate['id'] === $pid) $plan = $candidate;
                if (!$plan) throw new InvalidArgumentException('Choose an existing catalog plan.');
                $settings = wp_unslash($_POST['website_cart'] ?? []);
                if (!is_array($settings)) throw new InvalidArgumentException('Invalid website cart settings.');
                $settings['resources'] = wp_unslash($_POST['resources'] ?? []);
                if (isset($_POST['rackmonk_apply_demo'])) {
                    if (($settings['currency'] ?? '') !== 'INR') throw new InvalidArgumentException('Demo upgrade prices are INR examples. Use INR or enter your own prices.');
                    if (!is_array($settings['resources'])) throw new InvalidArgumentException('Invalid resource settings.');
                    foreach (Rackmonk_Website_Cart::demo_resources($plan) as $key => $rows) {
                        $current = $settings['resources'][$key] ?? [];
                        if (!is_array($current)) throw new InvalidArgumentException('Invalid resource choices.');
                        $first = $current ? reset($current) : [];
                        if (!$current || (count($current) === 1 && is_array($first) && trim((string) ($first['label'] ?? '')) === '' && trim((string) ($first['price'] ?? '')) === '' && in_array((string) ($first['id'] ?? '0'), ['', '0'], true))) {
                            $settings['resources'][$key] = $rows;
                        }
                    }
                    $settings['demo_pricing'] = 1;
                }
                Rackmonk_Website_Cart::save($pid, $settings, wp_unslash($_POST['resource_version'] ?? ''), $plan);
                $status = 'success';
                $page = Rackmonk_Website_Cart::ensure_page();
                $message = is_wp_error($page) ? 'Settings saved. A cart page could not be published; the Open website cart link is still available.' : 'Website cart settings saved. Clients can customize this plan on your Hosting Cart page.';
            } catch (Throwable $error) {
                $status = 'error';
                $message = $error instanceof PDOException ? 'The catalog could not be loaded. Check the connection and retry.' : $error->getMessage();
            }
            wp_safe_redirect(add_query_arg(['page' => 'rackmonk-whmcs-plans', 'resource_plan' => $pid, 'status' => $status, 'msg' => rawurlencode($message)], admin_url('admin.php')) . '#resource-builder');
            exit;
        }

        if (isset($_POST['rackmonk_action']) && $_POST['rackmonk_action'] === 'add_plan') {
            check_admin_referer('rackmonk_add_plan_nonce');
            if (($_POST['create_complete'] ?? '') !== '1') {
                wp_safe_redirect(admin_url('admin.php?page=rackmonk-whmcs-plans&status=error&msg=' . rawurlencode('The plan creation form was incomplete. No plan or currency prices were created.')) . '#create-plan');
                exit;
            }
            $manager = Rackmonk_Plans_Manager::get_instance();
            $res = $manager->create_whmcs_plan([
                'gid'         => wp_unslash($_POST['gid'] ?? ''),
                'name'        => wp_unslash($_POST['name'] ?? ''),
                'tagline'     => wp_unslash($_POST['tagline'] ?? ''),
                'description' => wp_unslash($_POST['description'] ?? ''),
                'monthly'     => wp_unslash($_POST['monthly'] ?? ''),
                'annually'    => wp_unslash($_POST['annually'] ?? ''),
                'biennially'  => wp_unslash($_POST['biennially'] ?? ''),
                'triennially' => wp_unslash($_POST['triennially'] ?? ''),
                'currency_prices' => wp_unslash($_POST['currency_prices'] ?? []),
                'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
            ]);
            if (is_wp_error($res)) {
                wp_redirect(admin_url('admin.php?page=rackmonk-whmcs-plans&status=error&msg=' . urlencode($res->get_error_message())));
            } else {
                wp_redirect(admin_url('admin.php?page=rackmonk-whmcs-plans&pricing_plan=' . (int) $res . '&status=success&msg=' . urlencode('Plan successfully created in WHMCS!')) . '#base-plan-prices');
            }
            exit;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete_plan' && !empty($_GET['pid'])) {
            check_admin_referer('rackmonk_delete_plan_' . intval($_GET['pid']));
            $manager = Rackmonk_Plans_Manager::get_instance();
            $deleted = $manager->delete_whmcs_plan(intval($_GET['pid']));
            $message = $deleted ? 'Plan deleted successfully!' : 'Plan could not be deleted. Plans linked to services or configurable options must be retired in WHMCS.';
            wp_redirect(admin_url('admin.php?page=rackmonk-whmcs-plans&status=' . ($deleted ? 'success' : 'error') . '&msg=' . urlencode($message)));
            exit;
        }

        if (isset($_POST['rackmonk_action']) && $_POST['rackmonk_action'] === 'add_group') {
            check_admin_referer('rackmonk_add_group_nonce');
            $manager = Rackmonk_Plans_Manager::get_instance();
            $name = sanitize_text_field($_POST['group_name'] ?? '');
            $tagline = sanitize_text_field($_POST['group_tagline'] ?? '');
            if (!empty($name)) {
                $manager->create_whmcs_group($name, $tagline);
                wp_redirect(admin_url('admin.php?page=rackmonk-whmcs-plans&status=success&msg=' . urlencode('Product Category created in WHMCS!')));
                exit;
            }
        }
    }

    public function render_page() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('You do not have permission to manage WHMCS plans.', 'rackmonk-whmcs'));
        $manager = Rackmonk_Plans_Manager::get_instance();
        $pdo = $manager->get_whmcs_db();
        $categories = $pdo ? $manager->get_categories() : [];
        $plans = $pdo ? $manager->get_plans() : [];
        $currency = $manager->get_default_currency();
        $curr_symbol = $currency['prefix'] ?? 'INR';
        $whmcs_url = $manager->get_whmcs_system_url();
        $groups = [];
        $group_error = '';
        try { $groups = Rackmonk_Configurable_Groups::overview($pdo); }
        catch (Throwable $error) { $group_error = 'Native configuration groups could not be loaded. Check your WHMCS connection.'; }
        $plan_groups = [];
        foreach ($groups as $group) foreach ($group['products'] as $product) $plan_groups[(int) $product['id']][] = $group;
        $usd_currency = null;
        if ($pdo) {
            try { $usd_currency = $pdo->query("SELECT id, code, prefix FROM tblcurrencies WHERE code = 'USD'")->fetch(PDO::FETCH_ASSOC) ?: null; }
            catch (Throwable $error) { $group_error = 'WHMCS currency settings could not be loaded. Check the connection.'; }
        }
        ?>
        <div class="wrap rackmonk-admin-wrap">
            <header class="rm-hero">
                <div class="rm-hero-top"><span class="rm-brand"><span class="dashicons dashicons-cloud" aria-hidden="true"></span> RACKMONK <span class="rm-brand-divider">/</span> HOSTING CONTROL</span><span class="rm-connection <?php echo $pdo ? '' : 'is-offline'; ?>"><span></span> WHMCS <?php echo $pdo ? 'connected' : 'disconnected'; ?></span></div>
                <div class="rm-hero-main">
                    <div><p class="rm-eyebrow">YOUR HOSTING CATALOG, SIMPLIFIED</p><h1>Plans &amp; Customization</h1><p>Basic plans from WHMCS. Resource choices organized by configuration group.</p></div>
                    <div class="rm-hero-actions"><a class="rm-button rm-button-light" href="<?php echo esc_url($whmcs_url . 'admin/'); ?>" target="_blank" rel="noopener noreferrer">WHMCS Admin <span class="dashicons dashicons-external" aria-hidden="true"></span></a><a class="rm-button rm-button-primary" href="#create-plan"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> Create a plan</a></div>
                </div>
                <nav class="rm-nav" aria-label="Dashboard sections"><a href="#plans" class="is-active">Plan overview</a><a href="#base-plan-prices">INR / USD prices</a><a href="#configuration-groups">Configuration groups</a><a href="#website-cart-settings">Website cart settings</a><a href="#create-plan">Create plan</a><a href="#publish-plans">Publish on website <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></a></nav>
            </header>

            <?php if (isset($_GET['status'])): ?>
                <div class="notice notice-<?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html(urldecode($_GET['msg'] ?? '')); ?></p></div>
            <?php endif; ?>
            <?php if (!$pdo): ?>
                <div class="rm-alert"><span class="dashicons dashicons-warning" aria-hidden="true"></span><div><strong>WHMCS database is unavailable.</strong> Reconnect WHMCS to load real plans, categories and configuration groups. Sample plans are not editable here.</div></div>
            <?php endif; ?>
            <?php if ($group_error): ?><div class="rm-alert" role="alert"><?php echo esc_html($group_error); ?></div><?php endif; ?>

            <section class="rm-stats" aria-label="Catalog overview">
                <div class="rm-stat"><span class="rm-stat-icon"><span class="dashicons dashicons-products" aria-hidden="true"></span></span><div><span class="rm-stat-label">Available plans</span><strong><?php echo count($plans); ?><small>in your catalog</small></strong></div></div>
                <div class="rm-stat"><span class="rm-stat-icon rm-icon-purple"><span class="dashicons dashicons-category" aria-hidden="true"></span></span><div><span class="rm-stat-label">Product categories</span><strong><?php echo count($categories); ?><small>hosting groups</small></strong></div></div>
                <div class="rm-stat"><span class="rm-stat-icon rm-icon-amber"><span class="dashicons dashicons-admin-settings" aria-hidden="true"></span></span><div><span class="rm-stat-label">Configuration groups</span><strong><?php echo count($groups); ?><small>native WHMCS option groups</small></strong></div></div>
                <div class="rm-stat"><span class="rm-stat-icon rm-icon-green"><span class="dashicons dashicons-money-alt" aria-hidden="true"></span></span><div><span class="rm-stat-label">Store currency</span><strong><?php echo esc_html($currency['code']); ?><small><?php echo esc_html($curr_symbol); ?> &middot; Default currency</small></strong></div></div>
            </section>

            <section id="plans" class="rm-panel">
                <div class="rm-section-heading"><div><p class="rm-eyebrow">THE BIG PICTURE</p><h2>Your hosting plans <span class="rm-count"><?php echo count($plans); ?></span></h2><p>Base prices as displayed on the website, with native customization groups listed separately.</p></div><span class="rm-subtle-label"><span class="dashicons dashicons-update" aria-hidden="true"></span> Fetched from WHMCS</span></div>
                <div class="rm-toolbar">
                    <div class="rm-search"><span class="dashicons dashicons-search" aria-hidden="true"></span><label class="screen-reader-text" for="rm-plan-search">Search plans</label><input id="rm-plan-search" type="search" placeholder="Search by plan, category or feature..."></div>
                    <div class="rm-filter"><label for="rm-category-filter">Category</label><select id="rm-category-filter"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html($cat['name']); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="rm-table-scroll" role="region" aria-label="Hosting plans and billing prices" tabindex="0">
                    <table class="rm-plans-table"><thead><tr><th scope="col">Plan details</th><th scope="col">Top features</th><th scope="col">Configuration groups</th><th scope="col">Monthly</th><th scope="col">12 months</th><th scope="col">24 months</th><th scope="col" class="rm-best-column">48 months <span>WEBSITE</span></th><th scope="col">Actions</th></tr></thead><tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr data-rm-plan data-gid="<?php echo esc_attr($p['gid']); ?>" data-search="<?php echo esc_attr($p['name'] . ' ' . $p['group_name'] . ' ' . implode(' ', $p['features'])); ?>">
                            <td><span class="rm-category-badge"><?php echo esc_html($p['group_name']); ?></span><strong class="rm-plan-name"><?php echo esc_html($p['name']); ?></strong><span class="rm-plan-meta">#<?php echo esc_html($p['id']); ?><?php if ($p['is_popular']): ?> <span class="rm-popular">Popular</span><?php endif; ?></span></td>
                            <td><ul class="rm-feature-list"><?php foreach (array_slice($p['features'], 0, 2) as $feature): ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?></ul><?php if (count($p['features']) > 2): ?><details class="rm-more-features"><summary>+<?php echo count($p['features']) - 2; ?> more features</summary><ul class="rm-feature-list"><?php foreach (array_slice($p['features'], 2) as $feature): ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?></ul></details><?php endif; ?></td>
                            <td class="rm-plan-groups"><?php foreach ($plan_groups[(int) $p['id']] ?? [] as $group): ?><a href="<?php echo esc_url(add_query_arg(['page' => 'rackmonk-whmcs-plans', 'native_group' => $group['id']], admin_url('admin.php')) . '#configuration-groups'); ?>"><?php echo esc_html($group['name']); ?></a><small><?php echo esc_html($group['option_count'] . ' options / ' . $group['choice_count'] . ' choices'); ?></small><?php endforeach; ?><?php if (empty($plan_groups[(int) $p['id']])): ?><span class="rm-native-muted">No linked native group</span><?php endif; ?></td>
                            <?php foreach (['1', '12', '24', '48'] as $term): $cycle = $p['cycles'][$term]; ?>
                                <td class="rm-price-cell <?php echo $term === '48' ? 'rm-best-column' : ''; ?>"><strong><?php echo esc_html($p['currency'] . number_format($cycle['price_per_month'], 0)); ?></strong><span class="rm-per-month">/mo</span><?php if ($cycle['discount_text']): ?><span class="rm-saving"><?php echo esc_html($cycle['discount_text']); ?></span><?php else: ?><span class="rm-regular">Regular price</span><?php endif; ?></td>
                            <?php endforeach; ?>
                            <td><div class="rm-row-actions"><a class="rm-icon-button" href="<?php echo esc_url($p['order_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr('Preview order for ' . $p['name']); ?>" title="Preview order"><span class="dashicons dashicons-external" aria-hidden="true"></span></a><a class="rm-icon-button rm-delete" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=rackmonk-whmcs-plans&action=delete_plan&pid=' . $p['id']), 'rackmonk_delete_plan_' . $p['id'])); ?>" data-rm-delete aria-label="<?php echo esc_attr('Delete ' . $p['name']); ?>" title="Delete plan"><span class="dashicons dashicons-trash" aria-hidden="true"></span></a></div></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr id="rm-empty-state" <?php echo !empty($plans) ? 'hidden' : ''; ?>><td colspan="8"><div class="rm-empty"><span class="dashicons dashicons-search" aria-hidden="true"></span><strong>No plans found</strong><p>Try another search or category, or create a new hosting plan.</p></div></td></tr>
                    </tbody></table>
                </div>
                <div class="rm-table-footer"><span id="rm-results-count" role="status" aria-live="polite">Showing <?php echo count($plans); ?> plans</span><span>4 billing durations &middot; One connected catalog</span></div>
            </section>

            <?php Rackmonk_Plan_Prices::render($plans, $pdo); ?>
            <?php Rackmonk_Configurable_Groups::render($groups, $plans, $categories, $pdo, $whmcs_url); ?>

            <details id="website-cart-settings" class="rm-website-settings" <?php echo isset($_GET['resource_plan']) ? 'open' : ''; ?>>
                <summary>Website cart settings <span>Separate WordPress / WooCommerce configuration, not native WHMCS groups</span></summary>
                <?php if ($pdo) Rackmonk_Website_Cart::render_editor($plans, $currency); ?>
            </details>

            <div class="rm-workspace">
                <section id="create-plan" class="rm-panel">
                    <div class="rm-section-heading"><div><p class="rm-eyebrow">MAKE ROOM FOR SOMETHING NEW</p><h2>Create a custom plan</h2><p>Add a new offering directly to your WHMCS catalog.</p></div><span class="rm-heading-icon dashicons dashicons-plus-alt2" aria-hidden="true"></span></div>
                    <form method="post" action="" class="rm-plan-form">
                        <?php wp_nonce_field('rackmonk_add_plan_nonce'); ?>
                        <input type="hidden" name="rackmonk_action" value="add_plan">
                        <div class="rm-form-section"><h3><span>01</span> Plan essentials</h3><div class="rm-form-grid">
                            <div class="rm-field"><label for="rm-gid">Product category <span>*</span></label><select id="rm-gid" name="gid" required><?php foreach ($categories as $cat): ?><option value="<?php echo esc_attr($cat['id']); ?>"><?php echo esc_html($cat['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="rm-field"><label for="rm-name">Plan name <span>*</span></label><input id="rm-name" type="text" name="name" placeholder="e.g. Developer NVMe Hosting" required></div>
                            <div class="rm-field rm-full-width"><label for="rm-tagline">Tagline <span class="rm-optional">Optional</span></label><input id="rm-tagline" type="text" name="tagline" placeholder="A short line that makes your plan stand out"></div>
                        </div></div>
                        <div class="rm-form-section"><h3><span>02</span> Pricing &amp; billing <small><?php echo esc_html($currency['code'] . ' (' . $curr_symbol . ')'); ?></small></h3><p class="rm-field-help">Enter total prices for each duration. Leave longer terms blank to use automatic discounts.</p><div class="rm-pricing-grid">
                            <?php $pricing_fields = [
                                ['monthly', '1 month', '299.00', 'Regular monthly price', 'in_monthly'],
                                ['annually', '12 months', 'Auto-calculated', '40% automatic discount', 'in_annually'],
                                ['biennially', '24 months', 'Auto-calculated', '55% automatic discount', 'in_biennially'],
                                ['triennially', '48 months', 'Auto-calculated', '65% off / Default term', 'in_triennially'],
                            ]; foreach ($pricing_fields as $field): ?>
                                <div class="rm-billing-card <?php echo $field[0] === 'triennially' ? 'is-best' : ''; ?>"><label for="<?php echo esc_attr($field[4]); ?>"><?php echo esc_html($field[1] . ' ' . $currency['code']); ?><?php echo $field[0] === 'monthly' ? ' *' : ''; ?></label><span class="rm-billing-type"><?php echo $field[0] === 'monthly' ? 'Monthly price' : 'Total for the term'; ?></span><input id="<?php echo esc_attr($field[4]); ?>" type="number" min="0" step="0.01" name="<?php echo esc_attr($field[0]); ?>" placeholder="<?php echo esc_attr($field[2]); ?>" <?php echo $field[0] === 'monthly' ? 'required' : ''; ?> aria-describedby="<?php echo esc_attr($field[4] . '-help'); ?>"><small id="<?php echo esc_attr($field[4] . '-help'); ?>"><?php echo esc_html($field[3]); ?></small></div>
                            <?php endforeach; ?>
                        </div></div>
                        <?php if ($usd_currency && $currency['code'] !== 'USD'): ?>
                        <div class="rm-form-section"><h3>USD prices ($) <small>Optional, independent from INR</small></h3><p class="rm-field-help">Enter USD amounts separately. Leave every USD field empty to configure USD later. INR amounts are never copied into these fields.</p><div class="rm-pricing-grid">
                            <?php foreach ($pricing_fields as $field): $usd_id = 'in_usd_' . $field[0]; ?>
                            <div class="rm-billing-card"><label for="<?php echo esc_attr($usd_id); ?>"><?php echo esc_html($field[1]); ?> USD</label><span class="rm-billing-type"><?php echo $field[0] === 'monthly' ? 'USD monthly price' : 'USD total for the term'; ?></span><input id="<?php echo esc_attr($usd_id); ?>" type="number" min="0" max="9999999.99" step="0.01" name="currency_prices[USD][<?php echo esc_attr($field[0]); ?>]" placeholder="<?php echo $field[0] === 'monthly' ? 'Not configured' : 'From USD monthly'; ?>"></div>
                            <?php endforeach; ?>
                        </div></div>
                        <?php endif; ?>
                        <div class="rm-form-section"><h3><span>03</span> The details that sell</h3><div class="rm-field"><label for="rm-description">Plan features</label><textarea id="rm-description" name="description" rows="5" placeholder="100 Websites Hosted&#10;50 GB NVMe Storage&#10;Free SSL Certificate&#10;Daily Backups" aria-describedby="rm-features-help"></textarea><p class="rm-field-help" id="rm-features-help">One feature per line. These appear on your website's pricing cards.</p></div><label class="rm-featured-toggle"><input type="checkbox" name="is_featured" value="1"><span><strong>Make this a popular choice</strong><small>Add a highlighted border and a "Most Popular" badge.</small></span><span class="dashicons dashicons-star-filled" aria-hidden="true"></span></label></div>
                        <input type="hidden" name="create_complete" value="1">
                        <div class="rm-form-footer"><span><span class="dashicons dashicons-database" aria-hidden="true"></span> Saved directly to WHMCS</span><button type="submit" class="rm-button rm-button-primary" <?php disabled(!$pdo); ?>>Create plan <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></button></div>
                    </form>
                </section>

                <aside id="publish-plans" class="rm-publish">
                    <div class="rm-publish-card"><span class="rm-publish-icon dashicons dashicons-editor-code" aria-hidden="true"></span><p class="rm-eyebrow">FROM CATALOG TO WEBSITE</p><h2>Your plans.<br>Your pages.</h2><p>Bring your hosting catalog to any WordPress page with a simple shortcode.</p><label for="rm-shortcode-all">Display all plans</label><div class="rm-code-row"><code id="rm-shortcode-all">[rackmonk_plans]</code><button type="button" data-rm-copy="rm-shortcode-all" aria-label="Copy all plans shortcode"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button></div><label for="rm-shortcode-category">Display one category</label><div class="rm-code-row"><code id="rm-shortcode-category">[rackmonk_plans category="shared"]</code><button type="button" data-rm-copy="rm-shortcode-category" aria-label="Copy category shortcode"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button></div><p class="rm-shortcode-help">Use shared, wordpress, cloud or vps to filter by category.</p><span id="rm-copy-status" role="status" aria-live="polite"></span><div class="rm-publish-note"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>Category tabs and a 48-month default billing selector are included.</span></div></div>
                    <div class="rm-elementor-card"><span class="rm-elementor-mark" aria-hidden="true">E</span><h3>Built for Elementor, too.</h3><p>Search for <strong>Rackmonk Dynamic Pricing</strong> in the Elementor widget panel. Drag it onto your page and you're ready.</p><span>No shortcode needed <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></span></div>
                    <div class="rm-tip"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><p><strong>A little pricing tip</strong>Longer billing terms help customers save more. Your default 48-month option gets the spotlight.</p></div>
                </aside>
            </div>
            <footer class="rm-dashboard-footer"><span>RACKMONK <span>/ WHMCS INTEGRATION</span></span><span>One catalog. Every billing option.</span></footer>
        </div>
        <?php
    }
}

new Rackmonk_Admin_Page();
