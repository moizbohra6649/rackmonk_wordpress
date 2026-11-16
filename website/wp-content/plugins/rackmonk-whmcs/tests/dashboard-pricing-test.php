<?php
/** Isolated admin/group/currency tests. No WordPress bootstrap, live writes or login bypass. */
if (PHP_SAPI !== 'cli') exit;
require __DIR__ . '/resource-options-test.php';
require dirname(__DIR__) . '/includes/class-configurable-groups.php';
require dirname(__DIR__) . '/includes/class-plan-prices.php';

function checked($value) { if ($value) echo 'checked'; }
function admin_url($path) { return 'https://rackmonk.test/wp-admin/' . $path; }
function add_query_arg($args, $value, $url = null) {
    if (is_array($args)) { $url = $value; } else { $args = [$args => $value]; }
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args);
}
function current_user_can($capability) { global $can_manage; return $can_manage; }
function check_admin_referer($action) { global $nonce_ok, $nonce_actions; $nonce_actions[] = $action; if (!$nonce_ok) throw new DashboardDenied('Invalid nonce'); }
function check_ajax_referer($action, $key, $die = true) { global $nonce_ok, $nonce_actions; $nonce_actions[] = $action; return $nonce_ok; }
function wp_create_nonce($action) { return 'test-' . $action; }
function wp_send_json_success($data, $status = 200) { throw new DashboardJson(['success' => true, 'data' => $data], $status); }
function wp_send_json_error($data, $status = 200) { throw new DashboardJson(['success' => false, 'data' => $data], $status); }
function wp_unslash($value) { return $value; }
function wp_safe_redirect($url) { throw new DashboardRedirect($url); }
function wp_redirect($url) { wp_safe_redirect($url); }
function wp_die($message) { throw new DashboardDenied($message); }
function esc_html__($value, $domain) { return $value; }
function wp_nonce_url($url, $action) { return $url . '&_wpnonce=test'; }
function add_action($name, $callback) {}
function add_filter($name, $callback) {}
function add_shortcode($name, $callback) {}
function get_option($key, $default = false) { return $default; }
function home_url($path) { return 'https://rackmonk.test' . $path; }
class DashboardDenied extends Error {}
class DashboardRedirect extends Error {}
class DashboardJson extends Error {
    public $payload;
    public $status;
    public function __construct($payload, $status) { parent::__construct('JSON response'); $this->payload = $payload; $this->status = $status; }
}
function native_ajax($admin, $post) {
    $_POST = $post;
    try { $admin->load_native_selection(); }
    catch (DashboardJson $response) { return ['status' => $response->status, 'payload' => $response->payload]; }
    throw new RuntimeException('Expected an AJAX response');
}
function section_ajax($admin, $post) {
    $_POST = $post;
    try { $admin->load_admin_section(); }
    catch (DashboardJson $response) { return ['status' => $response->status, 'payload' => $response->payload]; }
    throw new RuntimeException('Expected a dashboard section response');
}

$db->exec("ALTER TABLE tblproducts ADD COLUMN name TEXT DEFAULT 'Example plan'");
$db->exec('ALTER TABLE tblproducts ADD COLUMN gid INTEGER DEFAULT 1');
$db->exec("ALTER TABLE tblcurrencies ADD COLUMN code TEXT DEFAULT ''");
$db->exec("ALTER TABLE tblcurrencies ADD COLUMN prefix TEXT DEFAULT ''");
$db->exec("UPDATE tblcurrencies SET code = 'INR', prefix = 'Rs.' WHERE id = 1");
$db->exec("UPDATE tblcurrencies SET code = 'USD', prefix = '$' WHERE id = 2");
$db->exec("UPDATE tblproducts SET name = 'Cloud Example <script>alert(1)</script>' WHERE id = 11");
$db->exec('UPDATE tblproducts SET gid = 2 WHERE id = 12');

