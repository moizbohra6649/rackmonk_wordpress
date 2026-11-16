<?php
/** Private, bounded JSON transport for the documented WHMCS local API. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(0);
$completed = false;
// Discard bootstrap/hook output, including shutdown output, without retaining it.
ob_start(static function ($noise) { return ''; }, 4096);
$emit = static function (array $response) use (&$completed) {
    $json = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json) || strlen($json) > 2048) $json = '{"result":"error","kind":"transport"}';
    $completed = true;
    fwrite(STDOUT, $json);
};
register_shutdown_function(static function () use (&$completed, $emit) {
    if (!$completed) $emit(['result' => 'error', 'kind' => 'transport']);
});
try {
    $raw = stream_get_contents(STDIN, 65537);
    if ($raw === false || strlen($raw) > 65536) throw new RuntimeException();
    $request = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($request) || !is_string($request['command'] ?? null) || !is_array($request['params'] ?? null)) throw new RuntimeException();
    $command = $request['command'];
    $params = $request['params'];
    $allowed = [
        'AddClient' => ['firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'tax_id', 'password2', 'securityqid', 'securityqans', 'currency', 'customfields', 'language', 'marketingoptin', 'notes', 'skipvalidation', 'noemail'],
        'GetConfigurationValue' => ['setting'],
        'GetClientsDetails' => ['clientid', 'stats'],
        'GetUsers' => ['user_id', 'limitstart', 'limitnum'],
        'CreateSsoToken' => ['client_id', 'user_id'],
        'ValidateLogin' => ['email', 'password2'],
        // Not a localAPI command: sets a WHMCS user's password after a website reset link was verified.
        'SetUserPassword' => ['user_id', 'password2'],
    ];
    if (!isset($allowed[$command]) || array_diff(array_keys($params), $allowed[$command])) throw new RuntimeException();
    foreach ($params as $value) if (!is_scalar($value) && $value !== null) throw new RuntimeException();
    if ($command === 'GetConfigurationValue' && $params !== ['setting' => 'Version']) throw new RuntimeException();
    if ($command === 'AddClient') {
        if (($params['skipvalidation'] ?? null) !== false || ($params['noemail'] ?? null) !== true ||
            !preg_match('/\ARackmonk registration reference: [A-Za-z0-9_-]{20,128}\z/D', $params['notes'] ?? '') || empty($params['password2'])) throw new RuntimeException();
    }
    if ($command === 'CreateSsoToken' && ((int) ($params['client_id'] ?? 0) < 1 || (int) ($params['user_id'] ?? 0) < 1)) throw new RuntimeException();
    if ($command === 'ValidateLogin' && (!is_string($params['email'] ?? null) || !filter_var($params['email'], FILTER_VALIDATE_EMAIL) || !is_string($params['password2'] ?? null) || $params['password2'] === '' || strlen($params['password2']) > 4096)) throw new RuntimeException();
    // bridge -> plugin -> plugins -> wp-content -> website -> project/whmcs.
    $init = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'whmcs' . DIRECTORY_SEPARATOR . 'init.php';
    if (!is_file($init)) throw new RuntimeException();
    chdir(dirname($init));
    require $init;
    if (!function_exists('localAPI')) throw new RuntimeException();
    if ($command === 'SetUserPassword') {
        $userId = (int) ($params['user_id'] ?? 0);
        $password = $params['password2'] ?? null;
        if ($userId < 1 || !is_string($password) || strlen($password) < 8 || strlen($password) > 4096 || !class_exists('WHMCS\\User\\User')) throw new RuntimeException();
        $user = \WHMCS\User\User::find($userId);
        if (!$user) throw new RuntimeException();
        $user->updatePassword($password);
        $emit(['result' => 'success']);
        return;
    }
    $result = localAPI($command, $params);
    if (!is_array($result) || !isset($result['result'])) throw new RuntimeException();
    if ($result['result'] !== 'success') {
        // Only documented input errors are definite validation rejections. Unknown
        // errors (including licensing failures) must be treated as uncertain.
        $message = (string) ($result['message'] ?? '');
        $phoneError = in_array(trim($message), ['Your phone number is not valid', 'Invalid telephone phone number'], true);
        $validation = $command === 'AddClient' && ($phoneError || preg_match('/\A(?:You did not enter |You did not provide required custom field value for|The email address (?:you entered was not valid|entered is not available for use\.)|A user already exists with that email address|Valid country required|Invalid telephone phone number)/', $message));
        if ($command === 'ValidateLogin' && trim($message) === 'Email or Password Invalid') {
            $emit(['result' => 'error', 'kind' => 'invalid_login']);
        } else {
            $emit(['result' => 'error', 'kind' => $validation ? 'validation' : 'transport', 'field' => $validation && $phoneError ? 'phone' : '']);
        }
    } elseif ($command === 'ValidateLogin') {
        // Only the WHMCS user id and the 2FA flag leave the bridge; never the password hash.
        if ((int) ($result['userid'] ?? 0) < 1) throw new RuntimeException();
        $emit(['result' => 'success', 'userid' => (int) $result['userid'], 'two_factor' => !empty($result['twoFactorEnabled'])]);
    } elseif ($command === 'AddClient') {
        if ((int) ($result['clientid'] ?? 0) < 1) throw new RuntimeException();
        $emit(['result' => 'success', 'clientid' => (int) $result['clientid']]);
    } elseif ($command === 'GetConfigurationValue') {
        $version = (string) ($result['value'] ?? '');
        if (!preg_match('/\A[0-9][0-9A-Za-z. _-]{0,63}\z/D', $version)) throw new RuntimeException();
        $emit(['result' => 'success', 'version' => $version]);
    } elseif ($command === 'CreateSsoToken') {
        if (!is_string($result['redirect_url'] ?? null)) throw new RuntimeException();
        $emit(['result' => 'success', 'redirect_url' => $result['redirect_url']]);
    } else {
        // These allowlisted reads are not a generic client/PII export interface.
        $emit(['result' => 'success']);
    }
} catch (Throwable $error) {
    $emit(['result' => 'error', 'kind' => 'transport']);
}
