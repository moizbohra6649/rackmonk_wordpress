<?php
/** Dependency-free checkout boundary tests. No live orders, sessions or payments. */
if (PHP_SAPI !== 'cli') exit;
define('RACKMONK_CART_BOUNDARY_ONLY', true);
require __DIR__ . '/website-cart-test.php';
require dirname(__DIR__) . '/includes/class-website-checkout.php';

function get_woocommerce_currency() { global $store_currency; return $store_currency; }
function WC() { global $commerce; return $commerce; }
function wc_add_notice($message, $type) { global $notices; $notices[] = $message; }
function wc_has_notice($message, $type) { global $notices; return in_array($message, $notices, true); }
class CheckoutProduct {
    public $price = 0;
    public function set_price($value) { $this->price = $value; }
    public function get_meta($key) { return 1; }
}
class CheckoutCart {
    public $cart_contents = [];
    public function get_cart() { return $this->cart_contents; }
}
class CheckoutLine {
    public $meta = [];
    public function add_meta_data($key, $value, $unique = false) { $this->meta[$key] = $value; }
}

$store_currency = 'INR';
$notices = [];
$config = Rackmonk_Website_Cart::validate_config([
    'enabled' => 1, 'base_price' => '100.00', 'currency' => 'INR', 'symbol' => 'Rs',
    'resources' => ['ram' => [['id' => '0', 'label' => '4 GB', 'price' => '20.00']]],
], [], ['id' => 91, 'name' => 'Checkout test']);
update_option(Rackmonk_Website_Cart::OPTION, [91 => $config]);
$native = Rackmonk_Website_Cart::runtime_config(91);
$item = ['pid' => 91, 'months' => 12, 'choices' => ['option_9' => '30'], 'revision' => Rackmonk_Website_Cart::revision($native), 'total' => 0.01];
$quote = Rackmonk_Website_Checkout::price_item($item);
check($quote['total'] === 1063 && $quote['recurring'] === 1050 && $quote['setup'] === 13, 'Ignore client totals; use native annual prices and setup');
rejects(function () { Rackmonk_Website_Checkout::price_item(['pid' => '91']); }, 'Reject malformed plan IDs');
$store_currency = 'USD';
rejects(function () use ($item) { Rackmonk_Website_Checkout::price_item($item); }, 'Reject mixed store currency');
$store_currency = 'INR';
rejects(function () use ($item) { $item['revision'] = 'stale'; Rackmonk_Website_Checkout::price_item($item); }, 'Reject stale configuration');
rejects(function () use ($item) { $item['choices']['option_9'] = 'removed'; Rackmonk_Website_Checkout::price_item($item); }, 'Reject removed choices');
check(Rackmonk_Website_Checkout::price_item(array_merge($item, ['months' => 48]))['total'] > 0, 'Existing 48-month checkout uses server prices');
$native_db->prices[30]['annually'] = '151.00';
rejects(function () use ($item) { Rackmonk_Website_Checkout::price_item($item); }, 'Reject quote after native option price changes');
$native_db->prices[30]['annually'] = '150.00';

$bridge = new Rackmonk_Website_Checkout();
$product = new CheckoutProduct();
$second = $item;
$second['months'] = 1;
$cart = new CheckoutCart();
$cart->cart_contents = [
    'annual' => ['rackmonk_configuration' => $item, 'data' => $product],
    'monthly' => ['rackmonk_configuration' => $second, 'data' => $product],
];
$bridge->refresh_prices($cart);
check($cart->cart_contents['annual']['data']->price === 1063, 'Annual configuration has correct total including setup');
check($cart->cart_contents['monthly']['data']->price === 127, 'Same plan monthly line has separate price including setup');
check($product->price === 0, 'Shared product object is not mutated');
$commerce = (object) ['cart' => $cart];
$bridge->validate_cart();
check(!$notices, 'Valid configurations can proceed');
$cart->cart_contents['monthly']['rackmonk_configuration']['revision'] = 'old';
$bridge->validate_cart();
check(count($notices) === 1, 'Stale checkout line blocks order');
$bridge->validate_cart();
check(count($notices) === 1, 'Validation does not duplicate notices');
$line = new CheckoutLine();
$bridge->order_item($line, 'annual', $cart->cart_contents['annual'], null);
check($line->meta['Billing duration'] === '12 month(s)', 'Order stores billing duration');
check($line->meta['Memory'] === '4 GB', 'Order stores resource choice');
check($line->meta['_rackmonk_configuration']['total'] === 1063, 'Order retains server-validated configuration snapshot');
$commerce->cart->cart_contents = [['data' => $product]];
$bridge->validate_cart();
check(count($notices) === 2, 'Direct purchase without configuration is blocked');
$legacy = $item;
$legacy['months'] = 48;
$legacy['revision'] = 'stale-legacy-revision';
$commerce->cart->cart_contents = [['rackmonk_configuration' => $legacy, 'data' => $product]];
$bridge->validate_cart();
check(count($notices) === 2 && str_contains($notices[0], 'needs review'), 'Saved stale 48-month cart line requires review without duplicate notices');
rejects(function () use ($bridge, $legacy, $product) { $bridge->order_item(new CheckoutLine(), 'legacy', ['rackmonk_configuration' => $legacy, 'data' => $product], null); }, 'Reject legacy 48-month order-line creation');
$key = str_repeat('a', 32);
$commerce->cart->cart_contents = [$key => ['rackmonk_configuration' => $quote, 'quantity' => 3]];
$editing = Rackmonk_Website_Checkout::editing_item($key);
check($editing['quantity'] === 3 && $editing['choices'] === $quote['choices'], 'Session edit restores owned item quantity and choices');
check(strlen($editing['token']) === 64, 'Session edit has a concurrency token');
$commerce->cart->cart_contents[$key]['quantity'] = 4;
check(Rackmonk_Website_Checkout::editing_item($key)['token'] !== $editing['token'], 'Quantity changes invalidate stale edit token');
rejects(function () { Rackmonk_Website_Checkout::editing_item(str_repeat('b', 32)); }, 'Reject cart keys not owned by session');
rejects(function () { Rackmonk_Website_Checkout::editing_item(['invalid']); }, 'Reject malformed edit key');
echo "Checkout boundary assertions passed; {$checks} total assertions.\nVERDICT: PASS\n";