function group_state($db, $gid) { return Rackmonk_Configurable_Groups::group($db, $gid); }
function choice_input($state, $oid, $cid) {
    $choice = $state['options'][$oid]['choices'][$cid];
    $prices = [];
    foreach ($state['prices'] as $row) {
        if ((int) $row['relid'] !== $cid) continue;
        foreach (Rackmonk_Configurable_Groups::CYCLES as $column => $cycle) {
            $prices[(int) $row['currency']][$column] = number_format((float) $row[$column], 2, '.', '');
            $prices[(int) $row['currency']][$cycle['setup']] = number_format((float) $row[$cycle['setup']], 2, '.', '');
        }
    }
    return ['label' => $choice['optionname'], 'sortorder' => (string) $choice['sortorder'], 'hidden' => (string) $choice['hidden'], 'prices' => $prices];
}
function all_prices($db) { return $db->query('SELECT * FROM tblpricing ORDER BY id')->fetchAll(); }
function amounts($monthly) {
    $result = [];
    foreach (Rackmonk_Configurable_Groups::CYCLES as $column => $cycle) { $result[$column] = $column === 'monthly' ? $monthly : '-1.00'; $result[$cycle['setup']] = '0.00'; }
    return $result;
}

$gid = (int) Rackmonk_Resource_Options::get_config($db, 11)['group_id'];
$oid = (int) Rackmonk_Resource_Options::get_config($db, 11)['resources']['cpu']['id'];
$cid = (int) $cpu['id'];
$state = group_state($db, $gid);
$input = choice_input($state, $oid, $cid);
$before = all_prices($db);
$db->exec("CREATE TRIGGER forbid_noop_prices BEFORE UPDATE ON tblpricing BEGIN SELECT RAISE(ABORT, 'Unexpected price rewrite'); END");
Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $input, Rackmonk_Configurable_Groups::version($state));
check(all_prices($db) === $before, 'No-op native save does not rewrite any price column');
$input['label'] = 'Renamed CPU';
Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $input, Rackmonk_Configurable_Groups::version(group_state($db, $gid)));
check(all_prices($db) === $before, 'Label-only edit preserves manual USD annual totals and all setup fees');
$db->exec('DROP TRIGGER forbid_noop_prices');
$state = group_state($db, $gid);
$input = choice_input($state, $oid, $cid);
$input['prices'][2]['annually'] = '123.45';
Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $input, Rackmonk_Configurable_Groups::version($state));
$after = all_prices($db);
foreach ($after as $index => $row) {
    if ((int) $row['relid'] === $cid && (int) $row['currency'] === 2) {
        $expected = $before[$index]; $expected['annually'] = 123.45;
        check($row === $expected, 'Only the explicitly changed USD annual cell is updated');
    } else check($row === $before[$index], 'Unrelated native currency/choice prices remain unchanged');
}
rejects(function () use ($db, $gid, $oid, $cid, $input, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $input, Rackmonk_Configurable_Groups::version($state)); }, 'Reject stale native group editor');
$state = group_state($db, $gid);
$input = choice_input($state, $oid, $cid);
rejects(function () use ($db, $gid, $oid, $cid, $input, $state) { Rackmonk_Configurable_Groups::save_choice($db, 99, $oid, $cid, $input, Rackmonk_Configurable_Groups::version($state)); }, 'Reject foreign group/choice combinations');
$bad = $input; unset($bad['prices'][1]['monthly']);
rejects(function () use ($db, $gid, $oid, $cid, $bad, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $bad, Rackmonk_Configurable_Groups::version($state)); }, 'Reject truncated currency fields');
$bad = $input; $bad['prices'][99] = $bad['prices'][1];
rejects(function () use ($db, $gid, $oid, $cid, $bad, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $bad, Rackmonk_Configurable_Groups::version($state)); }, 'Reject injected currency IDs');
$bad = $input; $bad['hidden'] = '1';
rejects(function () use ($db, $gid, $oid, $cid, $bad, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $bad, Rackmonk_Configurable_Groups::version($state)); }, 'Do not hide the last visible dropdown choice');
foreach (['-2', '1e3', 'NaN', '1.001', '', '100000000'] as $bad_amount) {
    $bad = $input; $bad['prices'][1]['monthly'] = $bad_amount;
    rejects(function () use ($db, $gid, $oid, $cid, $bad, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $bad, Rackmonk_Configurable_Groups::version($state)); }, 'Reject invalid native price: ' . $bad_amount);
}
$db->exec("CREATE TRIGGER fail_dashboard_price BEFORE UPDATE ON tblpricing BEGIN SELECT RAISE(ABORT, 'Price update failed'); END");
$bad = $input; $bad['label'] = 'Must roll back'; $bad['prices'][2]['monthly'] = '500';
rejects(function () use ($db, $gid, $oid, $cid, $bad, $state) { Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $bad, Rackmonk_Configurable_Groups::version($state)); }, 'Atomic native metadata/price save rolls back');
check(group_state($db, $gid)['options'][$oid]['choices'][$cid]['optionname'] === 'Renamed CPU', 'Rollback retains previous native label');
$db->exec('DROP TRIGGER fail_dashboard_price');
$db->prepare('UPDATE tblproductconfigoptionssub SET optionname = ? WHERE id = ?')->execute(['image%20name|Image   Name ', $cid]);
$state = group_state($db, $gid);
$input = choice_input($state, $oid, $cid);
$before = all_prices($db);
Rackmonk_Configurable_Groups::save_choice($db, $gid, $oid, $cid, $input, Rackmonk_Configurable_Groups::version($state));
check(group_state($db, $gid)['options'][$oid]['choices'][$cid]['optionname'] === 'image%20name|Image   Name ', 'No-op save preserves native provisioning value and whitespace byte-for-byte');
check(all_prices($db) === $before, 'Complex-label no-op preserves the complete price matrix');

