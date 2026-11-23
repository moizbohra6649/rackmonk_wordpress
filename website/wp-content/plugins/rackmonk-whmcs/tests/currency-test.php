<?php
/** Deterministic currency selection tests. No WordPress bootstrap or live database writes. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
define('RACKMONK_WHMCS_DIR', dirname(__DIR__) . '/');
$user = 0;
$meta = [];
$admin = false;
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function current_user_can($capability) { return $GLOBALS['admin']; }
function is_user_logged_in() { return $GLOBALS['user'] > 0; }
function get_current_user_id() { return $GLOBALS['user']; }
function get_user_meta($id, $key, $single) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function apply_filters($name, $value) { return $value; }
function sanitize_text_field($value) { return $value; }
function wp_unslash($value) { return $value; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function sanitize_title($value) { return strtolower(str_replace(' ', '-', $value)); }
class Rackmonk_Plans_Manager {
    public static $db;
    public static function get_instance() { return new self(); }
    public function get_whmcs_db() { return self::$db; }
    public function get_categories() { return [['id' => 3, 'name' => 'VPS Hosting', 'slug' => 'vps-hosting']]; }
    public function get_plans($gid) {
        return [['cycles' => [48 => ['available' => true, 'price_per_month' => 4.5], 1 => ['available' => true, 'price_per_month' => 8.25]]]];
    }
}
$db = Rackmonk_Plans_Manager::$db = new PDO('sqlite::memory:');
$db->exec('CREATE TABLE tblcurrencies (id INTEGER, code TEXT, prefix TEXT, suffix TEXT, rate REAL, `default` INTEGER)');
$db->exec("INSERT INTO tblcurrencies VALUES (1, 'INR', 'Rs', '', 1, 1), (2, 'USD', '$', '', 0.011, 0), (3, 'EUR', 'EUR', '', 0.01, 0)");
$db->exec('CREATE TABLE tblclients (id INTEGER, currency INTEGER)');
$db->exec('INSERT INTO tblclients VALUES (10, 2)');
require dirname(__DIR__) . '/includes/class-currency.php';
$checks = 0;
function check_currency($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    $GLOBALS['checks']++;
}
function select_ip($ip, $expected) {
    $_SERVER = ['REMOTE_ADDR' => $ip];
    Rackmonk_Currency::reset();
    check_currency(Rackmonk_Currency::current()['code'] === $expected, $ip . ' selects ' . $expected);
}
select_ip('49.32.0.1', 'INR');
select_ip('2409:4000::1', 'INR');
select_ip('::ffff:49.32.0.1', 'INR');
select_ip('::ffff:192.168.1.5', 'INR');
select_ip('8.8.8.8', 'USD');
select_ip('2001:4860:4860::8888', 'USD');
select_ip('127.0.0.1', 'INR');
select_ip('::1', 'INR');
select_ip('192.168.1.5', 'INR');
$_SERVER = ['REMOTE_ADDR' => '8.8.8.8', 'HTTP_CF_IPCOUNTRY' => 'IN', 'HTTP_X_FORWARDED_FOR' => '49.32.0.1'];
Rackmonk_Currency::reset();
check_currency(Rackmonk_Currency::current()['code'] === 'USD', 'Untrusted proxy headers cannot spoof India');
$_SERVER = ['REMOTE_ADDR' => '173.245.48.1', 'HTTP_CF_IPCOUNTRY' => 'IN'];
Rackmonk_Currency::reset();
check_currency(Rackmonk_Currency::current()['code'] === 'INR', 'Trusted Cloudflare country accepted');
$_SERVER = ['REMOTE_ADDR' => '49.32.0.1'];
$user = 1;
$meta[1] = ['_rackmonk_whmcs_client_id' => 10, '_rackmonk_whmcs_currency_id' => 1];
Rackmonk_Currency::reset();
check_currency(Rackmonk_Currency::current()['code'] === 'USD', 'Actual WHMCS billing currency wins over India and old sign-up metadata');
$meta[1] = ['_rackmonk_whmcs_currency_id' => 3];
Rackmonk_Currency::reset();
check_currency(Rackmonk_Currency::current()['code'] === 'EUR', 'Sign-up currency fallback supports other configured currencies');
$meta[1] = [];
Rackmonk_Currency::reset();
check_currency(Rackmonk_Currency::current()['code'] === 'INR', 'Unlinked user falls back to IP');
check_currency(Rackmonk_Currency::convert(249, Rackmonk_Currency::get('USD')) === 2.74, 'WHMCS rate applied to the amount');
check_currency(Rackmonk_Currency::format(2.74, Rackmonk_Currency::get('USD')) === '$2.74', 'USD cents retained');
check_currency(Rackmonk_Currency::number(249, Rackmonk_Currency::get('INR')) === '249', 'Existing INR number display retained');
check_currency(!Rackmonk_Currency::is_india_ip('garbage'), 'Invalid IP rejected');
check_currency(Rackmonk_Currency::replace_tokens('From {from_price:vps}/mo') === 'From Rs5/mo', 'Group token rendered without changing surrounding markup');
check_currency(Rackmonk_Currency::replace_tokens('{monthly_price:vps}') === 'Rs8', 'Monthly token uses monthly rather than discounted term');
check_currency(Rackmonk_Currency::replace_tokens('{from_price:unknown}') === 'Unavailable', 'Missing groups do not display invented prices');
echo "$checks currency assertions passed. In-memory database only.\nVERDICT: PASS\n";
