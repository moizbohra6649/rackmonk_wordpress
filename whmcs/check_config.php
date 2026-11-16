<?php
$pdo = new PDO('mysql:host=localhost;dbname=rackmonk_whmcs;charset=utf8', 'root', '');
$stmt = $pdo->query("SELECT setting, value FROM tblconfiguration WHERE setting IN ('SystemURL', 'SystemSSLURL', 'CompanyName', 'Email', 'Domain', 'SessionIPCheck', 'CookieDomain', 'AutoSSL')");
print_r($stmt->fetchAll(PDO::FETCH_KEY_PAIR));