// Independent base-plan currencies: INR existing, USD initially absent.
$inr = amounts('999.00'); $inr['annually'] = '7000.00'; $inr['msetupfee'] = '19.00';
$columns = implode(', ', array_keys($inr));
$db->prepare("INSERT INTO tblpricing (type, currency, relid, $columns) VALUES ('product', 1, 11, " . implode(',', array_fill(0, count($inr), '?')) . ')')->execute(array_values($inr));
$plan_state = Rackmonk_Plan_Prices::state($db, 11);
$inr_before = $plan_state['prices'][0];
$native_before = $db->query("SELECT * FROM tblpricing WHERE type = 'configoptions' ORDER BY id")->fetchAll();
Rackmonk_Plan_Prices::save_currency($db, 11, 2, amounts('9.99'), Rackmonk_Plan_Prices::version($plan_state));
$plan_after = Rackmonk_Plan_Prices::state($db, 11);
check($plan_after['prices'][0] === $inr_before, 'Adding USD leaves every original INR amount unchanged');
check((float) $plan_after['prices'][1]['monthly'] === 9.99, 'USD gets its explicit price, never the INR amount');
check((float) $plan_after['prices'][1]['annually'] === -1.0, 'Unconfigured USD terms stay disabled rather than free');
check($db->query("SELECT * FROM tblpricing WHERE type = 'configoptions' ORDER BY id")->fetchAll() === $native_before, 'Base pricing edits never touch native resource prices');
rejects(function () use ($db, $plan_state, $inr) { Rackmonk_Plan_Prices::save_currency($db, 11, 1, $inr, Rackmonk_Plan_Prices::version($plan_state)); }, 'Stale INR form cannot overwrite later USD edits');
Rackmonk_Plan_Prices::save_currency($db, 11, 2, amounts('9.99'), Rackmonk_Plan_Prices::version($plan_after));
check(count(Rackmonk_Plan_Prices::state($db, 11)['prices']) === 2, 'Repeated currency save does not duplicate USD rows');
rejects(function () use ($db, $plan_after) { Rackmonk_Plan_Prices::save_currency($db, 11, 99, amounts('1'), Rackmonk_Plan_Prices::version($plan_after)); }, 'Reject foreign base-plan currency');
check(!$db->inTransaction(), 'Failed saves leave no open transaction');

