<?php
/** Opt-in read-only integration check. Never opens checkout or creates an order. */
if (PHP_SAPI !== 'cli') exit;
if (!in_array('--live-read-only', $argv, true)) {
    echo "Run with --live-read-only to verify the installed catalog without creating orders.\n";
    exit;
}
define('DISABLE_WP_CRON', true);
define('WP_USE_THEMES', false);
require dirname(__DIR__, 4) . '/wp-load.php';
$manager = Rackmonk_Plans_Manager::get_instance();
$db = $manager->get_whmcs_db();
if (!$db) throw new RuntimeException('WHMCS database unavailable');
$db->exec('SET TRANSACTION READ ONLY');
$db->beginTransaction();
try {
    $currency = $manager->get_default_currency();
    $plans = $manager->get_plans();
    $checked = 0;
    foreach ($plans as $plan) {
        $pid = (int) $plan['id'];
        if ($pid < 1 || $pid > 12) continue;
        $cycle = $plan['cycles'][48];
        $stmt = $db->prepare("SELECT p.id, pr.monthly, pr.msetupfee FROM tblproducts p JOIN tblpricing pr ON pr.relid = p.id AND pr.type = 'product' AND pr.currency = ? WHERE p.slug = ? AND p.paytype = 'onetime'");
        $stmt->execute([(int) $currency['id'], 'rackmonk-48-month-onetime-' . $pid]);
        $package = $stmt->fetch();
        if (!$package || !$cycle['available'] || $cycle['total_price'] !== (float) $package['monthly'] || $cycle['setup_fee'] !== (float) $package['msetupfee']) throw new RuntimeException('Native pricing mismatch for source ' . $pid);
        parse_str(parse_url($cycle['order_url'], PHP_URL_QUERY), $query);
        if ((int) ($query['pid'] ?? 0) !== (int) $package['id'] || (int) ($query['currency'] ?? 0) !== (int) $currency['id'] || isset($query['billingcycle'])) throw new RuntimeException('Native URL mismatch for source ' . $pid);
        foreach ([1, 12, 24] as $term) {
            parse_str(parse_url($plan['cycles'][$term]['order_url'], PHP_URL_QUERY), $short);
            if (Rackmonk_Website_Cart::decode_plan_id($short['plan'] ?? '') !== $pid || (int) ($short['term'] ?? 0) !== $term) throw new RuntimeException('Short-term URL mismatch');
        }
        echo "Source $pid -> native " . (int) $package['id'] . ": price, setup, currency and all term URLs match.\n";
        $checked++;
    }
    if ($checked !== 12) throw new RuntimeException('Expected all twelve mapped source plans');
    foreach ($plans as $plan) if ((int) $plan['id'] >= 14 && (int) $plan['id'] <= 25) throw new RuntimeException('One-time package leaked into source catalog');
    echo count($plans) . " public source plans present; the twelve native one-time products remain excluded.\n";
    foreach ([48, 24, 12, 1] as $term) {
        $html = (new Rackmonk_Pricing_Shortcode())->render_shortcode(['default_term' => (string) $term]);
        $doc = new DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $links = (new DOMXPath($doc))->query('//a[contains(@class,"order-link-btn")]');
        if ($links->length !== count($plans)) throw new RuntimeException('Missing shortcode cards');
        foreach ($links as $index => $link) {
            if ($term === 48 && $link->getAttribute('href') !== $plans[$index]['cycles'][$term]['order_url']) throw new RuntimeException('Initial native shortcode URL mismatch');
            if ($term !== 48) {
                parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $short);
                if ((int) ($short['term'] ?? 0) !== $term || Rackmonk_Website_Cart::decode_plan_id($short['plan'] ?? '') !== (int) $plans[$index]['id']) throw new RuntimeException('Initial shorter shortcode URL mismatch');
            }
        }
    }
    $items = $manager->get_elementor_pricing_items();
    foreach ([1, 2, 3, 4] as $style) {
        $settings = ['hide_my' => 'yes', 'm_label' => '48 Months', 'y_label' => '12 Months', 'pricings' => $items];
        foreach ($settings['pricings'] as &$item) $item['lists'] = [];
        unset($item);
        ob_start();
        require dirname(__DIR__, 2) . '/hostinkar-library/classes/widgets/Pricing/pricing-template/pricing-' . $style . '.php';
        $html = ob_get_clean();
        $doc = new DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $links = (new DOMXPath($doc))->query('//div[contains(@class,"pricing__btn")]/a');
        if ($links->length !== count($plans)) throw new RuntimeException('Missing Hostinkar style ' . $style . ' CTAs');
        foreach ($links as $index => $link) if ($link->getAttribute('href') !== $items[$index]['link']['url']) throw new RuntimeException('Hostinkar native URL mismatch');
    }
    $_GET = ['plan' => '1', 'term' => '48'];
    $blocked = (new Rackmonk_Website_Cart())->render();
    if (strpos($blocked, 'base package only') === false || strpos($blocked, 'data-rm-cart-data') !== false) throw new RuntimeException('Direct48 cart not blocked');
    echo "12 mapped packages, 4 initial shortcode terms, 4 Hostinkar styles, direct48 rejection verified. No orders/payments/package writes.\nVERDICT: PASS\n";
} finally {
    $db->rollBack();
}
