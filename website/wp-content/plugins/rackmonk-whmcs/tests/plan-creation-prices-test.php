<?php
/** Standalone creation tests. Only an isolated in-memory SQLite database is used. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity === E_DEPRECATED) return true;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function sanitize_text_field($value) { return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags($value))); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function sanitize_title($value) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($value))), '-'); }
class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
require dirname(__DIR__) . '/includes/class-plans-manager.php';

class PlanCreationTestPDO extends PDO {
    public $writes = [];
    public $transaction_starts = 0;
    public function __construct() {
        parent::__construct('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $this->sqliteCreateFunction('NOW', function () { return '2026-10-31 12:00:00'; });
        $this->sqliteCreateFunction('record_write', function ($table, $currency) { $this->writes[] = [$table, $currency]; return 1; });
        $this->exec('PRAGMA foreign_keys = ON');
    }
    public function beginTransaction(): bool {
        $this->transaction_starts++;
        return parent::beginTransaction();
    }
}
class PlanCreationTestManager extends Rackmonk_Plans_Manager {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function get_whmcs_db() { return $this->db; }
}
$checks = 0;
function creation_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $checks++;
}
function creation_snapshot($db) {
    $tables = [];
    foreach (['tblproducts', 'tblpricing', 'tblcurrencies', 'tblproductgroups'] as $table) {
        $tables[$table] = $db->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll();
    }
    return $tables;
}
function creation_prices($db, $pid) {
    $stmt = $db->prepare("SELECT c.code, p.monthly, p.annually, p.biennially, p.triennially FROM tblpricing p JOIN tblcurrencies c ON c.id = p.currency WHERE p.type = 'product' AND p.relid = ? ORDER BY c.code");
    $stmt->execute([$pid]);
    $prices = [];
    foreach ($stmt->fetchAll() as $row) {
        $code = $row['code'];
        unset($row['code']);
        $prices[$code] = $row;
    }
    return $prices;
}
function creation_rejects($manager, $db, $input, $label) {
    $before = creation_snapshot($db);
    $writes = $db->writes;
    $transactions = $db->transaction_starts;
    $result = $manager->create_whmcs_plan($input);
    creation_check($result instanceof WP_Error, $label . ': return WP_Error');
    creation_check(creation_snapshot($db) === $before, $label . ': leave all tables unchanged');
    creation_check($db->writes === $writes, $label . ': reject before any insert');
    creation_check($db->transaction_starts === $transactions, $label . ': validate before starting writes');
    creation_check(!$db->inTransaction(), $label . ': leave no open transaction');
}

$db = new PlanCreationTestPDO();
foreach ([
    'CREATE TABLE tblproductgroups (id INTEGER PRIMARY KEY, name TEXT)',
    'CREATE TABLE tblcurrencies (id INTEGER PRIMARY KEY, code TEXT UNIQUE, `default` INTEGER, rate REAL)',
    'CREATE TABLE tblproducts (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, gid INTEGER REFERENCES tblproductgroups(id), name TEXT, slug TEXT, description TEXT, tagline TEXT, paytype TEXT, is_featured INTEGER, hidden INTEGER, retired INTEGER, `order` INTEGER, created_at TEXT, updated_at TEXT)',
    'CREATE TABLE tblpricing (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, currency INTEGER REFERENCES tblcurrencies(id), relid INTEGER REFERENCES tblproducts(id), msetupfee TEXT, qsetupfee TEXT, ssetupfee TEXT, asetupfee TEXT, bsetupfee TEXT, tsetupfee TEXT, monthly TEXT, quarterly TEXT, semiannually TEXT, annually TEXT, biennially TEXT, triennially TEXT, UNIQUE(type, currency, relid))',
] as $sql) $db->exec($sql);
$db->exec("INSERT INTO tblproductgroups VALUES (1, 'Shared Hosting'), (4, 'Cloud Hosting')");
$db->exec("INSERT INTO tblcurrencies VALUES (7, 'INR', 1, 1), (2, 'USD', 0, 0.012), (3, 'EUR', 0, 0.011)");
$db->exec("INSERT INTO tblproducts (id, gid, name) VALUES (11, 1, 'Existing plan')");
$db->exec("INSERT INTO tblpricing (type, currency, relid, monthly, annually, biennially, triennially) VALUES ('product', 7, 11, '499.00', '3592.80', '5389.20', '8383.20'), ('product', 2, 11, '5.55', '40.00', '60.00', '90.00')");
$db->exec("CREATE TRIGGER record_product AFTER INSERT ON tblproducts BEGIN SELECT record_write('product', 0); END");
$db->exec("CREATE TRIGGER record_price AFTER INSERT ON tblpricing BEGIN SELECT record_write('pricing', NEW.currency); END");
$manager = new PlanCreationTestManager($db);
$existing_prices = creation_prices($db, 11);
$base = ['gid' => '4', 'name' => '<b>Starter Plan</b>', 'tagline' => '<i>Fast hosting</i>', 'description' => "<b>NVMe storage</b>\nFree SSL", 'monthly' => '249.00', 'annually' => '', 'biennially' => '', 'triennially' => '', 'is_featured' => '1'];

$input = $base;
$input['currency_prices'] = ['USD' => ['monthly' => '9.99', 'annually' => '99.99', 'biennially' => '179.99', 'triennially' => '299.99']];
$pid = $manager->create_whmcs_plan($input);
creation_check(!($pid instanceof WP_Error) && (int) $pid > 11, 'Create product using valid WordPress sanitizer names');
$prices = creation_prices($db, $pid);
creation_check($prices === [
    'INR' => ['monthly' => '249.00', 'annually' => '1792.80', 'biennially' => '2689.20', 'triennially' => '4183.20'],
    'USD' => ['monthly' => '9.99', 'annually' => '99.99', 'biennially' => '179.99', 'triennially' => '299.99'],
], 'Keep independently entered INR/USD prices and existing 12/24/48-month discount formulas');
creation_check(!isset($prices['EUR']), 'Do not copy or convert default prices into an unprovided currency');
$unoffered = $db->query('SELECT quarterly, semiannually FROM tblpricing WHERE relid = ' . (int) $pid)->fetchAll();
foreach ($unoffered as $row) creation_check((float) $row['quarterly'] === -1.0 && (float) $row['semiannually'] === -1.0, 'Unconfigured 3/6-month terms stay disabled, not silently free');
$product = $db->query('SELECT * FROM tblproducts WHERE id = ' . (int) $pid)->fetch();
creation_check($product['name'] === 'Starter Plan' && $product['tagline'] === 'Fast hosting' && $product['description'] === "NVMe storage\nFree SSL", 'Sanitize name, tagline and multiline description');
creation_check($product['slug'] === 'starter-plan' && $product['gid'] === 4 && $product['is_featured'] === 1 && $product['paytype'] === 'recurring', 'Keep compatible product metadata');
creation_check(!$db->inTransaction(), 'Commit successful creation');

foreach ([null, [], ['USD' => []], ['USD' => ['monthly' => '', 'annually' => ' ', 'biennially' => '', 'triennially' => '']]] as $optional) {
    $input = $base;
    if ($optional !== null) $input['currency_prices'] = $optional;
    $pid = $manager->create_whmcs_plan($input);
    creation_check(!($pid instanceof WP_Error), 'Allow omitted or wholly blank optional currency');
    creation_check(array_keys(creation_prices($db, $pid)) === ['INR'], 'Create no USD or EUR row for blank or omitted optional prices');
}

$input = $base;
$input['currency_prices'] = ['USD' => ['monthly' => '9.99']];
$pid = $manager->create_whmcs_plan($input);
creation_check(creation_prices($db, $pid)['USD'] === ['monthly' => '9.99', 'annually' => '71.93', 'biennially' => '107.89', 'triennially' => '167.83'], 'Derive blank USD terms from USD monthly only, rounded to cents');

$input = array_replace($base, ['monthly' => 0, 'annually' => '0', 'biennially' => 0.0, 'triennially' => '0.00']);
$input['currency_prices'] = ['USD' => ['monthly' => '0', 'annually' => '0', 'biennially' => '0', 'triennially' => '0']];
$pid = $manager->create_whmcs_plan($input);
$free = ['monthly' => '0.00', 'annually' => '0.00', 'biennially' => '0.00', 'triennially' => '0.00'];
creation_check(creation_prices($db, $pid) === ['INR' => $free, 'USD' => $free], 'Treat explicit zero amounts as prices, including zero USD monthly');
$input = array_replace($base, ['annually' => 0, 'biennially' => '0', 'triennially' => 0.0]);
$pid = $manager->create_whmcs_plan($input);
creation_check(creation_prices($db, $pid)['INR'] === array_replace($free, ['monthly' => '249.00']), 'Do not replace explicit zero long-term prices with discounted fallback prices');
$input = $base;
$input['currency_prices'] = ['USD' => ['monthly' => 0]];
$pid = $manager->create_whmcs_plan($input);
creation_check(creation_prices($db, $pid)['USD'] === $free, 'Allow zero USD monthly with blank long terms');
$input = array_replace($base, ['monthly' => 249.0, 'annually' => 1792.8, 'biennially' => 2689.2, 'triennially' => 4183.2]);
$pid = $manager->create_whmcs_plan($input);
creation_check(creation_prices($db, $pid)['INR']['monthly'] === '249.00', 'Accept compatible numeric default-currency parameters');
$input = $base;
$input['currency_prices'] = ['USD' => ['monthly' => '9.99'], 'EUR' => ['monthly' => '8.88', 'annually' => '80.00']];
$pid = $manager->create_whmcs_plan($input);
creation_check(array_keys(creation_prices($db, $pid)) === ['EUR', 'INR', 'USD'] && creation_prices($db, $pid)['EUR']['monthly'] === '8.88', 'Support actual configured nondefault currency codes without hardcoded IDs');
creation_check(creation_prices($db, 11) === $existing_prices, 'Retain every existing INR and USD price unchanged');

foreach ([[], '249', null, true] as $bad) creation_rejects($manager, $db, $bad, 'Invalid plan data container');
foreach (['name' => ['', '   ', '<b></b>', [], new stdClass(), true, str_repeat('x', 256)], 'tagline' => [[], true], 'description' => [[], true], 'gid' => [[], '0', '-1', '4x', '4.0', '4e0', '999', true], 'is_featured' => [[], 'on', 2]] as $field => $values) {
    foreach ($values as $bad) creation_rejects($manager, $db, array_replace($base, [$field => $bad]), 'Invalid ' . $field);
}
foreach (['monthly', 'annually', 'biennially', 'triennially'] as $field) {
    foreach ([[], null, true, '-1', '1e3', '1E+2', 'NaN', 'INF', '1.001', '1,000', '+1', '100000000.00'] as $bad) {
        creation_rejects($manager, $db, array_replace($base, [$field => $bad]), 'Invalid default-currency ' . $field);
        $input = $base;
        $input['currency_prices'] = ['USD' => ['monthly' => '9.99', $field => $bad]];
        creation_rejects($manager, $db, $input, 'Invalid USD ' . $field);
    }
}
foreach (['', ' '] as $blank) creation_rejects($manager, $db, array_replace($base, ['monthly' => $blank]), 'Missing default monthly price');
$input = $base;
unset($input['monthly']);
creation_rejects($manager, $db, $input, 'Omitted default monthly price');
foreach (['9.99', null, true] as $bad) creation_rejects($manager, $db, array_replace($base, ['currency_prices' => $bad]), 'Invalid currency map');
foreach ([['USD' => '9.99'], ['USD' => null], ['USD' => ['monthly' => '', 'annually' => '12.00']], ['USD' => ['annually' => '0']], ['USD' => ['monthly' => '9.99', 'quarterly' => '10.00']], ['XXX' => ['monthly' => '1']], ['XXX' => ['monthly' => '']], ['usd' => ['monthly' => '1']], [2 => ['monthly' => '1']], ['INR' => ['monthly' => '1']]] as $bad) {
    creation_rejects($manager, $db, array_replace($base, ['currency_prices' => $bad]), 'Invalid or partial secondary currency submission');
}
creation_rejects($manager, $db, array_replace($base, ['monthly' => '99999999.99']), 'Fallback price exceeding native decimal storage');
$db->exec('UPDATE tblcurrencies SET `default` = 0');
creation_rejects($manager, $db, $base, 'Missing actual default currency');
$db->exec('UPDATE tblcurrencies SET `default` = 1 WHERE id IN (7, 2)');
creation_rejects($manager, $db, $base, 'Ambiguous default currency');
$db->exec('UPDATE tblcurrencies SET `default` = 0 WHERE id = 2');

$db->exec("CREATE TRIGGER fail_usd BEFORE INSERT ON tblpricing WHEN NEW.currency = 2 BEGIN SELECT RAISE(ABORT, 'secondary currency insert failure'); END");
foreach ([PDO::ERRMODE_EXCEPTION, PDO::ERRMODE_SILENT] as $mode) {
    $db->setAttribute(PDO::ATTR_ERRMODE, $mode);
    $before = creation_snapshot($db);
    $write_count = count($db->writes);
    $input = $base;
    $input['currency_prices'] = ['USD' => ['monthly' => '9.99']];
    $result = $manager->create_whmcs_plan($input);
    creation_check($result instanceof WP_Error, 'Return WP_Error on secondary currency insert failure');
    creation_check(array_slice($db->writes, $write_count) === [['product', 0], ['pricing', 7]], 'Reach secondary currency failure only after product and default price inserts');
    creation_check(creation_snapshot($db) === $before, 'Rollback product and already-inserted INR row on USD failure');
    creation_check(!$db->inTransaction(), 'Close failed creation transaction');
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('DROP TRIGGER fail_usd');
creation_check((new PlanCreationTestManager(false))->create_whmcs_plan($base) instanceof WP_Error, 'Report unavailable database without live connection fallback');

echo "$checks plan creation assertions passed in isolated SQLite. No live database or WordPress bootstrap.\nVERDICT: PASS\n";
