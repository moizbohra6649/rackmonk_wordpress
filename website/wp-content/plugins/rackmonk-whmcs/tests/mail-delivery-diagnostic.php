<?php
/** Read-only, secret-free email transport diagnostics. Does not send mail. */
if (PHP_SAPI !== 'cli') exit;
echo "Loading WordPress mail configuration...\n";
require dirname(__DIR__, 4) . '/wp-load.php';
echo "WordPress loaded; checking email templates and transport...\n";
$emails = WC()->mailer()->get_emails();
$reset = $emails['WC_Email_Customer_Reset_Password'] ?? null;
$host = (string) ini_get('SMTP');
$port = (int) ini_get('smtp_port');
$socket = @fsockopen($host ?: 'localhost', $port ?: 25, $errno, $error, 3);
$connected = is_resource($socket);
if ($connected) fclose($socket);
$result = [
    'reset_email_enabled' => $reset ? $reset->is_enabled() : false,
    'phpmailer_transport_hook_configured' => (bool) has_action('phpmailer_init'),
    'php_mail_host' => $host,
    'php_mail_port' => $port,
    'php_mail_server_reachable' => $connected,
    'sendmail_configured' => trim((string) ini_get('sendmail_path')) !== '',
];
$pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
if ($pdo) {
    $stmt = $pdo->query("SELECT setting,CASE WHEN LENGTH(TRIM(value)) > 0 THEN 1 ELSE 0 END AS configured FROM tblconfiguration WHERE setting IN ('SMTPHost','SMTPUsername','SMTPPassword')");
    $result['whmcs_smtp_fields_configured'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->query("SELECT value FROM tblconfiguration WHERE setting='MailType'");
    $type = strtolower((string) $stmt->fetchColumn());
    $result['whmcs_mail_type'] = in_array($type, ['smtp', 'mail', 'phpmail'], true) ? $type : 'not_configured';
}
echo wp_json_encode($result, JSON_PRETTY_PRINT) . "\n";
echo "No email sent; no credentials, reset keys or session values displayed.\n";
