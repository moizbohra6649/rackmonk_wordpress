<?php
/** Standalone cart tests with in-memory WordPress doubles. No live database or orders. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
$options = [];
$hooks = [];
$posts = [];
$checks = 0;
function add_action($name, $callback) { global $hooks; $hooks[$name][] = $callback; }
function add_filter($name, $callback) { add_action($name, $callback); }
function add_shortcode($name, $callback) { add_action($name, $callback); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_generate_uuid4() { static $id = 1; return sprintf('00000000-0000-4000-8000-%012d', $id++); }
function maybe_serialize($value) { return serialize($value); }
function maybe_unserialize($value) { return unserialize($value); }
function get_option($key, $default = false) { global $options; return isset($options[$key]) ? unserialize($options[$key]) : $default; }
function wp_cache_delete($key, $group) {}
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function get_post_status($id) { $post = get_post($id); return $post ? $post->post_status : false; }
function get_post_field($field, $id) { $post = get_post($id); return $post ? $post->$field : ''; }
function has_shortcode($content, $tag) { return strpos($content, '[' . $tag . ']') !== false; }
function get_current_user_id() { return 1; }
function get_permalink($id) { return home_url('/hosting-cart/'); }
function wp_insert_post($data, $wp_error = false) { global $posts; $id = count($posts) + 1; $posts[$id] = (object) $data; return $id; }
function update_option($key, $value, $autoload = false) { global $options; $options[$key] = serialize($value); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function home_url($path) { return 'https://rackmonk.test' . $path; }
function admin_url($path) { return 'https://rackmonk.test/wp-admin/' . $path; }
function add_query_arg($key, $value, $url) { return $url . (strpos($url, '?') === false ? '?' : '&') . rawurlencode($key) . '=' . rawurlencode((string) $value); }
function absint($value) { return abs((int) $value); }
function rest_url($path) { return home_url('/wp-json/' . $path); }
function wp_create_nonce($action) { return 'test-nonce'; }
function wp_salt($scheme) { return 'test-secret-for-plan-links'; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
function selected($a, $b) { if ((string) $a === (string) $b) echo 'selected'; }
function checked($value) { if ($value) echo 'checked'; }
function disabled($value) { if ($value) echo 'disabled'; }
function wp_nonce_field($action) { echo '<input name="_wpnonce" value="test">'; }
function shortcode_atts($defaults, $atts, $tag) { return array_merge($defaults, $atts); }
class WP_Error {
    public $code; public $message; public $data;
    public function __construct($code, $message, $data) { $this->code = $code; $this->message = $message; $this->data = $data; }
}
class TestResponse {
    public $data; public $headers = [];
    public function __construct($data) { $this->data = $data; }
    public function header($key, $value) { $this->headers[$key] = $value; }
}
function rest_ensure_response($value) { return new TestResponse($value); }
class TestRequest {
    private $data;
    public function __construct($data) { $this->data = $data; }
    public function get_json_params() { return $this->data; }
}
class TestWPDB {
    public $options = 'wp_options';
    public $interfere = null;
    public $initializeInterfere = null;
    public function prepare($query, ...$values) { return [$query, $values]; }
    public function get_var($statement) { global $options; return $options[$statement[1][0]] ?? null; }
    public function query($statement) {
        global $options;
        if (strpos($statement[0], 'INSERT IGNORE') === 0) {
            if ($this->initializeInterfere) { ($this->initializeInterfere)(); $this->initializeInterfere = null; }
            [$key, $value] = $statement[1];
            if (isset($options[$key])) return 0;
            $options[$key] = $value;
            return 1;
        }
        if ($this->interfere) { ($this->interfere)(); $this->interfere = null; return 0; }
        [$next, $key, $before] = $statement[1];
        if (($options[$key] ?? null) !== $before) return 0;
        $options[$key] = $next;
        return 1;
    }
}
$wpdb = new TestWPDB();
require dirname(__DIR__) . '/includes/class-website-cart.php';
require dirname(__DIR__) . '/includes/class-plans-manager.php';
class CartNativeStatement {
    private $db, $sql, $args;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function execute($args) { $this->args = $args; }
    public function fetchAll($mode) {
        $sql = $this->sql; $args = $this->args;
        if (strpos($sql, 'tblcurrencies') !== false) return [['id' => 1, 'code' => 'INR', 'prefix' => 'Rs.']];
        if (strpos($sql, 'tblproducts') !== false) return isset($this->db->products[$args[0]]) ? [$this->db->products[$args[0]]] : [];
        if (strpos($sql, 'tblproductconfiglinks') !== false) return $this->db->options[$args[0]] ?? [];
        if (strpos($sql, 'tblproductconfigoptionssub') !== false) return $this->db->choices[$args[0]] ?? [];
        if (strpos($sql, "type = 'product'") !== false) return $args[1] == 1 && isset($this->db->base[$args[0]]) ? [$this->db->base[$args[0]]] : [];
        if (strpos($sql, "type = 'configoptions'") !== false) return $args[1] == 1 && isset($this->db->prices[$args[0]]) ? [$this->db->prices[$args[0]]] : [];
        throw new RuntimeException('Unexpected native pricing query');
    }
}
class CartNativeDB {
    public $products, $options, $choices, $base, $prices;
    public static function amounts($monthly, $annual, $monthly_setup, $annual_setup) {
        return ['monthly' => $monthly, 'msetupfee' => $monthly_setup, 'annually' => $annual, 'asetupfee' => $annual_setup, 'biennially' => '-1.00', 'triennially' => '-1.00'];
    }
    public function __construct() {
        $this->products = [11 => ['id' => 11, 'name' => 'Custom Cloud', 'paytype' => 'recurring', 'hidden' => 0], 91 => ['id' => 91, 'name' => 'Checkout test', 'paytype' => 'recurring', 'hidden' => 0]];
        $this->options = [11 => [['id' => 7, 'optiontype' => 1, 'hidden' => 0, 'optionname' => 'Memory'], ['id' => 8, 'optiontype' => 1, 'hidden' => 0, 'optionname' => 'Antivirus']], 91 => [['id' => 9, 'optiontype' => 1, 'hidden' => 0, 'optionname' => 'Memory']]];
        $this->choices = [7 => [['id' => 10, 'optionname' => '8 GB', 'hidden' => 0]], 8 => [['id' => 20, 'optionname' => 'Enterprise Antivirus', 'hidden' => 0]], 9 => [['id' => 30, 'optionname' => '4 GB', 'hidden' => 0]]];
        $this->base = [11 => self::amounts('1000.00', '9000.00', '10.00', '20.00'), 91 => self::amounts('100.00', '900.00', '5.00', '10.00')];
        $this->prices = [10 => self::amounts('100.01', '900.01', '2.00', '3.00'), 20 => self::amounts('50.00', '400.00', '1.00', '4.00'), 30 => self::amounts('20.00', '150.00', '2.00', '3.00')];
    }
    public function prepare($sql) { return new CartNativeStatement($this, $sql); }
}
class NativeCartManager extends Rackmonk_Plans_Manager {
    public $db;
    public function get_whmcs_db() { return $this->db; }
}
$native_db = new CartNativeDB();
$native_manager = new NativeCartManager();
$native_manager->db = $native_db;
$singleton = new ReflectionProperty(Rackmonk_Plans_Manager::class, 'instance');
$singleton->setValue(null, $native_manager);
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException('FAIL: ' . $message); $checks++; }
function rejects($callback, $message) { try { $callback(); } catch (Throwable $error) { check(true, $message); return; } check(false, $message); }

$plan = ['id' => 11, 'name' => 'Custom Cloud'];
$input = ['enabled' => '1', 'base_price' => '999.99', 'currency' => 'INR', 'symbol' => 'Rs.', 'resources' => [
    'ram' => [['id' => '0', 'label' => '4 GB', 'price' => '0'], ['id' => '0', 'label' => '8 GB', 'price' => '100.01']],
    'antivirus' => [['id' => '0', 'label' => 'No', 'price' => '0'], ['id' => '0', 'label' => 'Enterprise Antivirus', 'price' => '50']],
]];
$config = Rackmonk_Website_Cart::validate_config($input, [], $plan);
check(count(Rackmonk_Website_Cart::RESOURCES) === 14, 'Expose all fourteen resource fields');
check($config['resources']['antivirus']['type'] === 'checkbox', 'Use antivirus checkbox');
$demo_rows = Rackmonk_Website_Cart::demo_resources(['name' => 'KVM VPS 1']);
check(array_keys($demo_rows) === array_slice(array_keys(Rackmonk_Website_Cart::RESOURCES), 0, 12), 'Demo template matches screenshot field order and all twelve resources');
$demo_config = Rackmonk_Website_Cart::validate_config(['enabled' => 1, 'base_price' => '100', 'currency' => 'INR', 'symbol' => 'Rs.', 'resources' => $demo_rows, 'demo_pricing' => 1], [], $plan);
$demo_choices = array_map(function ($r) { return $r['choices'][0]['id']; }, $demo_config['resources']);
$demo_quote = Rackmonk_Website_Cart::price_config($demo_config, 12, $demo_choices, Rackmonk_Website_Cart::revision($demo_config));
check(count($demo_quote['selection']) === 12 && $demo_quote['total'] === 1200, 'All included defaults keep original base price');
check($demo_quote['demo_pricing'] === true, 'Authoritative quote identifies sample upgrade prices');
$demo_choices['ram'] = $demo_config['resources']['ram']['choices'][1]['id'];
$demo_choices['antivirus'] = $demo_config['resources']['antivirus']['choices'][1]['id'];
$demo_upgraded = Rackmonk_Website_Cart::price_config($demo_config, 12, $demo_choices, Rackmonk_Website_Cart::revision($demo_config));
check($demo_upgraded['monthly'] === 798 && $demo_upgraded['total'] === 9576, 'Memory and antivirus upgrades correctly multiply across billing duration');
check($demo_config['resources']['os']['label'] === 'Operating System' && $demo_config['resources']['ram']['label'] === 'Memory', 'Keep screenshot labels');
$selected = ['ram' => $config['resources']['ram']['choices'][1]['id'], 'antivirus' => $config['resources']['antivirus']['choices'][1]['id']];
$revision = Rackmonk_Website_Cart::revision($config);
$quote = Rackmonk_Website_Cart::price_config($config, 12, $selected, $revision);
check($quote['monthly'] === 1150, 'Calculate resources plus base without floating-point cent loss');
check($quote['total'] === 13800, 'Calculate authoritative annual total');
check($quote['selection'][1]['choice'] === 'Enterprise Antivirus', 'Retain selected resource labels');
foreach ([0, -1, 48, '12', 12.0, [], null] as $bad) rejects(function() use ($config, $bad, $selected, $revision) { Rackmonk_Website_Cart::price_config($config, $bad, $selected, $revision); }, 'Reject invalid or legacy billing term');
foreach (['-1', '1e3', 'NaN', '1.001', '10000000', ''] as $bad) rejects(function() use ($bad) { Rackmonk_Website_Cart::money($bad); }, 'Reject invalid monthly rate');
rejects(function() use ($config, $selected) { Rackmonk_Website_Cart::price_config($config, 1, $selected, 'old'); }, 'Reject stale price revision');
rejects(function() use ($config, $revision) { Rackmonk_Website_Cart::price_config($config, 1, [], $revision); }, 'Require choices for each enabled resource');
rejects(function() use ($config, $selected, $revision) { $selected['extra'] = '0'; Rackmonk_Website_Cart::price_config($config, 1, $selected, $revision); }, 'Reject unexpected resource keys');
rejects(function() use ($config, $selected, $revision) { $selected['ram'] = $selected['antivirus']; Rackmonk_Website_Cart::price_config($config, 1, $selected, $revision); }, 'Reject cross-resource choice IDs');
rejects(function() use ($config, $selected) { $config['enabled'] = false; Rackmonk_Website_Cart::price_config($config, 1, $selected, Rackmonk_Website_Cart::revision($config)); }, 'Block disabled plans');
rejects(function() use ($input, $plan) { $input['resources']['antivirus'] = [$input['resources']['antivirus'][0]]; Rackmonk_Website_Cart::validate_config($input, [], $plan); }, 'Require two antivirus states');
rejects(function() use ($input, $plan) { $input['resources']['ram'] = array_fill(0, 21, ['label' => 'RAM', 'price' => '0']); Rackmonk_Website_Cart::validate_config($input, [], $plan); }, 'Limit fields to twenty choices to fit standard PHP input limits');
$free = Rackmonk_Website_Cart::validate_config(['enabled' => 1, 'base_price' => '0', 'currency' => 'INR', 'symbol' => 'Rs.', 'resources' => []], [], $plan);
check(Rackmonk_Website_Cart::price_config($free, 36, [], Rackmonk_Website_Cart::revision($free))['total'] === 0, 'Preserve legitimate free prices');

$wpdb->initializeInterfere = function() { global $options; $options[Rackmonk_Website_Cart::OPTION] = serialize([77 => ['name' => 'Raced first-save plan']]); };
Rackmonk_Website_Cart::save(11, $input, Rackmonk_Website_Cart::revision([]), $plan);
check(isset(get_option(Rackmonk_Website_Cart::OPTION)[77]), 'First-save initialization never overwrites an option created by a concurrent editor');
$saved = Rackmonk_Website_Cart::get_config(11);
check($saved['base_price'] === '999.99' && Rackmonk_Website_Cart::enabled(11), 'Save local WordPress settings');
rejects(function() use ($input, $plan) { Rackmonk_Website_Cart::save(11, $input, Rackmonk_Website_Cart::revision([]), $plan); }, 'Reject stale admin edits');
$edit = $input;
$edit['resources'] = array_map(function($resource) { return $resource['choices']; }, $saved['resources']);
$wpdb->interfere = function() { global $options; $all = get_option(Rackmonk_Website_Cart::OPTION); $all[99] = ['name' => 'Concurrent plan']; $options[Rackmonk_Website_Cart::OPTION] = serialize($all); };
$edit['base_price'] = '1000.00';
Rackmonk_Website_Cart::save(11, $edit, Rackmonk_Website_Cart::revision($saved), $plan);
check(isset(get_option(Rackmonk_Website_Cart::OPTION)[99]), 'Compare-and-swap preserves another editor\'s plan');
check(Rackmonk_Website_Cart::get_config(11)['base_price'] === '1000.00', 'Retry intended edit after concurrent unrelated save');
$saved = Rackmonk_Website_Cart::get_config(11);
$native = Rackmonk_Website_Cart::runtime_config(11);
$body = ['pid' => 11, 'months' => 12, 'choices' => ['option_7' => '10', 'option_8' => '20'], 'revision' => Rackmonk_Website_Cart::revision($native), 'total' => 0, 'price' => 0];
$cart = new Rackmonk_Website_Cart();
$response = $cart->quote(new TestRequest($body));
check($response->data['total'] === 10327.01 && $response->data['recurring'] === 10300.01 && $response->data['setup'] === 27, 'Ignore client totals and use native annual prices and setup fees');
check($response->data['selection'][0]['id'] === '10' && $response->data['selection'][1]['id'] === '20', 'Quote native linked choice IDs');
check($response->headers['Cache-Control'] === 'no-store', 'Prevent quote caching');
$body['revision'] = 'stale';
check($cart->quote(new TestRequest($body))->data['status'] === 409, 'Surface stale cart revisions as HTTP 409');
$body['revision'] = Rackmonk_Website_Cart::revision($native);
$native_db->prices[10]['annually'] = '901.01';
check($cart->quote(new TestRequest($body))->data['status'] === 409, 'Native repricing invalidates a previously quoted revision');
$native_db->prices[10]['annually'] = '900.01';
check($cart->quote(new TestRequest(['pid' => ['bad']]))->data['status'] === 400, 'Validate REST plan IDs');
$url = Rackmonk_Website_Cart::get_cart_url(11);
parse_str(parse_url($url, PHP_URL_QUERY), $query);
check(strpos($url, 'rackmonk.test/?rackmonk_cart=1&plan=') !== false && $query['plan'] !== '11', 'Offer a virtual cart route with an encrypted plan ID');
check(Rackmonk_Website_Cart::decode_plan_id($query['plan']) === 11, 'Encrypted plan ID round trips');
$tampered = substr_replace($query['plan'], $query['plan'][5] === 'A' ? 'B' : 'A', 5, 1);
check(Rackmonk_Website_Cart::decode_plan_id($tampered) === 0, 'Reject tampered plan token');
check(Rackmonk_Website_Cart::decode_plan_id('11') === 11, 'Preserve existing numeric links');
$_GET = ['plan' => $query['plan']];
check(strpos($cart->render(), '"requestedPlan":11') !== false, 'Encrypted link selects the requested plan');
$_GET = ['plan' => '11', 'term' => '12'];
$html = $cart->render();
check(strpos($html, 'data-rm-cart-data') !== false && strpos($html, '"requestedPlan":11') !== false, 'Render standalone frontend catalog and deep link');
check(strpos($html, 'whmcs/cart.php') === false, 'Cart markup has no WHMCS checkout URL');
$_GET = ['plan' => '11', 'term' => '48'];
$blocked = $cart->render();
check(strpos($blocked, 'base package only') !== false && strpos($blocked, 'data-rm-cart-data') === false && strpos($blocked, 'checkoutUrl') === false, 'Direct 48-month cart link is explicitly rejected before configurator or WooCommerce handoff');
ob_start();
$_GET['resource_plan'] = 11;
Rackmonk_Website_Cart::render_editor([$plan], ['code' => 'INR', 'prefix' => 'Rs.']);
$editor = ob_get_clean();
check(substr_count($editor, 'data-resource="') === 14, 'Render all expanded admin fields');
check(strpos($editor, 'name="resource_complete"') !== false, 'Detect truncated admin submissions');
check(strpos($editor, 'website_cart[manual_48][choices][7][10][recurring]') !== false, 'Editor exposes native option and choice IDs');
$edit['manual_48'] = ['version' => Rackmonk_Website_Cart::revision($native), 'base' => ['recurring' => '4000', 'setup' => '0'], 'choices' => [7 => [10 => ['recurring' => '50', 'setup' => '1']], 8 => [20 => ['recurring' => '0', 'setup' => '0']]]];
$bad = $edit; unset($bad['manual_48']['choices'][8][20]);
rejects(function () use ($bad, $saved, $plan) { Rackmonk_Website_Cart::save(11, $bad, Rackmonk_Website_Cart::revision($saved), $plan); }, 'Reject incomplete manual form');
$native_db->prices[10]['annually'] = '901.01';
rejects(function () use ($edit, $saved, $plan) { Rackmonk_Website_Cart::save(11, $edit, Rackmonk_Website_Cart::revision($saved), $plan); }, 'Reject stale native editor');
$native_db->prices[10]['annually'] = '900.01';
Rackmonk_Website_Cart::save(11, $edit, Rackmonk_Website_Cart::revision($saved), $plan);
$manual_config = Rackmonk_Website_Cart::runtime_config(11);
check($manual_config['prices'][48]['recurring'] === '4000.00', 'Preserve saved manual rates without offering website checkout');
rejects(function () use ($manual_config) { Rackmonk_Website_Cart::price_config($manual_config, 48, ['option_7' => '10', 'option_8' => '20'], Rackmonk_Website_Cart::revision($manual_config)); }, 'Reject legacy manually priced 48-month quote before WooCommerce');
$page = Rackmonk_Website_Cart::ensure_page();
check(get_post($page)->post_content === '[rackmonk_cart]', 'Create a dedicated WordPress cart page with the correct shortcode');
check(Rackmonk_Website_Cart::ensure_page() === $page && count($posts) === 1, 'Reuse cart page without duplicate publishing');
check(Rackmonk_Website_Cart::get_cart_url() === 'https://rackmonk.test/hosting-cart/', 'Use the dedicated page URL when published');

class NoWhmcsCartManager extends Rackmonk_Plans_Manager {
    public function get_whmcs_db() { throw new RuntimeException('Unexpected WHMCS database access'); }
    public function get_48_month_package($pid) { return ['id' => 24, 'monthly' => '13440.00', 'msetupfee' => '25.00', 'order_url' => 'https://billing.rackmonk.test/cart.php?a=add&pid=24&currency=1']; }
    public function get_plans($group_id = null) { return [$this->format_product(['id' => 11, 'name' => 'Custom Cloud', 'monthly' => 800])]; }
    public function get_categories() { return [['id' => 1, 'name' => 'Cloud Hosting', 'slug' => 'cloud']]; }
    public function get_default_currency() { return ['code' => 'INR', 'prefix' => 'Rs.']; }
}
$manager = new NoWhmcsCartManager();
$formatted = $manager->format_product(['id' => 11, 'name' => 'Custom Cloud', 'monthly' => 1000]);
check($formatted['is_configurable'] && strpos($formatted['order_url'], 'billing.rackmonk.test/cart.php') !== false, '48-month plan links use the mapped native package');
check($manager->delete_whmcs_plan(11) === false, 'Protect locally configured plans from deletion before database access');
$singleton->setValue(null, $manager);
require dirname(__DIR__) . '/includes/class-shortcode.php';
$pricing = (new Rackmonk_Pricing_Shortcode())->render_shortcode([]);
check(strpos($pricing, 'href="https://billing.rackmonk.test/cart.php?a=add&amp;pid=24&amp;currency=1"') !== false, 'Default public card leads directly to mapped WHMCS package');
check(strpos($pricing, '<span class="price-val">280</span>') !== false, 'Configurable plans retain numeric source-catalog pricing for the selected default term');
check(strpos($pricing, 'Build your plan') === false && strpos($pricing, 'Choose resources and billing') === false, 'Resource customization does not replace public base prices');
$monthly_pricing = (new Rackmonk_Pricing_Shortcode())->render_shortcode(['default_term' => '1']);
check(strpos($monthly_pricing, 'href="https://rackmonk.test/hosting-cart/?plan=') !== false && strpos($monthly_pricing, '?plan=11') === false && strpos($monthly_pricing, '&amp;term=1') !== false, 'Shorter pricing cycles retain encrypted cart links and exact billing term');
check(strpos($monthly_pricing, '<span class="price-val">800</span>') !== false, 'Public monthly price comes from source catalog, not website resource settings');
$elementor_items = $manager->get_elementor_pricing_items();
check($elementor_items[0]['m_price'] === $formatted['currency'] . '280' && $elementor_items[0]['y_price'] === $formatted['currency'] . '480', 'Hostinkar widget retains numeric base-plan prices for both billing tabs');
check($elementor_items[0]['m_period'] === '/mo' && $elementor_items[0]['btn_label'] === 'Choose Plan', 'Public Elementor cards remain basic priced plans with a cart link');
$singleton->setValue(null, $native_manager);
echo "$checks standalone cart assertions passed. No live database, orders, invoices or WHMCS calls.\nVERDICT: PASS\n";
