<?php
/**
 * Rackmonk single logout: when a user logs out of WHMCS, also end their session on the
 * WordPress website. WHMCS finishes its own logout first; the final redirect is then
 * pointed at the website, which signs the user out and sends them to its sign-in page.
 *
 * The website URL and signing key are written to tblconfiguration by the
 * rackmonk-whmcs WordPress plugin (settings RackmonkWebsiteUrl / RackmonkLogoutKey).
 */
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

add_hook('UserLogout', 1, function ($vars) {
    $user = $vars['user'] ?? null;
    $userId = is_object($user) ? (int) $user->id : 0;
    if ($userId < 1 || PHP_SAPI === 'cli') return;
    try {
        $config = \WHMCS\Database\Capsule::table('tblconfiguration')
            ->whereIn('setting', ['RackmonkWebsiteUrl', 'RackmonkLogoutKey'])->pluck('value', 'setting');
        $site = (string) ($config['RackmonkWebsiteUrl'] ?? '');
        $key = (string) ($config['RackmonkLogoutKey'] ?? '');
    } catch (\Throwable $e) {
        return;
    }
    if (!preg_match('#\Ahttps?://[^\s]+\z#i', $site) || strlen($key) < 32) return;
    $time = time();
    $target = rtrim($site, '/') . '/?' . http_build_query([
        'rm_whmcs_logout' => 1, 'uid' => $userId, 'ts' => $time,
        'sig' => hash_hmac('sha256', $userId . '|' . $time, $key),
    ]);
    // Runs just before WHMCS sends its response: only replace an existing redirect.
    header_register_callback(function () use ($target) {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Location:') === 0) {
                header('Location: ' . $target, true);
                return;
            }
        }
    });
});
