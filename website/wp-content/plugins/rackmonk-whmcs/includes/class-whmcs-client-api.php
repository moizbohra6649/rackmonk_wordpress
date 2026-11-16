<?php
/** WHMCS identities are created only by the supported local API, never SQL. */
if (!defined('ABSPATH')) exit;

class Rackmonk_WHMCS_Client_API {
    private const REFERENCE_PREFIX = 'Rackmonk registration reference: ';
    private const MAX_BYTES = 65536;
    private const TIMEOUT = 25;

    private static function db() {
        $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
        if (!$pdo) throw new RuntimeException();
        return $pdo;
    }

    private static function error($code, $message) {
        return new WP_Error($code, $message);
    }

    private static function yes($value) {
        return in_array(strtolower((string) $value), ['1', 'on', 'yes', 'true'], true);
    }

    /** Safe display/registration metadata only. Native regex stays inside WHMCS. */
    public static function schema() {
        try {
            $pdo = self::db();
            $keys = ['AllowClientRegister', 'OptionalClientFields', 'OptionalClientsFields', 'DefaultCountry', 'TaxEnabled', 'TaxLabel', 'MarketingEmailOptIn', 'RequiredPWStrength'];
            $statement = $pdo->prepare('SELECT setting,value FROM tblconfiguration WHERE setting IN (' . implode(',', array_fill(0, count($keys), '?')) . ')');
            $statement->execute($keys);
            $config = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
            $optional = preg_split('/[,\s]+/', (string) ($config['OptionalClientFields'] ?? $config['OptionalClientsFields'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $optional = array_values(array_intersect($optional, ['firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'tax_id']));
            $optional = array_values(array_unique(array_merge(['companyname', 'address2', 'tax_id'], $optional)));
            $fields = $pdo->query("SELECT id,fieldname,fieldtype,description,fieldoptions,required,showorder FROM tblcustomfields WHERE type='client' AND (adminonly IS NULL OR adminonly IN ('','0','off')) ORDER BY sortorder,id")->fetchAll(PDO::FETCH_ASSOC);
            $fields = array_values(array_filter($fields, static function ($field) { return self::yes($field['required']) || self::yes($field['showorder']); }));
            foreach ($fields as &$field) {
                $field['id'] = (int) $field['id'];
                $field['required'] = self::yes($field['required']);
                $field['showorder'] = self::yes($field['showorder']);
                foreach (['fieldname', 'description', 'fieldoptions'] as $key) $field[$key] = strip_tags((string) $field[$key]);
            }
            unset($field);
            $country = strtoupper((string) ($config['DefaultCountry'] ?? ''));
            if (!preg_match('/\A[A-Z]{2}\z/D', $country)) $country = '';
            $questions = $pdo->query('SELECT id,question FROM tbladminsecurityquestions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($questions as &$question) {
                $question['id'] = (int) $question['id'];
                $question['question'] = strip_tags((string) $question['question']);
            }
            unset($question);
            $currencies = $pdo->query('SELECT id,code,prefix,suffix,`default` FROM tblcurrencies ORDER BY `default` DESC,id')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($currencies as &$currency) {
                $currency['id'] = (int) $currency['id'];
                $currency['default'] = (bool) $currency['default'];
            }
            unset($currency);
            return [
                'registration_enabled' => self::yes($config['AllowClientRegister'] ?? ''),
                'optional_fields' => $optional, 'country' => $country, 'default_country' => $country,
                'custom_fields' => $fields, 'security_questions' => $questions, 'currencies' => $currencies,
                'tax_enabled' => self::yes($config['TaxEnabled'] ?? ''), 'tax_label' => strip_tags((string) ($config['TaxLabel'] ?? 'Tax ID')),
                'marketing_enabled' => self::yes($config['MarketingEmailOptIn'] ?? ''),
                'minimum_password_strength' => max(0, min(100, (int) ($config['RequiredPWStrength'] ?? 0))),
            ];
        } catch (Throwable $error) {
            return self::error('whmcs_schema', 'WHMCS registration settings are unavailable.');
        }
    }

    /** No arbitrary commands, executable overrides, shell, or secrets in argv. */
    private static function request(string $command, array $params) {
        if (!in_array($command, ['AddClient', 'GetConfigurationValue', 'GetClientsDetails', 'GetUsers', 'CreateSsoToken', 'ValidateLogin', 'SetUserPassword'], true)) return self::error('whmcs_transport', 'WHMCS is unavailable.');
        $process = null;
        $pipes = [];
        try {
            if (!function_exists('proc_open')) throw new RuntimeException();
            $name = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
            $candidates = PHP_SAPI === 'cli' ? [PHP_BINARY] : [PHP_BINDIR . DIRECTORY_SEPARATOR . $name, dirname(php_ini_loaded_file() ?: PHP_BINDIR . '/php.ini') . DIRECTORY_SEPARATOR . $name];
            $binary = '';
            foreach ($candidates as $candidate) if (is_file($candidate)) { $binary = $candidate; break; }
            $bridge = dirname(__DIR__) . '/bridge/whmcs-client-cli.php';
            if (!is_file($binary) || !is_file($bridge)) throw new RuntimeException();
            $json = json_encode(['command' => $command, 'params' => $params], JSON_THROW_ON_ERROR);
            if (strlen($json) > self::MAX_BYTES) throw new RuntimeException();
            $process = proc_open([$binary, '-d', 'display_errors=0', '-d', 'log_errors=0', $bridge], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname($bridge), null, ['bypass_shell' => true, 'suppress_errors' => true]);
            if (!is_resource($process)) throw new RuntimeException();
            foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
            $deadline = microtime(true) + self::TIMEOUT;
            $written = 0;
            $exit = -1;
            while (true) {
                if (microtime(true) >= $deadline) throw new RuntimeException();
                if (isset($pipes[0])) {
                    $count = fwrite($pipes[0], substr($json, $written, 8192));
                    if ($count === false) throw new RuntimeException();
                    $written += $count;
                    if ($written === strlen($json)) { fclose($pipes[0]); unset($pipes[0]); }
                }
                $status = proc_get_status($process);
                if (!$status['running']) { $exit = $status['exitcode']; break; }
                usleep(10000);
            }
            // Windows anonymous-pipe reads are not genuinely nonblocking. Never
            // read a live child's pipes: poll its deadline first. The bridge's
            // response is capped at 2KB so normal output cannot fill the pipe;
            // a noisy/stuck bootstrap is terminated at the same deadline.
            $output = stream_get_contents($pipes[1], self::MAX_BYTES + 1);
            $stderr = stream_get_contents($pipes[2], self::MAX_BYTES + 1);
            if ($output === false || strlen($output) > self::MAX_BYTES || $stderr !== '') throw new RuntimeException();
            foreach ($pipes as $pipe) fclose($pipe);
            $pipes = [];
            $closed = proc_close($process);
            $process = null;
            if (($exit >= 0 ? $exit : $closed) !== 0) throw new RuntimeException();
            $result = json_decode($output, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($result) || ($result['result'] ?? '') !== 'success') {
                if ($command === 'AddClient' && ($result['kind'] ?? '') === 'validation') return self::error('whmcs_validation', ($result['field'] ?? '') === 'phone' ? 'Enter a valid phone number, including its country code.' : 'WHMCS rejected the registration details. Review the required fields or use the existing account sign-in.');
                if ($command === 'ValidateLogin' && ($result['kind'] ?? '') === 'invalid_login') return self::error('whmcs_invalid_login', 'Incorrect email address or password.');
                throw new RuntimeException();
            }
            return $result;
        } catch (Throwable $error) {
            return self::error('whmcs_transport', 'WHMCS could not confirm the request. Do not retry registration before checking its reference.');
        } finally {
            foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
            if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        }
    }

    public static function health() {
        return self::request('GetConfigurationValue', ['setting' => 'Version']);
    }

    private static function valid_reference($reference) {
        return is_string($reference) && preg_match('/\A[A-Za-z0-9_-]{20,128}\z/D', $reference);
    }

    /** Read only: verify WHMCS8's actual owner relationship, never email-link. */
    private static function owner(int $clientId, string $email = '', int $userId = 0, bool $forSso = false) {
        $pdo = self::db();
        $expected = [
            'tblusers_clients' => ['auth_user_id', 'client_id', 'owner'],
            'tblusers' => ['id', 'email', 'second_factor', 'second_factor_config'],
            'tblclients' => ['id', 'notes', 'status', 'allow_sso'],
        ];
        foreach ($expected as $table => $columns) {
            $actual = $pdo->query('SHOW COLUMNS FROM ' . $table)->fetchAll(PDO::FETCH_COLUMN);
            if (array_diff($columns, $actual)) throw new RuntimeException();
        }
        $statement = $pdo->prepare('SELECT c.id AS client_id,u.id AS user_id,u.email,c.status,c.allow_sso,u.second_factor,u.second_factor_config FROM tblclients c JOIN tblusers_clients r ON r.client_id=c.id AND r.owner=1 JOIN tblusers u ON u.id=r.auth_user_id WHERE c.id=?');
        $statement->execute([$clientId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) !== 1) throw new RuntimeException();
        $row = $rows[0];
        if (($email !== '' && strcasecmp(trim($row['email']), trim($email)) !== 0) || ($userId && (int) $row['user_id'] !== $userId)) throw new RuntimeException();
        if ($forSso && ($row['status'] !== 'Active' || !self::yes($row['allow_sso']) || !in_array($row['second_factor'], ['', null], true) || !in_array($row['second_factor_config'], ['', null, '{}', '[]'], true))) throw new RuntimeException();
        return ['result' => 'success', 'client_id' => (int) $row['client_id'], 'user_id' => (int) $row['user_id']];
    }

    public static function recover_registration($reference, $email) {
        if (!self::valid_reference($reference) || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return self::error('whmcs_validation', 'Invalid registration reference.');
        try {
            $statement = self::db()->prepare('SELECT id FROM tblclients WHERE BINARY notes = ?');
            $statement->execute([self::REFERENCE_PREFIX . $reference]);
            $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
            if (count($ids) !== 1) return self::error('whmcs_recovery', 'No unique confirmed registration was found.');
            return self::owner((int) $ids[0], $email);
        } catch (Throwable $error) {
            return self::error('whmcs_recovery', 'The registration owner could not be confirmed.');
        }
    }

    public static function register_client(array $data, string $reference) {
        if (!self::valid_reference($reference)) return self::error('whmcs_validation', 'Invalid registration reference.');
        $schema = self::schema();
        if (is_wp_error($schema)) return $schema;
        if (!$schema['registration_enabled']) return self::error('whmcs_validation', 'WHMCS registration is currently disabled.');
        $email = $data['email'] ?? '';
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($data['password2'] ?? null) || $data['password2'] === '') return self::error('whmcs_validation', 'Email and password are required.');
        try {
            // A reference is single-use even when the caller lost the API response.
            $statement = self::db()->prepare('SELECT id FROM tblclients WHERE BINARY notes = ?');
            $statement->execute([self::REFERENCE_PREFIX . $reference]);
            if ($statement->fetchColumn() !== false) {
                $existing = self::recover_registration($reference, $email);
                return is_wp_error($existing) ? self::error('whmcs_transport', 'The existing registration could not be confirmed.') : $existing;
            }
        } catch (Throwable $error) { return self::error('whmcs_transport', 'WHMCS registration could not be checked.'); }
        $params = [];
        foreach (['firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'tax_id', 'password2', 'securityqid', 'securityqans', 'currency', 'language', 'marketingoptin'] as $key) {
            if (array_key_exists($key, $data)) {
                if (!is_scalar($data[$key])) return self::error('whmcs_validation', 'Invalid registration field.');
                $params[$key] = $data[$key];
            }
        }
        if (isset($data['customfields'])) {
            if (!is_array($data['customfields'])) return self::error('whmcs_validation', 'Invalid custom fields.');
            $custom = [];
            $allowedIds = array_column($schema['custom_fields'], 'id');
            foreach ($data['customfields'] as $id => $value) {
                if (!in_array((int) $id, $allowedIds, true) || !is_scalar($value)) return self::error('whmcs_validation', 'Invalid custom fields.');
                $custom[(int) $id] = (string) $value;
            }
            $params['customfields'] = base64_encode(serialize($custom));
        }
        $params['notes'] = self::REFERENCE_PREFIX . $reference;
        $params['skipvalidation'] = false;
        $params['noemail'] = true;
        $result = self::request('AddClient', $params);
        if (is_wp_error($result)) return $result;
        $recovered = self::recover_registration($reference, $email);
        if (is_wp_error($recovered) || (int) ($result['clientid'] ?? 0) !== $recovered['client_id']) return self::error('whmcs_transport', 'WHMCS registration ownership could not be confirmed.');
        return $recovered;
    }

    /**
     * Check client-area credentials against WHMCS (the source of truth for customer logins).
     * @return array{user_id:int,two_factor:bool}|WP_Error
     */
    public static function validate_login($email, $password) {
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '' || strlen($password) > 4096) {
            return self::error('whmcs_invalid_login', 'Incorrect email address or password.');
        }
        $result = self::request('ValidateLogin', ['email' => $email, 'password2' => $password]);
        if (is_wp_error($result)) {
            return $result->get_error_code() === 'whmcs_invalid_login' ? $result : self::error('whmcs_unavailable', 'Sign-in is temporarily unavailable. Please try again in a few minutes.');
        }
        $userId = (int) ($result['userid'] ?? 0);
        if ($userId < 1) return self::error('whmcs_unavailable', 'Sign-in is temporarily unavailable. Please try again in a few minutes.');
        return ['user_id' => $userId, 'two_factor' => !empty($result['two_factor'])];
    }

    /**
     * The client account a WHMCS user belongs to (owned accounts first, then active ones).
     * @return array{client_id:int,owner:bool,status:string,email:string,user_email:string,firstname:string,lastname:string}|null
     */
    public static function client_for_user(int $userId) {
        try {
            $statement = self::db()->prepare("SELECT c.id AS client_id, r.owner, c.status, c.email, c.firstname, c.lastname, u.email AS user_email, u.first_name AS user_first, u.last_name AS user_last
                FROM tblusers u JOIN tblusers_clients r ON r.auth_user_id = u.id JOIN tblclients c ON c.id = r.client_id
                WHERE u.id = ? ORDER BY r.owner DESC, (c.status = 'Active') DESC, c.id ASC LIMIT 1");
            $statement->execute([$userId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            return [
                'client_id' => (int) $row['client_id'], 'owner' => (int) $row['owner'] === 1, 'status' => (string) $row['status'],
                'email' => (string) $row['email'], 'user_email' => (string) $row['user_email'],
                'firstname' => (string) ($row['user_first'] ?: $row['firstname']), 'lastname' => (string) ($row['user_last'] ?: $row['lastname']),
            ];
        } catch (Throwable $error) {
            return null;
        }
    }

    /** WHMCS user id for a login email, or 0. */
    public static function user_id_by_email(string $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 0;
        try {
            $statement = self::db()->prepare('SELECT id FROM tblusers WHERE email = ? LIMIT 2');
            $statement->execute([$email]);
            $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
            return count($ids) === 1 ? (int) $ids[0] : 0;
        } catch (Throwable $error) {
            return 0;
        }
    }

    /** Set a WHMCS user's sign-in password (used after the website reset link was verified). */
    public static function set_user_password(int $userId, string $password) {
        if ($userId < 1 || strlen($password) < 8 || strlen($password) > 4096) return self::error('whmcs_password', 'The new password could not be saved.');
        $result = self::request('SetUserPassword', ['user_id' => $userId, 'password2' => $password]);
        return is_wp_error($result) ? self::error('whmcs_password', 'Your new password could not be saved right now. Please try again in a few minutes.') : true;
    }

    /** WHMCS "forgot password" page for client users (no longer used by the storefront). */
    public static function password_reset_url() {
        $base = (string) Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url();
        return $base !== '' ? trailingslashit($base) . 'index.php?rp=/password/reset' : '';
    }

    private static function same_origin(string $url, string $systemUrl) {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) return false;
        $target = parse_url($url);
        $system = parse_url($systemUrl);
        if (!$target || !$system || isset($target['user']) || isset($target['pass']) || isset($target['fragment'])) return false;
        foreach ([$target, $system] as $parts) if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) return false;
        $port = static function ($parts) { return $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80); };
        return strtolower($target['scheme']) === strtolower($system['scheme']) && strtolower($target['host']) === strtolower($system['host']) && $port($target) === $port($system);
    }

    /** Caller must perform nonce/CSRF checks; this additionally binds the actor. */
    public static function sso($wpUserId) {
        $wpUserId = (int) $wpUserId;
        if ($wpUserId < 1 || get_current_user_id() !== $wpUserId || get_user_meta($wpUserId, '_rackmonk_whmcs_sync_status', true) !== 'active') return self::error('whmcs_sso', 'Sign in with a confirmed linked account.');
        $clientId = (int) get_user_meta($wpUserId, '_rackmonk_whmcs_client_id', true);
        $userId = (int) get_user_meta($wpUserId, '_rackmonk_whmcs_user_id', true);
        if ($clientId < 1 || $userId < 1) return self::error('whmcs_sso', 'No confirmed WHMCS account is linked.');
        try { self::owner($clientId, '', $userId, true); }
        catch (Throwable $error) { return self::error('whmcs_sso', 'Use the WHMCS sign-in page. Account ownership, status, or multi-factor settings prevent single sign-on.'); }
        // The documented default is the client-area homepage; no custom destination.
        $result = self::request('CreateSsoToken', ['client_id' => $clientId, 'user_id' => $userId]);
        if (is_wp_error($result)) return $result;
        $url = $result['redirect_url'] ?? '';
        if (!is_string($url) || !self::same_origin($url, Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url())) return self::error('whmcs_sso', 'WHMCS returned an unsafe sign-in destination.');
        self::logout_bridge_key(); // make sure the WHMCS logout hook can reach this website
        return ['result' => 'success', 'redirect_url' => $url];
    }

    /**
     * Single logout: the WHMCS hook (whmcs/includes/hooks/rackmonk-website-logout.php) needs this
     * website's URL and a signing key. Both live in WHMCS tblconfiguration; returns the key.
     */
    public static function logout_bridge_key() {
        static $key = null;
        if ($key !== null) return $key;
        try {
            $pdo = self::db();
            $rows = $pdo->query("SELECT setting,value FROM tblconfiguration WHERE setting IN ('RackmonkWebsiteUrl','RackmonkLogoutKey')")->fetchAll(PDO::FETCH_KEY_PAIR);
            $save = static function ($setting, $value) use ($pdo) {
                $pdo->prepare('DELETE FROM tblconfiguration WHERE setting = ?')->execute([$setting]);
                $pdo->prepare('INSERT INTO tblconfiguration (setting, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())')->execute([$setting, $value]);
            };
            $key = (string) ($rows['RackmonkLogoutKey'] ?? '');
            if (strlen($key) < 32) { $key = bin2hex(random_bytes(32)); $save('RackmonkLogoutKey', $key); }
            $site = home_url('/');
            if (($rows['RackmonkWebsiteUrl'] ?? '') !== $site) $save('RackmonkWebsiteUrl', $site);
        } catch (Throwable $error) {
            $key = '';
        }
        return $key;
    }
}
