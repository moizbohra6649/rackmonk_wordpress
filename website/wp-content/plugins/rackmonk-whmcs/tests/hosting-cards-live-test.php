<?php
/** Run with the local PHP CLI against the installed WordPress/WHMCS catalog. */
require dirname(__DIR__, 4) . '/wp-load.php';

function card_check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}

try {
    $controller = new Rackmonk_Hosting_Cards();
    $categories = $controller->categories();
    card_check((bool) Rackmonk_Plans_Manager::get_instance()->get_whmcs_db(), 'Live WHMCS connection available');
    echo 'Live categories: ' . wp_json_encode($categories) . "\n";
    card_check(count($categories) > 0, 'Live catalog contains eligible hosting categories');
    $empty = function () { return []; };
    add_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $empty);
    $cards = $controller->cards();
    card_check(count($cards) === count($categories), 'Exactly one card per active category');
    card_check(count(array_unique(array_column($cards, 'id'))) === count($cards), 'No duplicate category cards');
    $expected_slugs = ['Shared Hosting' => 'shared-hosting-2', 'Cloud Hosting' => 'cloud-hosting', 'VPS Hosting' => 'vps-hosting', 'WordPress Hosting' => 'wp-hosting'];
    foreach ($cards as $card) {
        card_check($controller->website_url($card['url']), 'Default link stays on website for ' . $card['title']);
        if (isset($expected_slugs[$card['title']])) {
            $page = get_page_by_path($expected_slugs[$card['title']]);
            card_check($page && $card['url'] === get_permalink($page), 'Correct published page for ' . $card['title']);
        }
        card_check(strpos($card['description'], 'plans available.') === false, 'Category-specific copy for ' . $card['title']);
        echo $card['title'] . ': ' . $card['url'] . "\n";
    }
    card_check(count(array_unique(array_column($cards, 'description'))) === count($cards), 'Each category has distinct description');
    card_check(!$controller->website_url('https://example.com/hosting/'), 'External destination rejected');
    card_check(!$controller->website_url(Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url() . 'cart.php'), 'WHMCS destination rejected');
    card_check(!$controller->website_url(home_url('/../whmcs/cart.php')), 'Path traversal destination rejected');
    remove_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $empty);
    if ($categories) {
        $id = (int) $categories[0]['id'];
        $override = function () use ($id) { return [$id => ['title' => '<script>test</script>', 'description' => 'Custom description', 'badge' => '', 'order' => -10]]; };
        add_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $override);
        $widget = new class {
            public $source = 'catalog';
            public function get_name() { return 'xhosting-service'; }
            public function get_settings_for_display() { return ['style' => '1', 'rackmonk_card_source' => $this->source]; }
        };
        $html = $controller->render('original', $widget);
        card_check(strpos($html, '&lt;script&gt;test&lt;/script&gt;') !== false && strpos($html, '<script>test</script>') === false, 'Card output escapes custom titles');
        card_check(substr_count($html, 'class="swiper-slide"') === count($categories), 'Renderer outputs actual category count');
        $widget->source = 'manual';
        card_check($controller->render('original', $widget) === 'original', 'Manual Elementor source preserved');
        remove_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $override);
        $old_link = function () use ($id) { return [$id => ['url' => Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url() . 'cart.php']]; };
        add_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $old_link);
        foreach ($controller->cards() as $card) card_check($controller->website_url($card['url']), 'Saved WHMCS links cannot override website destinations');
        remove_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $old_link);
        $hide = function () use ($id) { return [$id => ['hidden' => 1]]; };
        add_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $hide);
        card_check(count($controller->cards()) === count($categories) - 1, 'Hidden card excluded');
        remove_filter('pre_option_' . Rackmonk_Hosting_Cards::OPTION, $hide);
    }
    if (class_exists('\\Elementor\\Plugin')) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        echo "Elementor cache cleared.\n";
    }
    echo 'Homepage: ' . home_url('/') . "\n";
    echo "VERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n");
    exit(1);
}
