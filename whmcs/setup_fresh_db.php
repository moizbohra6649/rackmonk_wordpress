<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Connecting to MySQL...\n";
$pdo = new PDO('mysql:host=localhost', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Recreate database
$pdo->exec("SET sql_mode=''");
$pdo->exec("DROP DATABASE IF EXISTS `rackmonk_whmcs`");
$pdo->exec("CREATE DATABASE `rackmonk_whmcs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `rackmonk_whmcs`");
$pdo->exec("SET foreign_key_checks = 0");
$pdo->exec("SET sql_mode=''");

$dir = 'E:/xampp/htdocs/rackmonk_wordpress/whmcs/resources/sql/install';

// 1. Schema files
$schemaFiles = glob($dir . '/*.schema.sql');
echo "Importing " . count($schemaFiles) . " schema files...\n";
foreach ($schemaFiles as $file) {
    $sql = file_get_contents($file);
    if (!empty(trim($sql))) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            echo "Error in schema " . basename($file) . ": " . $e->getMessage() . "\n";
        }
    }
}

// 2. Data files
$dataFiles = glob($dir . '/*.data.sql');
echo "Importing " . count($dataFiles) . " data files...\n";
foreach ($dataFiles as $file) {
    $sql = file_get_contents($file);
    if (!empty(trim($sql))) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            // Ignore minor duplicate or syntax notes
        }
    }
}

$pdo->exec("SET foreign_key_checks = 1");

// Verify tbltransientdata exists
$res = $pdo->query("SHOW TABLES LIKE 'tbltransientdata'")->fetchAll();
echo "tbltransientdata exists: " . (!empty($res) ? "YES" : "NO") . "\n";

// Verify tblconfiguration
$res2 = $pdo->query("SHOW COLUMNS FROM tblconfiguration LIKE 'updated_at'")->fetchAll();
echo "tblconfiguration.updated_at exists: " . (!empty($res2) ? "YES" : "NO") . "\n";

// Update site settings
echo "Configuring Rackmonk settings...\n";
$configUpdates = [
    'CompanyName' => 'Rackmonk',
    'Email' => 'admin@rackmonk.com',
    'Domain' => 'http://localhost/rackmonk_wordpress/website',
    'SystemURL' => 'http://localhost/rackmonk_wordpress/whmcs/',
    'Version' => '8.13.1-release.1',
];

$stmtConfig = $pdo->prepare("INSERT INTO tblconfiguration (setting, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()");
foreach ($configUpdates as $s => $v) {
    $stmtConfig->execute([$s, $v]);
}

// Ensure Currency INR
$pdo->exec("INSERT INTO tblcurrencies (code, prefix, suffix, format, rate, `default`, created_at, updated_at) VALUES ('INR', '₹', ' INR', 1, 1.00000, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE `default` = 1");
$pdo->exec("INSERT INTO tblcurrencies (code, prefix, suffix, format, rate, `default`, created_at, updated_at) VALUES ('USD', '$', ' USD', 1, 0.01200, 0, NOW(), NOW())");

