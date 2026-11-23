<?php
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
function add_shortcode(...$args) {}
function add_action(...$args) {}
function add_filter(...$args) {}
function get_option($key, $default = false) { global $settings; return $key === 'rackmonk_website_cart_plans' ? [1 => $settings ?? ['enabled' => true]] : $default; }
function get_woocommerce_currency() { return 'INR'; }
class Rackmonk_Plans_Manager {
    public static $db;
    public static function get_instance() { return new self(); }
    public function get_whmcs_db() { return self::$db; }
}
require dirname(__DIR__) . '/includes/class-website-cart.php';
require dirname(__DIR__) . '/includes/class-website-checkout.php';
class NativeStatement {
    private $db; private $sql; private $args;
    function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    function execute($args) { $this->args = $args; }
    function fetchAll($mode) {
        $db = $this->db; $sql = $this->sql;
        if (strpos($sql, 'tblcurrencies') !== false) return $db->currencies;
        if (strpos($sql, 'tblproducts') !== false) return $db->products;
        if (strpos($sql, 'tblproductconfiglinks') !== false) return $db->options;
        if (strpos($sql, 'tblproductconfigoptionssub') !== false) return $db->choices;
        if (strpos($sql, "type = 'product'") !== false) return $db->base;
        if (strpos($sql, "type = 'configoptions'") !== false) return $db->prices[$this->args[0]] ?? [];
        throw new RuntimeException('Unexpected query');
    }
}
class NativeDB {
    public $currencies = [['id' => 1, 'code' => 'INR', 'prefix' => 'Rs.']];
    public $products = [['id' => 1, 'name' => 'Plan', 'paytype' => 'recurring', 'hidden' => 0]];
    public $options = [['id' => 7, 'optiontype' => 1, 'hidden' => 0, 'optionname' => 'Memory']];
    public $choices = [['id' => 10, 'optionname' => '8GB', 'hidden' => 0]];
    public $base; public $prices;
    function __construct() {
        $this->base = [['monthly' => '100', 'msetupfee' => '10', 'annually' => '900', 'asetupfee' => '20', 'biennially' => '-1.00', 'triennially' => '2000', 'tsetupfee' => '0']];
        $this->prices = [10 => [['monthly' => '1899', 'msetupfee' => '5', 'annually' => '10788', 'asetupfee' => '7', 'biennially' => '-1.00', 'triennially' => '30000', 'tsetupfee' => '0']]];
    }
    function prepare($sql) { return new NativeStatement($this, $sql); }
}
function verify($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function reject($fn) { try { $fn(); } catch (RuntimeException | InvalidArgumentException $e) { return; } throw new RuntimeException('Expected rejection'); }
$db = new NativeDB();
Rackmonk_Plans_Manager::$db = $db;
$config = Rackmonk_Native_Cart_Pricing::config($db, 1, []);
$revision = Rackmonk_Website_Cart::revision($config);
verify(Rackmonk_Website_Cart::price_config($config, 48, ['option_7' => '10'], $revision)['total'] > 0, 'Existing derived 48-month amounts remain available');
$settings = ['enabled' => true, 'manual_48' => ['currency' => 'INR', 'base' => ['recurring' => '4000.00', 'setup' => '11.00'], 'choices' => [7 => [10 => ['recurring' => '600.00', 'setup' => '2.00']]]]];
$manual = Rackmonk_Website_Cart::runtime_config(1);
$manual_revision = Rackmonk_Website_Cart::revision($manual);
$manual_item = ['pid' => 1, 'months' => 48, 'choices' => ['option_7' => '10'], 'revision' => $manual_revision, 'total' => 1];
verify($manual['prices'][48]['recurring'] === '4000.00' && $manual['prices'][48]['setup'] === '11.00', 'Keep manual settings intact for administrators');
verify(Rackmonk_Website_Checkout::price_item($manual_item)['total'] === 4613, 'Manual checkout includes required option and setup fees');
$settings['manual_48']['choices'][7][10]['recurring'] = '601.00';
reject(function () use ($manual_item) { Rackmonk_Website_Checkout::price_item($manual_item); });
$settings['manual_48']['choices'][7][10] = null;
$missing = Rackmonk_Website_Cart::runtime_config(1);
verify(Rackmonk_Website_Cart::price_config($missing, 48, ['option_7' => '10'], Rackmonk_Website_Cart::revision($missing))['total'] > 4000, 'Missing manual option retains existing native-derived pricing');
$settings['manual_48']['currency'] = 'USD';
$wrong_currency = Rackmonk_Website_Cart::runtime_config(1);
verify($wrong_currency['prices'][48]['recurring'] !== '4000.00', 'Wrong-currency manual amount is not used');
$settings = ['enabled' => true];
$quote = Rackmonk_Website_Cart::price_config($config, 12, ['option_7' => '10'], $revision);
verify($quote['recurring'] === 11688 && $quote['setup'] === 27 && $quote['total'] === 11715, 'Native annual amounts and setup');
$item = ['pid' => 1, 'months' => 12, 'choices' => ['option_7' => '10'], 'revision' => $revision, 'total' => 1];
verify(Rackmonk_Website_Checkout::price_item($item)['total'] === 11715, 'Checkout ignores client totals and uses native prices');
reject(function () use ($config, $revision) { Rackmonk_Website_Cart::price_config($config, 24, ['option_7' => '10'], $revision); });
reject(function () use ($config, $revision) { Rackmonk_Website_Cart::price_config($config, 12, ['option_7' => '11'], $revision); });
$db->prices[10][0]['annually'] = '10000';
$changed = Rackmonk_Native_Cart_Pricing::config($db, 1, []);
reject(function () use ($changed, $revision) { Rackmonk_Website_Cart::price_config($changed, 12, ['option_7' => '10'], $revision); });
reject(function () use ($item) { Rackmonk_Website_Checkout::price_item($item); });
$db->prices[10][] = $db->prices[10][0];
reject(function () use ($db) { Rackmonk_Native_Cart_Pricing::config($db, 1, []); });
$db->prices[10] = [];
reject(function () use ($db) { Rackmonk_Native_Cart_Pricing::config($db, 1, []); });
$db->options[0]['optiontype'] = 4;
reject(function () use ($db) { Rackmonk_Native_Cart_Pricing::config($db, 1, []); });
echo "Native pricing assertions passed (annual totals, setup, stale revision and fail-closed cases).\nVERDICT: PASS\n";