class Rackmonk_Plans_Manager {
    public static $db;
    public static $created = [];
    public static function get_instance() { return new self(); }
    public function get_whmcs_db() { return self::$db; }
    public function get_categories() { return [['id' => 1, 'name' => 'Shared'], ['id' => 2, 'name' => 'VPS'], ['id' => 3, 'name' => 'Empty']]; }
    public function get_default_currency() { return ['id' => 1, 'code' => 'INR', 'prefix' => 'Rs.']; }
    public function get_whmcs_system_url() { return 'https://billing.rackmonk.test/'; }
    public function create_whmcs_plan($data) { self::$created[] = $data; return 123; }
    public function get_plans() {
        $plans = [];
        foreach (self::$db->query('SELECT * FROM tblproducts ORDER BY id')->fetchAll() as $row) $plans[] = ['id' => $row['id'], 'name' => $row['name'], 'gid' => $row['gid'], 'group_name' => 'Shared', 'is_popular' => false, 'features' => ['Basic hosting plan'], 'currency' => 'Rs.', 'cycles' => array_fill_keys(['1', '12', '24', '48'], ['price_per_month' => 100, 'discount_text' => '']), 'order_url' => 'https://rackmonk.test/hosting-cart/?plan=' . $row['id']];
        return $plans;
    }
}
Rackmonk_Plans_Manager::$db = $db;
require dirname(__DIR__) . '/includes/class-website-cart.php';
require dirname(__DIR__) . '/includes/class-admin-page.php';
$admin = new Rackmonk_Admin_Page();
$can_manage = false; $nonce_ok = false; $nonce_actions = [];
$_GET = ['page' => 'rackmonk-whmcs-plans'];
$_POST = ['rackmonk_action' => 'save_native_choice', 'native_group' => $gid, 'native_option' => $oid, 'native_choice' => $cid];
$admin->handle_form_actions();
check(!$nonce_actions, 'Users without manage_options cannot reach native save');
rejects(function () use ($admin) { $admin->render_page(); }, 'Dashboard rendering requires manage_options');
$can_manage = true;
$_GET['page'] = 'another-page'; $admin->handle_form_actions();
check(!$nonce_actions, 'Admin actions are scoped to this dashboard');
$_GET['page'] = 'rackmonk-whmcs-plans';
rejects(function () use ($admin) { $admin->handle_form_actions(); }, 'Native save requires a valid nonce');
$nonce_ok = true;
try { $admin->handle_form_actions(); check(false, 'Incomplete native form must redirect with an error'); }
catch (DashboardRedirect $redirect) { check(strpos($redirect->getMessage(), 'status=error') !== false && strpos($redirect->getMessage(), '#configuration-groups') !== false, 'Incomplete native form is rejected before writes'); }
$_POST = ['rackmonk_action' => 'save_plan_currency', 'pricing_plan' => 11, 'pricing_currency' => 2];
$nonce_ok = false;
rejects(function () use ($admin) { $admin->handle_form_actions(); }, 'Base currency save requires its own nonce');
$nonce_ok = true;
try { $admin->handle_form_actions(); check(false, 'Incomplete base price form must be rejected'); }
catch (DashboardRedirect $redirect) { check(strpos($redirect->getMessage(), 'status=error') !== false, 'Incomplete currency form is rejected before writes'); }
$_POST = ['rackmonk_action' => 'add_plan', 'monthly' => '249.00'];
try { $admin->handle_form_actions(); check(false, 'Truncated creation form must be rejected'); }
catch (DashboardRedirect $redirect) { check(strpos($redirect->getMessage(), 'status=error') !== false && !Rackmonk_Plans_Manager::$created, 'Creation terminal marker prevents silently lost USD fields'); }

