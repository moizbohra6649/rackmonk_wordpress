<?php
/** Isolated currency/cart/checkout tests. No WordPress bootstrap, network, database writes or orders. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function current_user_can($cap) { return false; }
function is_user_logged_in() { return $GLOBALS['logged_in'] ?? false; }
function get_current_user_id() { return 1; }
function get_user_meta($id, $key, $single) { return $key === '_rackmonk_whmcs_currency_id' ? ($GLOBALS['account_id'] ?? 0) : 0; }
function apply_filters($name, $value) { return $value; }
function get_option($name, $default = false) { return $name === Rackmonk_Website_Cart::OPTION ? [1 => $GLOBALS['settings']] : $default; }
function get_woocommerce_currency() { return (new Rackmonk_Website_Checkout())->cart_currency('INR'); }
function WC() { return $GLOBALS['wc']; }
function wc_add_notice($message, $type) { $GLOBALS['notices'][] = $message; }
function wc_has_notice($message, $type) { return in_array($message, $GLOBALS['notices'], true); }
function is_wc_endpoint_url($endpoint) { return ($GLOBALS['endpoint'] ?? '') === $endpoint; }
class Rackmonk_Plans_Manager {
    static function get_instance() { return new self(); }
    function get_whmcs_db() { return $GLOBALS['db']; }
}
require dirname(__DIR__) . '/includes/class-currency.php';
require dirname(__DIR__) . '/includes/class-website-cart.php';
require dirname(__DIR__) . '/includes/class-website-checkout.php';
class CurrencyStatement {
    private $sql, $args;
    function __construct($sql) { $this->sql = $sql; }
    function execute($args) { $this->args = $args; }
    function fetchAll($mode) {
        $db = $GLOBALS['db'];
        if (strpos($this->sql, 'tblcurrencies') !== false) return [$db->currencies[0]];
        if (strpos($this->sql, 'tblproducts') !== false) return [['id' => 1, 'name' => 'Plan', 'paytype' => 'recurring']];
        if (strpos($this->sql, 'tblproductconfiglinks') !== false) return [['id' => 7, 'optiontype' => 1, 'optionname' => 'Memory']];
        if (strpos($this->sql, 'tblproductconfigoptionssub') !== false) return [['id' => 10, 'optionname' => '8 GB']];
        $type = strpos($this->sql, "type = 'product'") !== false ? 'product' : 'option';
        return $db->prices[$type][$this->args[1]] ?? [];
    }
}
class CurrencyDB {
    public $currencies, $prices;
    function prepare($sql) { return new CurrencyStatement($sql); }
}
class CurrencyCart {
    public $cart_contents = [];
    function get_cart() { return $this->cart_contents; }
}
class CurrencyProduct {
    public $price = 0;
    function set_price($price) { $this->price = $price; }
    function get_meta($name) { return false; }
}
class CurrencyOrder {
    public $currency = 'INR';
    function set_currency($code) { $this->currency = $code; }
    function get_currency() { return $this->currency; }
}
class CurrencyLine {
    public $meta = [];
    function add_meta_data($key, $value, $unique = false) { $this->meta[$key] = $value; }
}
$checks = 0;
function expect($ok, $label) { $GLOBALS['checks']++; if (!$ok) throw new RuntimeException($label); }
function blocked($fn, $label) { try { $fn(); } catch (RuntimeException | InvalidArgumentException $e) { expect(true, $label); return; } expect(false, $label); }
function select_country($country) {
    $_SERVER['GEOIP_COUNTRY_CODE'] = $country;
    (new ReflectionProperty(Rackmonk_Currency::class, 'current'))->setValue(null, null);
}
function currency_cache($currencies) {
    (new ReflectionProperty(Rackmonk_Currency::class, 'currencies'))->setValue(null, array_column($currencies, null, 'code'));
    select_country($_SERVER['GEOIP_COUNTRY_CODE'] ?? 'IN');
}
function amounts($monthly, $setup) {
    return ['monthly' => $monthly, 'msetupfee' => $setup, 'annually' => '900.00', 'asetupfee' => '20.00', 'biennially' => '-1.00', 'triennially' => '-1.00'];
}
$db = new CurrencyDB();
$db->currencies = [
    ['id' => 1, 'code' => 'INR', 'prefix' => 'Rs.', 'suffix' => '', 'rate' => 1.0, 'default' => true],
    ['id' => 2, 'code' => 'USD', 'prefix' => '$', 'suffix' => '', 'rate' => 0.01, 'default' => false],
];
currency_cache($db->currencies);
$db->prices = ['product' => [1 => [amounts('100.00', '10.00')]], 'option' => [1 => [amounts('20.00', '5.00')]]];
$settings = ['enabled' => true, 'manual_48' => ['currency' => 'INR', 'base' => ['recurring' => '4000.00', 'setup' => '100.00'], 'choices' => [7 => [10 => ['recurring' => '600.00', 'setup' => '50.00']]]]];
$wc = (object) ['cart' => new CurrencyCart()];
$notices = [];
$bridge = new Rackmonk_Website_Checkout();
select_country('IN');
$inr = Rackmonk_Website_Cart::runtime_config(1);
expect($inr['currency'] === 'INR' && $inr['prices'][1]['recurring'] === '100.00', 'India uses real INR amounts');
select_country('US');
$usd = Rackmonk_Website_Cart::runtime_config(1);
expect($usd['currency'] === 'USD' && $usd['prices'][1] === ['recurring' => '1.00', 'setup' => '0.10'], 'Foreign visitor gets converted base and setup');
expect($usd['resources']['option_7']['choices'][0]['prices'][1] === ['recurring' => '0.20', 'setup' => '0.05'], 'Required option and setup converted');
expect($usd['prices'][48] === ['recurring' => '40.00', 'setup' => '1.00'], 'Manual base and setup converted');
expect($usd['resources']['option_7']['choices'][0]['prices'][48] === ['recurring' => '6.00', 'setup' => '0.50'], 'Manual option and setup converted');
expect(Rackmonk_Native_Cart_Pricing::config($db, 1, $settings)['currency'] === 'INR', 'Direct config remains default for editors');
expect(Rackmonk_Website_Cart::revision($inr) !== Rackmonk_Website_Cart::revision($usd), 'Shared catalog revision includes currency');
$item = ['pid' => 1, 'months' => 1, 'choices' => ['option_7' => '10'], 'revision' => Rackmonk_Website_Cart::revision($usd), 'total' => 999];
$quote = Rackmonk_Website_Checkout::price_item($item);
expect($quote['total'] === 1.35 && $quote['currency'] === 'USD', 'Checkout uses converted amounts not client total or store default');
$manualItem = $item; $manualItem['months'] = 48;
expect(Rackmonk_Website_Checkout::price_item($manualItem)['total'] === 47.5, '48-month checkout includes converted fees');
$product = new CurrencyProduct();
$wc->cart->cart_contents = ['hosting' => ['rackmonk_configuration' => $quote, 'data' => $product]];
expect($bridge->cart_currency('INR') === 'USD', 'Hosting-only cart currency is USD');
$bridge->refresh_prices($wc->cart);
expect($wc->cart->cart_contents['hosting']['data']->price === 1.35 && $product->price === 0, 'Cart price updated without shared product mutation');
$order = new CurrencyOrder();
$bridge->order_currency($order, []);
expect($order->currency === 'USD', 'New mock order receives reviewed currency');
$line = new CurrencyLine();
$bridge->order_item($line, 'hosting', $wc->cart->cart_contents['hosting'], $order);
expect($line->meta['_rackmonk_configuration']['total'] === 1.35, 'Order line retains matching actual amount');
blocked(function () use ($bridge, $wc) { $bridge->order_item(new CurrencyLine(), 'hosting', $wc->cart->cart_contents['hosting'], new CurrencyOrder()); }, 'Mismatched order currency fails closed');
$endpoint = 'order-pay';
expect($bridge->cart_currency('INR') === 'INR', 'Pay-existing-order does not inherit current cart currency');
$endpoint = '';
$wc->cart->cart_contents['ordinary'] = ['data' => $product];
expect($bridge->cart_currency('INR') === 'INR', 'Mixed cart never relabels ordinary products');
$bridge->validate_cart();
expect(count($notices) === 1, 'Mixed cart blocks checkout');
blocked(function () use ($bridge) { $bridge->order_currency(new CurrencyOrder(), []); }, 'Mixed order creation blocked');
expect(!$bridge->validate_addition(true, 2, 1), 'Ordinary product cannot be added to hosting cart');
$wc->cart->cart_contents = ['ordinary' => ['data' => $product]];
expect(!$bridge->validate_addition(true, 1, 1, 0, [], ['rackmonk_configuration' => $quote]), 'Hosting cannot be added to ordinary cart');
expect($bridge->cart_currency('INR') === 'INR', 'Ordinary cart unchanged');
$ordinaryOrder = new CurrencyOrder(); $ordinaryOrder->currency = 'EUR';
$bridge->order_currency($ordinaryOrder, []);
expect($ordinaryOrder->currency === 'EUR', 'Nonhosting order currency is never changed');
$wc->cart->cart_contents = ['hosting' => ['rackmonk_configuration' => $quote, 'data' => $product]];
select_country('IN');
blocked(function () use ($item) { Rackmonk_Website_Checkout::price_item($item); }, 'Location/currency change requires quote review');
expect($bridge->cart_currency('INR') === 'USD', 'Stale saved amounts keep original USD label until review');
$bridge->refresh_prices($wc->cart);
expect($wc->cart->cart_contents['hosting']['rackmonk_configuration'] === $quote, 'Stale price not silently replaced');
expect($wc->cart->cart_contents['hosting']['data']->price === $quote['total'], 'Stale session line displays its saved amount, not shared product price');
blocked(function () use ($bridge) { $bridge->order_currency(new CurrencyOrder(), []); }, 'Currency change blocks new order');
$logged_in = true; $account_id = 2;
select_country('IN');
expect(Rackmonk_Website_Cart::runtime_config(1)['currency'] === 'USD', 'Logged account USD wins over India');
$account_id = 1; select_country('US');
expect(Rackmonk_Website_Cart::runtime_config(1)['currency'] === 'INR', 'Logged account INR wins overseas');
$logged_in = false; select_country('US');
$db->prices['product'][2] = [amounts('7.00', '2.00')];
$own = Rackmonk_Website_Cart::runtime_config(1);
expect($own['prices'][1]['recurring'] === '7.00' && $own['prices'][1]['setup'] === '2.00', 'Own product currency prices preferred');
expect($own['resources']['option_7']['choices'][0]['price'] === '0.20', 'Missing own option row still converts default');
$db->prices['option'][2] = [amounts('0.00', '0.00')];
$own = Rackmonk_Website_Cart::runtime_config(1);
expect($own['resources']['option_7']['choices'][0]['price'] === '0.00', 'Explicit free own option preserved');
$db->prices['product'][2][0]['quadrennially'] = '99.00';
$db->prices['product'][2][0]['quadsetupfee'] = '3.00';
expect(Rackmonk_Website_Cart::runtime_config(1)['prices'][48]['recurring'] === '99.00', 'Native foreign 48-month price preferred over manual conversion');
$db->prices['product'][2][0]['quadrennially'] = '-1.00';
expect(Rackmonk_Website_Cart::runtime_config(1)['prices'][48] === null, 'Explicit foreign disabled48 is not re-enabled by manual or inferred prices');
$db->prices['product'][2][0]['quadrennially'] = '99.00';
$before = Rackmonk_Website_Cart::revision(Rackmonk_Website_Cart::runtime_config(1));
$db->currencies[1]['rate'] = 0.02; currency_cache($db->currencies);
expect(Rackmonk_Website_Cart::revision(Rackmonk_Website_Cart::runtime_config(1)) !== $before, 'Rate changes invalidate shared revision even with own rows');
$db->prices['product'][2][0]['monthly'] = '-1.00';
expect(Rackmonk_Website_Cart::runtime_config(1)['prices'][1] === null, 'Explicit disabled native term is not converted or fabricated');
$db->prices['option'][2] = []; $db->prices['option'][1] = [];
blocked(function () { Rackmonk_Website_Cart::runtime_config(1); }, 'Missing required option pricing fails closed');
$db->prices['option'][1] = [amounts('20.00', '5.00')];
$db->prices['product'][2][] = $db->prices['product'][2][0];
blocked(function () { Rackmonk_Website_Cart::runtime_config(1); }, 'Duplicate own currency rows fail closed');
echo "$checks currency/cart/checkout assertions passed. No external mutations.\nVERDICT: PASS\n";
