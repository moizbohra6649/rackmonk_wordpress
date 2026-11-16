<?php
/** One-time, additive CLI import. Preview by default; --apply is required for native writes. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (array_diff(array_slice($argv, 1), ['--dry-run', '--apply'])) {
    fwrite(STDERR, "Usage: php sync-native-options.php [--dry-run|--apply]\n");
    exit(1);
}
define('DISABLE_WP_CRON', true);
require dirname(__DIR__, 3) . '/wp-load.php';
require_once __DIR__ . '/includes/class-resource-options.php';

try {
    $apply = in_array('--apply', $argv, true);
    $manager = Rackmonk_Plans_Manager::get_instance();
    $db = $manager->get_whmcs_db();
    if (!$db) throw new RuntimeException('WHMCS connection is unavailable.');
    $currency = $manager->get_default_currency();
    $products = $db->query('SELECT id, name FROM tblproducts WHERE hidden = 0 AND retired = 0 ORDER BY id')->fetchAll();
    $protected_snapshot = function () use ($db) {
        return hash('sha256', json_encode([
            $db->query("SELECT * FROM tblpricing WHERE type = 'product' ORDER BY id")->fetchAll(),
            $db->query('SELECT * FROM tblproducts ORDER BY id')->fetchAll(),
            $db->query('SELECT COUNT(*) FROM tblorders')->fetchColumn(),
            $db->query('SELECT COUNT(*) FROM tblinvoices')->fetchColumn(),
            $db->query('SELECT COUNT(*) FROM tblhosting')->fetchColumn(),
        ]));
    };
    $before = $protected_snapshot();
    $original_ids = array_map('intval', $db->query('SELECT id FROM tblproductconfiggroups')->fetchAll(PDO::FETCH_COLUMN));
    $original_snapshot = function () use ($db, $original_ids) {
        if (!$original_ids) return hash('sha256', 'no original groups');
        $ids = implode(',', $original_ids);
        return hash('sha256', json_encode([
            $db->query('SELECT * FROM tblproductconfiggroups WHERE id IN (' . $ids . ') ORDER BY id')->fetchAll(),
            $db->query('SELECT * FROM tblproductconfiglinks WHERE gid IN (' . $ids . ') ORDER BY id')->fetchAll(),
            $db->query('SELECT * FROM tblproductconfigoptions WHERE gid IN (' . $ids . ') ORDER BY id')->fetchAll(),
            $db->query('SELECT s.* FROM tblproductconfigoptionssub s JOIN tblproductconfigoptions o ON o.id = s.configid WHERE o.gid IN (' . $ids . ') ORDER BY s.id')->fetchAll(),
            $db->query("SELECT pr.* FROM tblpricing pr JOIN tblproductconfigoptionssub s ON s.id = pr.relid JOIN tblproductconfigoptions o ON o.id = s.configid WHERE pr.type = 'configoptions' AND o.gid IN (" . $ids . ') ORDER BY pr.id')->fetchAll(),
        ]));
    };
    $original_before = $original_snapshot();
    $pending = [];
    $report = [];
    foreach ($products as $product) {
        $pid = (int) $product['id'];
        $source = Rackmonk_Website_Cart::get_config($pid);
        if (!$source || empty($source['enabled'])) continue;
        $linked = $db->prepare('SELECT l.gid, (SELECT COUNT(*) FROM tblproductconfigoptions o WHERE o.gid = l.gid) AS option_count FROM tblproductconfiglinks l WHERE l.pid = ?');
        $linked->execute([$pid]);
        $existing = $linked->fetchAll(PDO::FETCH_ASSOC);
        if (array_filter($existing, function ($group) { return (int) $group['option_count'] > 0; })) {
            $report[] = ['pid' => $pid, 'name' => $product['name'], 'status' => 'existing native groups preserved', 'group_ids' => array_map('intval', array_column($existing, 'gid'))];
            continue;
        }
        if ($source['currency'] !== $currency['code']) throw new RuntimeException('Plan #' . $pid . ': source currency differs from the WHMCS default currency.');
        $input = [];
        foreach (array_slice(array_keys(Rackmonk_Resource_Options::RESOURCES), 0, 12) as $key) {
            if (empty($source['resources'][$key]['choices'])) throw new RuntimeException('Plan #' . $pid . ': missing ' . $key . ' choices.');
            $input[$key] = array_map(function ($choice) { return ['id' => 0, 'label' => $choice['label'], 'price' => $choice['price'], 'hidden' => false]; }, $source['resources'][$key]['choices']);
        }
        // Match the second screenshot: free Ubuntu choices, with Linux/Windows retained but hidden.
        $input['os'] = [
            ['label' => 'Linux (64-bit)', 'price' => '0.00', 'hidden' => true],
            ['label' => 'Ubuntu 22.04 (64-bit)', 'price' => '0.00', 'hidden' => false],
            ['label' => 'Windows', 'price' => '0.00', 'hidden' => true],
            ['label' => 'Ubuntu 20.04 (64-bit)', 'price' => '0.00', 'hidden' => false],
        ];
        $native = Rackmonk_Resource_Options::get_config($db, $pid);
        Rackmonk_Resource_Options::validate($input, $native);
        $pending[] = ['pid' => $pid, 'name' => $product['name'], 'input' => $input, 'native_version' => hash('sha256', serialize($native)), 'source_version' => Rackmonk_Website_Cart::revision($source), 'demo' => !empty($source['demo_pricing']), 'preserved_empty_groups' => array_map('intval', array_column($existing, 'gid'))];
    }
    foreach ($pending as $entry) {
        $pid = $entry['pid'];
        $row = ['pid' => $pid, 'name' => $entry['name'], 'options' => 12, 'choices' => array_sum(array_map('count', $entry['input'])), 'demo_upgrade_prices' => $entry['demo'], 'preserved_empty_groups' => $entry['preserved_empty_groups'], 'status' => $apply ? 'created' : 'dry run'];
        if ($apply) {
            if (!hash_equals($entry['source_version'], Rackmonk_Website_Cart::revision(Rackmonk_Website_Cart::get_config($pid)))) throw new RuntimeException('Plan #' . $pid . ' changed during preflight. Run the preview again.');
            $name = 'Rackmonk - ' . $entry['name'] . ($entry['demo'] ? ' (Demo upgrade prices)' : '');
            Rackmonk_Resource_Options::save($db, $pid, $entry['input'], $entry['native_version'], $name);
            $native = Rackmonk_Resource_Options::get_config($db, $pid);
            $row['group_id'] = $native['group_id'];
            $row['admin_url'] = $manager->get_whmcs_system_url() . 'admin/configproductoptions.php?action=managegroup&id=' . $native['group_id'];
        }
        $report[] = $row;
    }
    if (!hash_equals($before, $protected_snapshot())) throw new RuntimeException('Protected catalog/order data changed during import. Review before continuing.');
    if (!hash_equals($original_before, $original_snapshot())) throw new RuntimeException('An existing native option group changed during import. Review before continuing.');
    echo json_encode(['mode' => $apply ? 'apply' : 'dry-run', 'plans' => $report, 'base_prices_products_and_order_counts_unchanged' => true, 'original_native_groups_unchanged' => true, 'note' => 'Native WHMCS cycles: 1, 3, 6, 12, 24, 36 months. Edit individual prices in WHMCS; this importer never overwrites linked groups.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "VERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