$state = group_state($db, $gid);
$_POST = ['rackmonk_action' => 'save_native_choice', 'native_group' => $gid, 'native_option' => $oid, 'native_choice' => $cid, 'native_complete' => '1', 'native_version' => Rackmonk_Configurable_Groups::version($state), 'native_settings' => choice_input($state, $oid, $cid)];
$_POST['native_settings']['label'] = 'CPU saved through dashboard';
try { $admin->handle_form_actions(); check(false, 'Native save should redirect'); }
catch (DashboardRedirect $redirect) { check(strpos($redirect->getMessage(), 'status=success') !== false, 'Authorized complete native form is routed to the native group store'); }
check(group_state($db, $gid)['options'][$oid]['choices'][$cid]['optionname'] === 'CPU saved through dashboard', 'Native admin handler persists choice metadata');
check(end($nonce_actions) === 'rackmonk_native_choice_' . $gid . '_' . $oid . '_' . $cid, 'Native nonce is bound to the selected group/option/choice');
$plan_state = Rackmonk_Plan_Prices::state($db, 11);
$_POST = ['rackmonk_action' => 'save_plan_currency', 'pricing_plan' => 11, 'pricing_currency' => 2, 'price_complete' => '1', 'pricing_version' => Rackmonk_Plan_Prices::version($plan_state), 'plan_prices' => amounts('9.99')];
try { $admin->handle_form_actions(); check(false, 'Currency save should redirect'); }
catch (DashboardRedirect $redirect) { check(strpos($redirect->getMessage(), 'status=success') !== false && strpos($redirect->getMessage(), '#base-plan-prices') !== false, 'Authorized currency form is routed to the selected base-price store'); }
check(Rackmonk_Plan_Prices::state($db, 11)['prices'][0] === $inr_before, 'USD admin form submission leaves original INR row intact');
check(end($nonce_actions) === 'rackmonk_plan_currency_11_2', 'Currency nonce is bound to the plan and currency');

$ajax_request = ['native_group' => (string) $gid, 'native_option' => (string) $oid, 'native_choice' => (string) $cid, 'nonce' => 'test-rackmonk_native_selection'];
$can_manage = false;
check(native_ajax($admin, $ajax_request)['status'] === 403, 'AJAX loading requires manage_options');
$can_manage = true; $nonce_ok = false;
check(native_ajax($admin, $ajax_request)['status'] === 403, 'AJAX loading requires a valid selection nonce');
$nonce_ok = true;
$ajax_before = all_prices($db);
$response = native_ajax($admin, $ajax_request);
check($response['status'] === 200 && $response['payload']['success'], 'Authorized selection returns a native detail fragment');
check($response['payload']['data']['group'] === $gid && $response['payload']['data']['option'] === $oid && $response['payload']['data']['choice'] === $cid, 'Response identifies the exact selected group, option and choice');
check(strpos($response['payload']['data']['html'], 'name="native_choice" value="' . $cid . '"') !== false, 'AJAX fragment refreshes the save form ownership IDs');
check(strpos($response['payload']['data']['html'], 'native_settings[prices][1][monthly]') !== false && strpos($response['payload']['data']['html'], 'native_settings[prices][2][monthly]') !== false, 'AJAX fragment contains both INR and USD fields');
check(strpos($response['payload']['data']['html'], 'Load option') === false && strpos($response['payload']['data']['html'], 'Load choice') === false, 'Option/choice load buttons are removed');
check(native_ajax($admin, array_replace($ajax_request, ['native_option' => '999999']))['status'] === 400, 'Reject options outside the selected group');
check(native_ajax($admin, array_replace($ajax_request, ['native_choice' => '999999']))['status'] === 400, 'Reject choices outside the selected option');
check(native_ajax($admin, array_replace($ajax_request, ['native_group' => ['bad']]))['status'] === 400, 'Reject malformed AJAX IDs');
$default_response = native_ajax($admin, array_replace($ajax_request, ['native_choice' => '0']));
$current_state = group_state($db, $gid);
check($default_response['payload']['data']['choice'] === (int) array_key_first($current_state['options'][$oid]['choices']), 'Changing option loads its first current choice');
check(all_prices($db) === $ajax_before, 'Loading options/choices never writes prices');