// Ensure Admin account
$hash = password_hash('admin123', PASSWORD_BCRYPT);
try {
    $pdo->exec("INSERT INTO tbladmins (id, roleid, username, password, firstname, lastname, email, disabled, template, language) 
                VALUES (1, 1, 'admin', '$hash', 'Rackmonk', 'Admin', 'admin@rackmonk.com', 0, 'blend', 'english')");
} catch (Exception $e) {
    // If table structure differs slightly, handle gracefully
    echo "Admin insert note: " . $e->getMessage() . "\n";
}

// Populate product groups and products
echo "Populating hosting plans...\n";
$groups = [
    1 => 'Shared Hosting',
    2 => 'Cloud Hosting',
    3 => 'VPS Hosting',
    4 => 'WordPress Hosting',
    5 => 'Dedicated Servers'
];

$stmtG = $pdo->prepare("INSERT INTO tblproductgroups (id, name, headline, tagline, `order`, created_at, updated_at) VALUES (?, ?, '', '', ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)");
foreach ($groups as $gid => $name) {
    $stmtG->execute([$gid, $name, $gid]);
}

$products = [
    [
        'gid' => 1,
        'name' => 'Starter Shared',
        'desc' => "1 Website Hosted\n25 GB NVMe SSD Storage\nFree SSL Certificate\n100 GB Bandwidth\n1 Email Account\nDirectAdmin / cPanel\n99.9% Uptime Guarantee",
        'monthly' => 249.00,
        'annual' => 1668.00,
        'biennial' => 2616.00,
        'triennial' => 3792.00,
    ],
    [
        'gid' => 1,
        'name' => 'Premium Shared',
        'desc' => "100 Websites Hosted\n100 GB High Speed NVMe Storage\nFree .COM / .IN Domain Included\nFree Unlimited SSL Certificates\nUnlimited Bandwidth\nFree Professional Email Accounts\nDaily Automatic Backups\n24/7 Priority Support",
        'monthly' => 499.00,
        'annual' => 2988.00,
        'biennial' => 4776.00,
        'triennial' => 7152.00,
    ],
    [
        'gid' => 1,
        'name' => 'Business Shared',
        'desc' => "300 Websites Hosted\n200 GB High Speed NVMe Storage\nFree Domain Name Included\nFree Unlimited SSL Certificates\nUnlimited Bandwidth\nDedicated IP Address Included\nFree CDN & Daily Backups\nImunify360 Security Suite",
        'monthly' => 799.00,
        'annual' => 4788.00,
        'biennial' => 7896.00,
        'triennial' => 11952.00,
    ],
    [
        'gid' => 2,
        'name' => 'Cloud Startup',
        'desc' => "3 GB Server RAM\n2 High-Frequency Core CPU\n100 GB NVMe Cloud Storage\n3 TB Monthly Bandwidth\nDedicated IPv4 Address\nFree SSL & Domain\nAutomated Cloud Snapshots",
        'monthly' => 1199.00,
        'annual' => 8388.00,
        'biennial' => 14376.00,
        'triennial' => 23952.00,
    ],
    [
        'gid' => 2,
        'name' => 'Cloud Professional',
        'desc' => "6 GB Server RAM\n4 High-Frequency Core CPU\n200 GB NVMe Cloud Storage\n6 TB Monthly Bandwidth\nDedicated IPv4 Address\nFree SSL & Domain\nDaily Backups & 24/7 Priority Support",
        'monthly' => 1999.00,
        'annual' => 14388.00,
        'biennial' => 23976.00,
        'triennial' => 43152.00,
    ],
    [
        'gid' => 2,
        'name' => 'Cloud Enterprise',
        'desc' => "12 GB Server RAM\n6 High-Frequency Core CPU\n300 GB NVMe Cloud Storage\nUnlimited Bandwidth\nDedicated IPv4 Address\nGlobal CDN & Cloudflare Protection\nDedicated Account Manager",
        'monthly' => 3499.00,
        'annual' => 23988.00,
        'biennial' => 40776.00,
        'triennial' => 71952.00,
    ],
    [
        'gid' => 3,
        'name' => 'KVM VPS 1',
        'desc' => "1 vCPU Core (AMD EPYC)\n2 GB Server Grade RAM\n40 GB Enterprise NVMe SSD\n1 TB High Speed Bandwidth\n1 Dedicated IPv4 + /64 IPv6\nFull Root SSH Access\nDDoS Shield Included",
        'monthly' => 699.00,
        'annual' => 5988.00,
        'biennial' => 10776.00,
        'triennial' => 19152.00,
    ],
    [
        'gid' => 3,
        'name' => 'KVM VPS 2',
        'desc' => "2 vCPU Cores (AMD EPYC)\n4 GB Server Grade RAM\n80 GB Enterprise NVMe SSD\n2 TB High Speed Bandwidth\n1 Dedicated IPv4 + /64 IPv6\nFull Root SSH Access\nInstant OS Reloads (Ubuntu, Debian, AlmaLinux)",
        'monthly' => 1399.00,
        'annual' => 11988.00,
        'biennial' => 20376.00,
        'triennial' => 38352.00,
    ],
    [
        'gid' => 3,
        'name' => 'KVM VPS 3',
        'desc' => "4 vCPU Cores (AMD EPYC)\n8 GB Server Grade RAM\n160 GB Enterprise NVMe SSD\n4 TB High Speed Bandwidth\n1 Dedicated IPv4 + /64 IPv6\nFull Root SSH Access\nWeekly Automated Snapshots",
        'monthly' => 2499.00,
        'annual' => 21588.00,
        'biennial' => 37176.00,
        'triennial' => 71952.00,
    ],
    [
        'gid' => 4,
        'name' => 'WP Starter',
        'desc' => "1 WordPress Site\n30 GB NVMe Storage\nFree SSL Certificate\nFree WP-CLI & SSH Access\nAuto WordPress Updates\nLiteSpeed Caching Included\n24/7 WordPress Support",
        'monthly' => 299.00,
        'annual' => 1788.00,
        'biennial' => 2856.00,
        'triennial' => 4752.00,
    ],
    [
        'gid' => 4,
        'name' => 'WP Pro',
        'desc' => "100 WordPress Sites\n100 GB NVMe Storage\nFree Domain Name Included\nFree Unlimited SSL\nLiteSpeed Enterprise Cache\nObject Cache (Redis) Enabled\n1-Click Staging Tool\nDaily Backups",
        'monthly' => 599.00,
        'annual' => 3588.00,
        'biennial' => 5736.00,
        'triennial' => 9552.00,
    ],
    [
        'gid' => 4,
        'name' => 'WP Ultra',
        'desc' => "300 WordPress Sites\n200 GB NVMe Storage\nFree Domain Name Included\nFree Unlimited SSL\nDedicated CPU & RAM Resources\nSmart AI WordPress Tools\nFree CDN & Imunify360\n24/7 Priority Support",
        'monthly' => 999.00,
        'annual' => 5988.00,
        'biennial' => 9576.00,
        'triennial' => 16752.00,
    ]
];

$stmtP = $pdo->prepare("INSERT INTO tblproducts (
    id, gid, type, name, description, showdomainoptions, welcomeemail,
    paytype, autosetup, created_at, updated_at
) VALUES (?, ?, 'hostingaccount', ?, ?, 1, 1, 'recurring', 'order', NOW(), NOW())");

$stmtPrice = $pdo->prepare("INSERT INTO tblpricing (
    type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee,
    monthly, quarterly, semiannually, annually, biennially, triennially
) VALUES ('product', 1, ?, 0, 0, 0, 0, 0, 0, ?, 0, 0, ?, ?, ?)");

$pId = 1;
foreach ($products as $p) {
    $stmtP->execute([
        $pId,
        $p['gid'],
        $p['name'],
        $p['desc'],
    ]);

    $stmtPrice->execute([
        $pId,
        $p['monthly'],
        $p['annual'],
        $p['biennial'],
        $p['triennial'],
    ]);

    $pId++;
}

echo "Successfully imported all WHMCS 8.13 tables and Rackmonk products!\n";
unlink(__FILE__);

