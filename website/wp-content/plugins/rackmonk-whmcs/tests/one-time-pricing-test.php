<?php
/** In-memory native mapping tests: no WordPress bootstrap or live writes. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
function add_query_arg($key, $value, $url) { return $url . (strpos($url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode((string) $value); }
function add_shortcode($tag, $callback) {}
function shortcode_atts($defaults, $atts, $tag) { return array_merge($defaults, $atts); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
class Rackmonk_Website_Cart {
    public static function get_cart_url($pid) { return 'https://website.test/cart/?plan=encrypted-' . $pid; }
    public static function enabled($pid) { return true; }
}
require dirname(__DIR__) . '/includes/class-plans-manager.php';
require dirname(__DIR__) . '/includes/class-shortcode.php';
class MappingManager extends Rackmonk_Plans_Manager {
    public $db;
    public function get_whmcs_db() { return $this->db; }
    public function get_default_currency() { return ['id' => 2, 'prefix' => '$']; }
    public function get_whmcs_system_url() { return 'https://billing.test/'; }
    public function get_categories() { return [['id' => 1, 'name' => 'Hosting', 'slug' => 'hosting']]; }
}
$manager = new MappingManager();
$db = $manager->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE tblproducts (id INTEGER, slug TEXT, paytype TEXT, hidden INTEGER, retired INTEGER, gid INTEGER, name TEXT, "order" INTEGER)');
$db->exec('CREATE TABLE tblpricing (relid INTEGER, type TEXT, currency INTEGER, monthly NUMERIC, msetupfee NUMERIC, quarterly NUMERIC, semiannually NUMERIC, annually NUMERIC, biennially NUMERIC, triennially NUMERIC)');
$db->exec('CREATE TABLE tblproductgroups (id INTEGER, name TEXT, slug TEXT, "order" INTEGER)');
$db->exec('CREATE TABLE tblcurrencies (id INTEGER, code TEXT, prefix TEXT, suffix TEXT)');
$db->exec("INSERT INTO tblcurrencies VALUES (2, 'USD', '$', '')");
$db->exec("INSERT INTO tblproductgroups VALUES (1, 'Hosting', 'hosting', 0)");
$db->exec("INSERT INTO tblproducts VALUES (1, 'source', 'recurring', 0, 0, 1, 'Source', 0), (14, 'rackmonk-48-month-onetime-1', 'onetime', 0, 0, 1, 'Native 48', 0), (15, 'rackmonk-48-month-onetime-10', 'onetime', 0, 0, 1, 'Wrong suffix', 0)");
$db->exec("INSERT INTO tblpricing VALUES (1, 'product', 2, 100, 0, -1, -1, 900, 1600, 1), (14, 'product', 1, 99999, 999, -1, -1, -1, -1, -1), (14, 'product', 2, 2400, 35.5, -1, -1, -1, -1, -1), (15, 'product', 2, 50, 0, -1, -1, -1, -1, -1)");
$checks = 0;
function verify($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
$plans = $manager->get_plans();
verify(count($plans) === 1, 'One-time products remain excluded from source catalog');
$plan = $plans[0]; $cycle = $plan['cycles'][48];
verify($cycle['total_price'] === 2400.0 && $cycle['price_per_month'] === 50.0 && $cycle['setup_fee'] === 35.5, 'Use mapped monthly/setup in default currency, never triennial or foreign currency');
verify($cycle['whmcs_cycle'] === 'onetime' && $cycle['order_url'] === 'https://billing.test/cart.php?a=add&pid=14&currency=2', 'Use actual mapped native product ID and default currency');
verify($plan['order_url'] === $cycle['order_url'] && strpos($cycle['billing_note'], 'no paid resource upgrades') !== false, 'Default order URL and note identify base-only native package');
foreach ([1, 12, 24] as $term) verify($plan['cycles'][$term]['order_url'] === 'https://website.test/cart/?plan=encrypted-1&term=' . $term, 'Short terms retain exact website-cart duration');
$singleton = new ReflectionProperty(Rackmonk_Plans_Manager::class, 'instance'); $singleton->setValue(null, $manager);
foreach ([48, 24, 12, 1] as $term) {
    $html = (new Rackmonk_Pricing_Shortcode())->render_shortcode(['default_term' => (string) $term]);
    verify(strpos($html, 'href="' . esc_attr($plan['cycles'][$term]['order_url']) . '"') !== false, 'Initial shortcode CTA matches selected term ' . $term);
}
$item = $manager->get_elementor_pricing_items()[0];
verify($item['link']['url'] === $cycle['order_url'] && $item['rackmonk_cycles'][12]['order_url'] === $plan['cycles'][12]['order_url'], 'Hostinkar adapter carries both native default and shorter URLs');
verify($manager->get_48_month_package(100) === null, 'Exact slug lookup does not prefix-match another source');
$db->exec('UPDATE tblproducts SET hidden = 1 WHERE id = 14');
verify($manager->get_48_month_package(1) === null, 'Hidden mapped product is unavailable');
$db->exec("UPDATE tblproducts SET hidden = 0, paytype = 'recurring' WHERE id = 14");
verify($manager->get_48_month_package(1) === null, 'Recurring product cannot masquerade as one-time');
$db->exec("UPDATE tblproducts SET paytype = 'onetime' WHERE id = 14");
$db->exec('UPDATE tblpricing SET monthly = -1 WHERE relid = 14 AND currency = 2');
$missing = $manager->get_plans()[0]['cycles'][48];
verify(!$missing['available'] && $missing['order_url'] === '' && $missing['price_per_month'] === 0.0, 'Disabled mapped price fails closed without recurring/synthetic fallback');
$db->exec('UPDATE tblpricing SET monthly = 0, msetupfee = 0 WHERE relid = 14 AND currency = 2');
verify($manager->get_plans()[0]['cycles'][48]['available'], 'Legitimate free one-time package stays available');
echo "$checks one-time mapping, pricing and initial URL assertions passed. In-memory database only.\nVERDICT: PASS\n";