$section_request = ['nonce' => 'test-rackmonk_admin_section', 'section' => 'prices', 'pricing_plan' => '11'];
$can_manage = false;
check(section_ajax($admin, $section_request)['status'] === 403, 'Dashboard section loading requires manage_options');
$can_manage = true; $nonce_ok = false;
check(section_ajax($admin, $section_request)['status'] === 403, 'Dashboard section loading requires its nonce');
$nonce_ok = true;
$price_response = section_ajax($admin, $section_request);
check($price_response['status'] === 200 && strpos($price_response['payload']['data']['html'], 'value="999.00"') !== false && strpos($price_response['payload']['data']['html'], 'value="9.99"') !== false, 'Plan price AJAX returns independent INR and USD for the selected plan');
$resource_response = section_ajax($admin, ['section' => 'resources', 'resource_plan' => '12']);
check($resource_response['status'] === 200 && strpos($resource_response['payload']['data']['html'], 'name="resource_pid" value="12"') !== false, 'Website resources AJAX refreshes save ownership for selected plan');
$group12 = (int) Rackmonk_Resource_Options::get_config($db, 12)['group_id'];
$group_request = ['section' => 'groups', 'config_category' => '2', 'config_plan' => '12', 'native_group' => (string) $group12];
$group_response = section_ajax($admin, $group_request);
check($group_response['status'] === 200 && strpos($group_response['payload']['data']['html'], 'data-native-detail data-native-group="' . $group12 . '"') !== false, 'Group AJAX returns the selected matching native group');
check(section_ajax($admin, ['section' => 'prices', 'pricing_plan' => '99999'])['status'] === 400, 'Reject unknown plan selections');
check(section_ajax($admin, ['section' => 'resources', 'resource_plan' => ['bad']])['status'] === 400, 'Reject malformed plan selections');
check(section_ajax($admin, array_replace($group_request, ['config_category' => '1']))['status'] === 400, 'Reject a plan outside the selected category');
check(section_ajax($admin, array_replace($group_request, ['native_group' => (string) $gid]))['status'] === 400, 'Reject a group outside the active filters');
check(section_ajax($admin, ['section' => 'unknown'])['status'] === 400, 'Reject unknown dashboard sections');
check(all_prices($db) === $ajax_before, 'Loading all dashboard sections does not change existing prices');

$_GET = ['page' => 'rackmonk-whmcs-plans', 'pricing_plan' => 11, 'native_group' => $gid, 'native_option' => $oid, 'native_choice' => $cid]; $_POST = [];
ob_start(); $admin->render_page(); $html = ob_get_clean();
$document = new DOMDocument(); $document->loadHTML($html); $xpath = new DOMXPath($document);
check($xpath->query('//*[@id="configuration-groups"]')->length === 1, 'Dashboard has a native group section');
check($xpath->query('//*[@id="base-plan-prices"]//form[@data-price-currency="INR"]')->length === 1 && $xpath->query('//*[@id="base-plan-prices"]//form[@data-price-currency="USD"]')->length === 1, 'Base-plan editor has distinct INR and USD forms');
check($xpath->query('//form[@data-price-currency="INR"]//input[@name="plan_prices[monthly]" and @value="999.00"]')->length === 1, 'Existing price populates the INR textbox');
check($xpath->query('//form[@data-price-currency="USD"]//input[@name="plan_prices[monthly]" and @value="9.99"]')->length === 1, 'USD textbox loads its independent amount');
check($xpath->query('//input[@name="currency_prices[USD][monthly]"]')->length === 1, 'Create-plan form offers optional USD input');
check($xpath->query('//input[@name="create_complete" and @value="1"]')->length === 1, 'Creation form includes a terminal completeness marker');
check($xpath->query('//*[@id="website-cart-settings" and not(@open)]')->length === 1, 'WordPress-only cart settings are clearly separate and collapsed by default');
check($xpath->query('//input[@name="native_settings[prices][2][annually]"]')->length === 1, 'Native choice editor has USD billing fields');
check(strpos($html, '<script>alert(1)</script>') === false && strpos($html, '&lt;script&gt;') !== false, 'Native group/product labels are escaped');
check(strpos($html, '36 Months') !== false, 'Native pricing uses the actual 36-month triennial term');
check($xpath->query('//select[@data-native-select]')->length === 2, 'Both dropdowns expose automatic AJAX selection controls');
check($xpath->query('//*[@data-native-detail and @data-native-nonce]')->length === 1, 'Rendered detail includes a scoped AJAX nonce and group identity');
check($xpath->query('//select[@data-admin-select]')->length === 5, 'Pricing, website-resource and group filters all support automatic selection');
check(strpos($html, '>Load INR / USD prices<') === false && strpos($html, '>Load resources<') === false && strpos($html, '>Filter groups<') === false, 'No dashboard load or group filter buttons remain');

