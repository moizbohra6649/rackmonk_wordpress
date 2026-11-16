<?php
// Standalone, no WordPress bootstrap and no account-creation calls.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Synthetic subprocess fixture; it never loads WHMCS.
if (getenv('RACKMONK_CLIENT_API_FIXTURE')) {
    $request = json_decode(stream_get_contents(STDIN), true);
    $mode = getenv('RACKMONK_CLIENT_API_FIXTURE');
    if ($mode === 'timeout') { sleep(4); exit; }
    if ($mode === 'noise') { echo str_repeat('x', 70000); exit; }
    if ($mode === 'invalid') { echo 'bootstrap noise'; exit; }
    if ($mode === 'fatal') exit(3);
    if ($mode === 'validation') { echo '{"result":"error","kind":"validation"}'; exit; }
    if ($mode === 'success' && $request['command'] === 'AddClient') {
        $p = $request['params'];
        $safe = !isset($p['owner_user_id']) && $p['noemail'] === true && $p['skipvalidation'] === false && $p['notes'] === 'Rackmonk registration reference: fixture-registration-reference';
        echo $safe ? '{"result":"success","clientid":11}' : '{}';
        exit;
    }
    if ($mode === 'sso') { echo '{"result":"success","redirect_url":"https://billing.example.test/oauth/singlesignon.php?access_token=synthetic"}'; exit; }
    if ($mode === 'unsafe_sso') { echo '{"result":"success","redirect_url":"https://attacker.example.test/"}'; exit; }
    echo '{"result":"success","version":"fixture"}';
    exit;
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
if (in_array('--inspect', $argv, true) || in_array('--health', $argv, true) || in_array('--schema', $argv, true)) {
    require dirname(__DIR__) . '/includes/class-plans-manager.php';
} else {
    class Rackmonk_Plans_Manager {
        public static $db;
        public static function get_instance() { return new self(); }
        public function get_whmcs_db() { return self::$db; }
        public function get_whmcs_system_url() { return 'https://billing.example.test/'; }
    }
}
class WP_Error {
    public $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_current_user_id() { return $GLOBALS['actor'] ?? 7; }
function get_user_meta($id, $key, $single) { return $GLOBALS['meta'][$key] ?? ''; }
require dirname(__DIR__) . '/includes/class-whmcs-client-api.php';
if (in_array('--schema', $argv, true)) {
    $schema = Rackmonk_WHMCS_Client_API::schema();
    if (is_wp_error($schema)) { echo 'Schema unavailable' . PHP_EOL; exit(1); }
    echo 'Schema read-only success; fields=' . count($schema['custom_fields']) . '; questions=' . count($schema['security_questions']) . '; currencies=' . count($schema['currencies']) . PHP_EOL;
    exit;
}
if (in_array('--health', $argv, true)) {
    $result = Rackmonk_WHMCS_Client_API::health();
    echo is_wp_error($result) ? 'Health failed: ' . $result->get_error_code() . PHP_EOL : 'Health success: Version ' . $result['version'] . PHP_EOL;
    exit(is_wp_error($result) ? 1 : 0);
}
if (in_array('--inspect', $argv, true)) {
    $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
    foreach (['tblusers', 'tblusers_clients', 'tblclients', 'tbladminsecurityquestions', 'tblcustomfields'] as $table) {
        echo $table . ': ' . implode(',', $pdo->query('SHOW COLUMNS FROM ' . $table)->fetchAll(PDO::FETCH_COLUMN)) . PHP_EOL;
    }
    echo 'CONFIG KEYS: ' . implode(',', $pdo->query("SELECT setting FROM tblconfiguration WHERE setting LIKE '%Client%' OR setting LIKE '%Country%' OR setting LIKE '%SecurityQuestion%' OR setting LIKE '%Optional%' OR setting LIKE '%Strength%' OR setting LIKE '%Marketing%' OR setting LIKE '%Tax%'")->fetchAll(PDO::FETCH_COLUMN)) . PHP_EOL;
    exit;
}

class ClientApiFixtureDb {
    public $ids = [11];
    public $existing = false;
    public $duplicateOwners = false;
    public $missingColumns = false;
    public $row = ['client_id' => 11, 'user_id' => 22, 'email' => 'fixture@example.test', 'status' => 'Active', 'allow_sso' => 1, 'second_factor' => '', 'second_factor_config' => ''];
    public function prepare($sql) { return new ClientApiFixtureStatement($this, $sql); }
    public function query($sql) { return $this->prepare($sql); }
}
class ClientApiFixtureStatement {
    private $db;
    private $sql;
    public function __construct($db, $sql) {
        if (!preg_match('/\A(?:SELECT |SHOW COLUMNS )/', $sql)) throw new RuntimeException('Non-read SQL forbidden');
        $this->db = $db; $this->sql = $sql;
    }
    public function execute($args = []) {
        if (str_contains($this->sql, 'BINARY notes') && ($args[0] ?? '') !== 'Rackmonk registration reference: fixture-registration-reference') $this->db->ids = [];
    }
    public function fetchColumn() { return $this->db->existing ? ($this->db->ids[0] ?? false) : false; }
    public function fetchAll($mode = null) {
        if (str_contains($this->sql, 'tblconfiguration')) return ['AllowClientRegister' => 'on', 'DefaultCountry' => 'IN', 'OptionalClientFields' => 'state,password,SecretKey', 'RequiredPWStrength' => '60'];
        if (str_contains($this->sql, 'SHOW COLUMNS')) {
            return $this->db->missingColumns ? [] : ['id','auth_user_id','client_id','owner','email','second_factor','second_factor_config','notes','status','allow_sso'];
        }
        if (str_contains($this->sql, 'BINARY notes')) return $this->db->ids;
        if (str_contains($this->sql, 'JOIN tblusers_clients')) return $this->db->duplicateOwners ? [$this->db->row, $this->db->row] : [$this->db->row];
        if (str_contains($this->sql, 'tblcustomfields')) return [
            ['id'=>3,'fieldname'=>'<b>Label</b>','fieldtype'=>'text','description'=>'<i>Info</i>','fieldoptions'=>'','required'=>'on','showorder'=>''],
            ['id'=>4,'fieldname'=>'Hidden','fieldtype'=>'text','description'=>'','fieldoptions'=>'','required'=>'','showorder'=>''],
        ];
        if (str_contains($this->sql, 'tbladminsecurityquestions')) return [['id'=>1,'question'=>'<b>Question</b>']];
        if (str_contains($this->sql, 'tblcurrencies')) return [['id'=>1,'code'=>'INR','prefix'=>'','suffix'=>'','default'=>1]];
        throw new RuntimeException('Unexpected SQL');
    }
}
Rackmonk_Plans_Manager::$db = $db = new ClientApiFixtureDb();
$count = 0;
function check($condition, $label) {
    global $count;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $count++;
}
function error_code($result, $code) { return is_wp_error($result) && $result->get_error_code() === $code; }
$ref = 'fixture-registration-reference';
$api = Rackmonk_WHMCS_Client_API::class;
$schema = $api::schema();
check($schema['registration_enabled'] === true, 'native registration toggle');
check($schema['minimum_password_strength'] === 60, 'native password threshold');
check($schema['default_country'] === 'IN', 'native default country');
check($schema['optional_fields'] === ['companyname','address2','tax_id','state'], 'only safe optional fields');
check(count($schema['custom_fields']) === 1 && $schema['custom_fields'][0]['fieldname'] === 'Label', 'only registration custom fields and plain labels');
check(!isset($schema['custom_fields'][0]['regexpr']), 'no public regex');
check($schema['security_questions'][0]['question'] === 'Question', 'plain security question');
check($schema['currencies'][0]['id'] === 1, 'currencies available');
check($api::recover_registration($ref, 'fixture@example.test') === ['result'=>'success','client_id'=>11,'user_id'=>22], 'unique reference owner');
check(error_code($api::recover_registration($ref, 'other@example.test'), 'whmcs_recovery'), 'email cannot link existing owner');
$db->ids = [11,12];
check(error_code($api::recover_registration($ref, 'fixture@example.test'), 'whmcs_recovery'), 'duplicate reference rejected');
$db->ids = [11]; $db->duplicateOwners = true;
check(error_code($api::recover_registration($ref, 'fixture@example.test'), 'whmcs_recovery'), 'duplicate owner rejected');
$db->duplicateOwners = false; $db->missingColumns = true;
check(error_code($api::recover_registration($ref, 'fixture@example.test'), 'whmcs_recovery'), 'unknown identity schema rejected');
$db->missingColumns = false;
check(error_code($api::recover_registration('short', 'fixture@example.test'), 'whmcs_validation'), 'short token rejected');
check(error_code($api::register_client(['email'=>'fixture@example.test'], $ref), 'whmcs_validation'), 'password required before subprocess');
$GLOBALS['meta'] = ['_rackmonk_whmcs_sync_status'=>'pending','_rackmonk_whmcs_client_id'=>11,'_rackmonk_whmcs_user_id'=>22];
check(error_code($api::sso(7), 'whmcs_sso'), 'pending cannot SSO');
$GLOBALS['meta']['_rackmonk_whmcs_sync_status'] = 'active';
check(error_code($api::sso(8), 'whmcs_sso'), 'different actor cannot SSO');
$GLOBALS['meta']['_rackmonk_whmcs_user_id'] = 23;
check(error_code($api::sso(7), 'whmcs_sso'), 'forged user mapping cannot SSO');
$GLOBALS['meta']['_rackmonk_whmcs_user_id'] = 22;
foreach (['second_factor'=>'totp','second_factor_config'=>'encrypted','status'=>'Closed','allow_sso'=>0] as $key => $value) {
    $old = $db->row[$key]; $db->row[$key] = $value;
    check(error_code($api::sso(7), 'whmcs_sso'), 'SSO blocked by ' . $key);
    $db->row[$key] = $old;
}
$origin = new ReflectionMethod($api, 'same_origin');
foreach (['https://billing.example.test/oauth/?token=synthetic','https://billing.example.test:443/path'] as $url) check($origin->invoke(null, $url, 'https://billing.example.test/') === true, 'same origin accepted');
foreach (['http://billing.example.test/','https://billing.example.test.attacker.test/','https://billing.example.test:444/','https://user@billing.example.test/','//billing.example.test/','javascript:alert(1)',"https://billing.example.test/\nLocation: bad",'https://billing.example.test/\\attacker'] as $url) check($origin->invoke(null, $url, 'https://billing.example.test/') === false, 'unsafe origin rejected');

// Test the real transport algorithm against an in-memory copy with only the
// fixed bridge path and deadline changed. Production has no injectable runner.
$source = file_get_contents(dirname(__DIR__) . '/includes/class-whmcs-client-api.php');
$source = str_replace('class Rackmonk_WHMCS_Client_API {', 'class ClientApiTransportFixture {', $source);
$source = str_replace("\$bridge = dirname(__DIR__) . '/bridge/whmcs-client-cli.php';", '$bridge = ' . var_export(__FILE__, true) . ';', $source);
$source = str_replace('private const TIMEOUT = 25;', 'private const TIMEOUT = 1;', $source);
eval(substr($source, 5));
$request = new ReflectionMethod(ClientApiTransportFixture::class, 'request');
putenv('RACKMONK_CLIENT_API_FIXTURE=success');
check(ClientApiTransportFixture::health()['version'] === 'fixture', 'subprocess JSON health');
$data = ['email'=>'fixture@example.test','password2'=>'synthetic-not-a-live-password','owner_user_id'=>999,'skipvalidation'=>true,'notes'=>'caller-controlled','customfields'=>[3=>'fixture']];
check(ClientApiTransportFixture::register_client($data, $ref)['client_id'] === 11, 'supported AddClient params and owner resolution against synthetic subprocess only');
$db->row['client_id'] = 12;
check(error_code(ClientApiTransportFixture::register_client($data, $ref), 'whmcs_transport'), 'created client mismatch remains uncertain');
$db->row['client_id'] = 11;
$db->existing = true;
check($api::register_client($data, $ref)['user_id'] === 22, 'existing reference recovers without another API write');
$db->row['email'] = 'other@example.test';
check(error_code($api::register_client($data, $ref), 'whmcs_transport'), 'unconfirmed existing reference preserves pending registration');
$db->row['email'] = 'fixture@example.test'; $db->existing = false;
putenv('RACKMONK_CLIENT_API_FIXTURE=validation');
check(error_code(ClientApiTransportFixture::register_client($data, $ref), 'whmcs_validation'), 'definite AddClient validation distinguished');
putenv('RACKMONK_CLIENT_API_FIXTURE=sso');
check(ClientApiTransportFixture::sso(7)['result'] === 'success', 'verified SSO mapping');
putenv('RACKMONK_CLIENT_API_FIXTURE=unsafe_sso');
check(error_code(ClientApiTransportFixture::sso(7), 'whmcs_sso'), 'cross origin API redirect rejected');
foreach (['invalid','fatal','timeout','noise'] as $mode) {
    putenv('RACKMONK_CLIENT_API_FIXTURE=' . $mode);
    $start = microtime(true);
    check(error_code(ClientApiTransportFixture::health(), 'whmcs_transport'), $mode . ' fails closed');
    check(microtime(true) - $start < 2.5, $mode . ' bounded deadline');
}
putenv('RACKMONK_CLIENT_API_FIXTURE');
check(error_code($request->invoke(null, 'ValidateLogin', []), 'whmcs_transport'), 'login validation never allowlisted');
check(error_code($request->invoke(null, 'UpdateClient', []), 'whmcs_transport'), 'password update never allowlisted');
check(error_code($request->invoke(null, 'GetConfigurationValue', ['setting'=>str_repeat('x',65537)]), 'whmcs_transport'), 'oversized request rejected before process creation');

// Exercise the actual bridge only with requests rejected before WHMCS bootstrap.
foreach (['{"command":"ValidateLogin","params":{}}','{"command":"GetConfigurationValue","params":{"setting":"Secret"}}','{"command":"AddClient","params":{"owner_user_id":99}}',str_repeat('x',65537)] as $input) {
    $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/bridge/whmcs-client-cli.php'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
    fwrite($pipes[0], $input); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
    check(json_decode($output, true) === ['result'=>'error','kind'=>'transport'] && $error === '', 'bridge rejects unauthorized or oversized input generically');
}
echo $count . ' assertions passed; no live account creation.' . PHP_EOL;
echo 'VERDICT: PASS' . PHP_EOL;
