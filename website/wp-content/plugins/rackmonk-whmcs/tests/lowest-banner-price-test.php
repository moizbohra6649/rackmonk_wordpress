<?php
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
function get_option($key, $default = false) { return [1 => ['enabled' => true], 2 => ['enabled' => true], 3 => ['enabled' => false]]; }
class Rackmonk_Website_Cart {
    const OPTION = 'rackmonk_website_cart_plans';
    public static $plans = [];
    public static function runtime_config($pid) { return self::$plans[$pid] ?? null; }
}
require dirname(__DIR__) . '/includes/class-plans-manager.php';
function verify_banner($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function amount($recurring, $setup = '0.00') { return ['recurring' => $recurring, 'setup' => $setup]; }
Rackmonk_Website_Cart::$plans = [
    1 => ['currency' => 'INR', 'prices' => [1 => amount('250'), 48 => amount('1200', '50')], 'resources' => [
        ['choices' => [['prices' => [1 => amount('10'), 48 => amount('240', '20')]], ['prices' => [1 => amount('5'), 48 => amount('120', '30')]]]],
    ]],
    2 => ['currency' => 'USD', 'prices' => [1 => amount('1')], 'resources' => []],
];
$manager = new Rackmonk_Plans_Manager();
$rate = $manager->get_lowest_inr_cart_rate();
verify_banner($rate === ['cents' => 132000, 'months' => 48, 'setup' => 8000, 'monthly' => 25500], 'Use configured 48-month INR totals and required options, not USD or disabled plans');
Rackmonk_Website_Cart::$plans[1]['resources'][0]['choices'][1]['prices'][48] = null;
$rate = $manager->get_lowest_inr_cart_rate();
verify_banner($rate['cents'] === 144000 && $rate['setup'] === 7000, 'Skip disabled option terms and include setup');
Rackmonk_Website_Cart::$plans[1]['resources'][0]['choices'][0]['prices'][48] = null;
$rate = $manager->get_lowest_inr_cart_rate();
verify_banner($rate === null, 'Do not advertise a 48-month term without all required options');
Rackmonk_Website_Cart::$plans[1]['currency'] = 'USD';
verify_banner($manager->get_lowest_inr_cart_rate() === null, 'Do not invent an INR fallback');
echo "Lowest banner price assertions passed.\nVERDICT: PASS\n";
