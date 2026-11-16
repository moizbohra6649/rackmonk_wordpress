<?php
/** Run with PHP CLI. Uses only an isolated in-memory SQLite database. */
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', __DIR__);
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function absint($value) { return abs((int) $value); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
function selected($a, $b) { if ((string) $a === (string) $b) echo 'selected'; }
function disabled($value) { if ($value) echo 'disabled'; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="test-nonce">'; }
require dirname(__DIR__) . '/includes/class-resource-options.php';

class ResourceTestPDO extends PDO {
    public function __construct() {
        parent::__construct('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $this->sqliteCreateFunction('CONCAT', function(...$values) { return implode('', $values); });
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}
$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $checks++;
}
function rejects($callback, $message) {
    try { $callback(); } catch (Throwable $error) { check(true, $message); return; }
    check(false, $message);
}
function version_of($db, $pid = 11) { return hash('sha256', serialize(Rackmonk_Resource_Options::get_config($db, $pid))); }

$db = new ResourceTestPDO();
foreach ([
    'CREATE TABLE tblproducts (id INTEGER PRIMARY KEY)',
    'CREATE TABLE tblcurrencies (id INTEGER PRIMARY KEY, rate REAL, `default` INTEGER)',
    'CREATE TABLE tblproductconfiggroups (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT)',
    'CREATE TABLE tblproductconfiglinks (id INTEGER PRIMARY KEY AUTOINCREMENT, gid INTEGER, pid INTEGER)',
    'CREATE TABLE tblproductconfigoptions (id INTEGER PRIMARY KEY AUTOINCREMENT, gid INTEGER, optionname TEXT, optiontype INTEGER, qtyminimum INTEGER, qtymaximum INTEGER, `order` INTEGER, hidden INTEGER)',
    'CREATE TABLE tblproductconfigoptionssub (id INTEGER PRIMARY KEY AUTOINCREMENT, configid INTEGER, optionname TEXT, sortorder INTEGER, hidden INTEGER)',
    'CREATE TABLE tblpricing (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, currency INTEGER, relid INTEGER, msetupfee REAL, qsetupfee REAL, ssetupfee REAL, asetupfee REAL, bsetupfee REAL, tsetupfee REAL, monthly REAL, quarterly REAL, semiannually REAL, annually REAL, biennially REAL, triennially REAL)',
] as $sql) $db->exec($sql);
$db->exec('INSERT INTO tblproducts VALUES (11), (12), (13)');
$db->exec('INSERT INTO tblcurrencies VALUES (1, 1, 1), (2, 0.012, 0)');
$initial = Rackmonk_Resource_Options::get_config($db, 11);
check($initial === ['group_id' => 0, 'resources' => []], 'Unconfigured product starts empty');

foreach (['-1', '1e3', 'NaN', '10.001', '10000000', '', '1,000'] as $bad) {
    rejects(function() use ($bad, $initial) {
        Rackmonk_Resource_Options::validate(['cpu' => [['label' => '2 vCPU', 'price' => $bad]]], $initial);
    }, 'Reject invalid price: ' . $bad);
}
rejects(function() use ($initial) { Rackmonk_Resource_Options::validate(['cpu' => [['label' => '2|vCPU', 'price' => '0']]], $initial); }, 'Reject WHMCS pipe syntax in labels');
rejects(function() use ($initial) { Rackmonk_Resource_Options::validate(['cpu' => [['label' => ['bad'], 'price' => '0']]], $initial); }, 'Reject array injection');
rejects(function() use ($initial) { Rackmonk_Resource_Options::validate(['cpu' => [['id' => '1x', 'label' => 'CPU', 'price' => '0']]], $initial); }, 'Reject nonnumeric choice IDs');
rejects(function() use ($initial) { Rackmonk_Resource_Options::validate(['cpu' => array_fill(0, 31, ['label' => 'CPU', 'price' => '0'])], $initial); }, 'Limit choices to 30 per resource');

$input = [
    'cpu' => [['label' => '2 vCPU', 'price' => '0'], ['label' => '4 vCPU', 'price' => '100.25']],
    'ram' => [['label' => '4 GB RAM', 'price' => '0'], ['label' => '8 GB RAM', 'price' => '200']],
];
$version = version_of($db);
Rackmonk_Resource_Options::save($db, 11, $input, $version);
$config = Rackmonk_Resource_Options::get_config($db, 11);
check($config['group_id'] > 0, 'Create a native configurable group');
check(count($config['resources']) === 2, 'Create configured resources only');
check((int) $db->query('SELECT COUNT(*) FROM tblpricing')->fetchColumn() === 8, 'Price every choice in every currency');
$cpu = $config['resources']['cpu']['choices'][1];
$rate = $db->query("SELECT * FROM tblpricing WHERE relid = {$cpu['id']} AND currency = 1")->fetch();
check((float) $rate['monthly'] === 100.25, 'Persist exact default currency monthly rate');
check((float) $rate['annually'] === 1203.0, 'Annual resource price is monthly times 12');
check((float) $rate['triennially'] === 3609.0, 'Native triennial term uses 36, not 48 months');
$converted = $db->query("SELECT monthly FROM tblpricing WHERE relid = {$cpu['id']} AND currency = 2")->fetchColumn();
check((float) $converted === 1.2, 'Convert and round using actual WHMCS currency rates');
check(!Rackmonk_Resource_Options::is_configurable($db, 12), 'Unrelated product does not get custom checkout');
check(Rackmonk_Resource_Options::is_configurable($db, 11), 'Configured product gets custom checkout');
rejects(function() use ($db, $input, $version) { Rackmonk_Resource_Options::save($db, 11, $input, $version); }, 'Reject stale editor versions');
check(!$db->inTransaction(), 'Rollback failed updates');
rejects(function() use ($config, $cpu) { Rackmonk_Resource_Options::validate(['ram' => [['id' => $cpu['id'], 'label' => 'Stolen option', 'price' => '0']]], $config); }, 'Reject choice ID from another resource');
rejects(function() use ($config, $cpu) { Rackmonk_Resource_Options::validate(['cpu' => [$cpu, $cpu]], $config); }, 'Reject duplicate choice IDs');
rejects(function() use ($initial) { Rackmonk_Resource_Options::validate(['cpu' => [['label' => 'CPU', 'price' => '0'], ['label' => 'cpu', 'price' => '10']]], $initial); }, 'Reject duplicate labels');

$db->exec("UPDATE tblpricing SET msetupfee = 7 WHERE relid = {$cpu['id']} AND currency = 1");
$config = Rackmonk_Resource_Options::get_config($db, 11);
$keep = ['cpu' => [['id' => $cpu['id'], 'label' => '4 vCPU', 'price' => '150']]];
Rackmonk_Resource_Options::save($db, 11, $keep, version_of($db));
check((int) $db->query('SELECT COUNT(*) FROM tblproductconfigoptionssub')->fetchColumn() === 4, 'Keep removed choices for historical services');
check((int) $db->query('SELECT COUNT(*) FROM tblproductconfigoptionssub WHERE hidden = 0')->fetchColumn() === 1, 'Only retained choice remains available');
check((int) $db->query("SELECT hidden FROM tblproductconfigoptions WHERE optionname = 'Memory'")->fetchColumn() === 1, 'Hide emptied resources');
check((float) $db->query("SELECT msetupfee FROM tblpricing WHERE relid = {$cpu['id']} AND currency = 1")->fetchColumn() === 7.0, 'Preserve existing setup fees');

$db->exec("INSERT INTO tblproductconfiglinks (gid, pid) VALUES ({$config['group_id']}, 12)");
rejects(function() use ($db, $keep) { Rackmonk_Resource_Options::save($db, 11, $keep, version_of($db)); }, 'Refuse writes to groups shared with another product');
$db->exec('DELETE FROM tblproductconfiglinks WHERE pid = 12');

// Fail after writes have begun and confirm the whole edit is rolled back.
$before = $db->query('SELECT optionname FROM tblproductconfigoptionssub ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$db->exec('CREATE TRIGGER fail_pricing BEFORE UPDATE ON tblpricing BEGIN SELECT RAISE(ABORT, \'test price failure\'); END');
$changed = ['cpu' => [['id' => $cpu['id'], 'label' => 'Changed name', 'price' => '500']]];
rejects(function() use ($db, $changed) { Rackmonk_Resource_Options::save($db, 11, $changed, version_of($db)); }, 'Fail atomically on pricing errors');
check($db->query('SELECT optionname FROM tblproductconfigoptionssub ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) === $before, 'Rollback labels and hidden state after pricing failure');
$db->exec('DROP TRIGGER fail_pricing');

$db->exec("UPDATE tblproductconfigoptions SET optiontype = 4, qtymaximum = 10 WHERE optionname = 'CPU'");
rejects(function() use ($db) { Rackmonk_Resource_Options::get_config($db, 11); }, 'Reject incompatible native quantity options');
$db->exec("UPDATE tblproductconfigoptions SET optiontype = 1, qtymaximum = 0 WHERE optionname = 'CPU'");
$old_version = version_of($db);
$db->exec("UPDATE tblpricing SET annually = 9999 WHERE relid = {$cpu['id']} AND currency = 2");
rejects(function() use ($db, $keep, $old_version) { Rackmonk_Resource_Options::save($db, 11, $keep, $old_version); }, 'Detect external term or secondary-currency price edits');

$_GET['resource_plan'] = 11;
ob_start();
Rackmonk_Resource_Options::render_editor([['id' => 11, 'name' => 'Custom VPS']], $db, ['code' => 'INR'], 'https://billing.example/');
$html = ob_get_clean();
$document = new DOMDocument();
libxml_use_internal_errors(true);
$document->loadHTML($html);
$xpath = new DOMXPath($document);
check($xpath->query('//*[@data-resource]')->length === 14, 'Render the twelve screenshot fields plus optional CPU and websites');
check($xpath->query('//*[@name="resource_version"]')->length === 1, 'Render optimistic concurrency token');
check(strpos($html, 'carttpl=standard_cart') !== false, 'Preview routes to styled native checkout');
check(strpos($html, 'value="4 vCPU"') !== false, 'Render persisted resource label');
check(strpos($html, 'value="Changed name"') === false, 'Do not expose rolled-back values');

$screenshot_names = ['Operating System', 'Memory', 'Primary Disk', 'Secondary Disk', 'Backup Disk (Recommend)', 'RAID Configuration', 'Control Panel', 'Premium Antivirus', 'Firewall', 'IP Addresses', 'Port Speed', 'DataTransfer'];
$input = [];
foreach (array_slice(Rackmonk_Resource_Options::RESOURCES, 0, 12) as $key => $name) $input[$key] = [['label' => 'Included', 'price' => '0']];
$input['os'] = [
    ['label' => 'Linux (64-bit)', 'price' => '0', 'hidden' => true],
    ['label' => 'Ubuntu 22.04 (64-bit)', 'price' => '0'],
    ['label' => 'Windows', 'price' => '0', 'hidden' => true],
    ['label' => 'Ubuntu 20.04 (64-bit)', 'price' => '0'],
];
Rackmonk_Resource_Options::save($db, 12, $input, version_of($db, 12), 'Screenshot options (Demo)');
$native = Rackmonk_Resource_Options::get_config($db, 12);
$names = $db->query('SELECT optionname FROM tblproductconfigoptions WHERE gid = ' . $native['group_id'] . ' ORDER BY `order`')->fetchAll(PDO::FETCH_COLUMN);
check($names === $screenshot_names, 'Native options match all screenshot names and sort order');
check(count($native['resources']) === 12, 'Create twelve native dropdowns without unrelated legacy fields');
check($db->query('SELECT name FROM tblproductconfiggroups WHERE id = ' . $native['group_id'])->fetchColumn() === 'Screenshot options (Demo)', 'Keep a clear native group name');
$operating_systems = $native['resources']['os']['choices'];
check($operating_systems[0]['hidden'] && $operating_systems[2]['hidden'] && !$operating_systems[1]['hidden'] && !$operating_systems[3]['hidden'], 'Match Linux/Windows hide flags and visible Ubuntu versions');
check(count($native['native_prices']) === 30, 'Price all fifteen choices in both currencies');
foreach ($native['native_prices'] as $prices) {
    foreach (['msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'tsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'] as $column) check((float) $prices[$column] === 0.0, 'Included choices have zero setup/recurring cost for every currency and cycle');
}
rejects(function () use ($initial) { Rackmonk_Resource_Options::validate(['os' => [['label' => 'Hidden only', 'price' => '0', 'hidden' => true]]], $initial); }, 'Require a visible native option choice');
rejects(function () use ($initial) { Rackmonk_Resource_Options::validate(['os' => [['label' => 'OS', 'price' => '0', 'hidden' => ['bad']]]], $initial); }, 'Reject invalid visibility flags');
$db->exec("INSERT INTO tblproductconfiggroups (id, name, description) VALUES (99, 'Existing manual group', 'Unmanaged')");
$db->exec('INSERT INTO tblproductconfiglinks (gid, pid) VALUES (99, 13)');
$db->exec("INSERT INTO tblproductconfigoptions (gid, optionname, optiontype, qtyminimum, qtymaximum, `order`, hidden) VALUES (99, 'Manual OS', 1, 0, 0, 0, 0)");
$group_count = $db->query('SELECT COUNT(*) FROM tblproductconfiggroups')->fetchColumn();
rejects(function () use ($db, $input) { Rackmonk_Resource_Options::save($db, 13, $input, version_of($db, 13)); }, 'Do not duplicate a product with manually linked native option groups');
check($db->query('SELECT COUNT(*) FROM tblproductconfiggroups')->fetchColumn() === $group_count, 'Refusing an unmanaged product leaves native groups unchanged');
$db->exec('INSERT INTO tblproducts VALUES (14)');
$db->exec("INSERT INTO tblproductconfiggroups (id, name, description) VALUES (100, 'Empty existing group', '')");
$db->exec('INSERT INTO tblproductconfiglinks (gid, pid) VALUES (100, 14)');
Rackmonk_Resource_Options::save($db, 14, $input, version_of($db, 14));
check((int) $db->query('SELECT COUNT(*) FROM tblproductconfiglinks WHERE pid = 14')->fetchColumn() === 2, 'Allow separate per-plan options while retaining an existing empty linked group');
check((int) $db->query('SELECT COUNT(*) FROM tblproductconfigoptions WHERE gid = 100')->fetchColumn() === 0, 'Existing empty native group is never populated or overwritten');

echo "$checks resource configuration assertions passed in isolated SQLite. No live products or orders changed.\nVERDICT: PASS\n";