$ajax_fixtures = [];
foreach (Rackmonk_Configurable_Groups::overview($db) as $group) {
    $group_id = (int) $group['id'];
    foreach (group_state($db, $group_id)['options'] as $option_id => $option_data) {
        foreach (array_merge([0], array_keys($option_data['choices'])) as $choice_id) {
            $fixture = native_ajax($admin, array_replace($ajax_request, ['native_group' => (string) $group_id, 'native_option' => (string) $option_id, 'native_choice' => (string) $choice_id]));
            $ajax_fixtures[$group_id . ':' . $option_id . ':' . $choice_id] = $fixture['payload'];
        }
    }
}
$section_fixtures = [];
foreach (['prices' => 'pricing_plan', 'resources' => 'resource_plan'] as $section => $field) {
    foreach (['11', '12'] as $plan_id) $section_fixtures[$section . ':' . $plan_id] = section_ajax($admin, ['section' => $section, $field => $plan_id])['payload'];
}
foreach ([['0', '0', '0'], ['1', '0', '0'], ['2', '0', '0'], ['2', '12', '0'], ['3', '0', '0']] as [$cat, $plan_id, $group_id]) {
    $section_fixtures['groups:' . $cat . ':' . $plan_id . ':' . $group_id] = section_ajax($admin, ['section' => 'groups', 'config_category' => $cat, 'config_plan' => $plan_id, 'native_group' => $group_id])['payload'];
}
foreach (Rackmonk_Configurable_Groups::overview($db) as $group) {
    $group_id = (string) $group['id'];
    $section_fixtures['groups:0:0:' . $group_id] = section_ajax($admin, ['section' => 'groups', 'config_category' => '0', 'config_plan' => '0', 'native_group' => $group_id])['payload'];
}

foreach ($argv as $argument) if (strpos($argument, '--snapshot=') === 0) {
    $file = substr($argument, strlen('--snapshot='));
    $css = 'file:///' . str_replace('\\', '/', dirname(__DIR__) . '/assets/css/admin.css');
    $js = 'file:///' . str_replace('\\', '/', dirname(__DIR__) . '/assets/js/admin.js');
    file_put_contents($file, '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="' . $css . '"><style>body{margin:0;padding:20px;background:#f0f0f1}input,button,select{font-family:inherit}</style></head><body>' . $html . '<script type="application/json" id="rm-native-test-responses">' . json_encode($ajax_fixtures, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script><script type="application/json" id="rm-section-test-responses">' . json_encode($section_fixtures, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script><script src="' . $js . '"></script></body></html>');
}
echo "Dashboard, native group and independent INR/USD assertions passed; {$checks} cumulative isolated assertions.\nVERDICT: PASS\n";
