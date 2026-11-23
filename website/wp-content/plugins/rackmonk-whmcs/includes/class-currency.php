<?php
/**
 * Display currency for prices on the website.
 *
 *  1. Logged-in client: the billing currency of their account (WHMCS client currency, chosen at sign-up).
 *  2. Visitor from India: INR. Visitor from anywhere else: USD.
 *  3. Unknown location (local network / development): the WHMCS default currency.
 *
 * Amounts come from WHMCS. When a product has no price of its own in the display currency,
 * the default-currency price is converted with the exchange rate set in WHMCS > Currencies.
 * Administrators can preview a currency with ?rm_currency=USD / INR / auto.
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Currency {
    const PREVIEW_COOKIE = 'rm_preview_currency';
    const DOMESTIC_COUNTRY = 'IN';
    const DOMESTIC_CURRENCY = 'INR';
    const FOREIGN_CURRENCY = 'USD';

    private static $currencies = null;
    private static $current = null;
    private static $from_prices = [];

    /** Cloudflare edge networks (https://www.cloudflare.com/ips/): only these may supply the visitor's country. */
    const CLOUDFLARE = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
        '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public static function boot() {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        add_action('init', [__CLASS__, 'preview_switch']);
        add_action('send_headers', 'nocache_headers');
        add_action('set_current_user', [__CLASS__, 'reset']);
        add_filter('elementor/element/is_dynamic_content', static function ($dynamic, $data) {
            return $dynamic || in_array($data['widgetType'] ?? '', ['xhosting-pricing', 'xhosting-banner', 'shortcode'], true);
        }, 10, 2);
        // Whole Elementor documents, after cached widgets are filled in, so every visitor gets their own currency.
        add_filter('elementor/frontend/the_content', [__CLASS__, 'replace_tokens'], 20);
    }

    public static function reset() {
        self::$current = null;
        self::$from_prices = [];
    }

    /* ------------------------------------------------------------ currencies */

    /** WHMCS currencies keyed by code. */
    public static function all() {
        if (self::$currencies !== null) return self::$currencies;
        self::$currencies = [];
        $pdo = class_exists('Rackmonk_Plans_Manager') ? Rackmonk_Plans_Manager::get_instance()->get_whmcs_db() : null;
        if ($pdo) {
            try {
                foreach ($pdo->query('SELECT id, code, prefix, suffix, rate, `default` FROM tblcurrencies ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $code = strtoupper((string) $row['code']);
                    if (!preg_match('/^[A-Z]{3}$/D', $code)) continue;
                    self::$currencies[$code] = ['id' => (int) $row['id'], 'code' => $code, 'prefix' => (string) $row['prefix'], 'suffix' => (string) $row['suffix'], 'rate' => (float) $row['rate'], 'default' => (int) $row['default'] === 1];
                }
            } catch (Throwable $error) {
                error_log('Rackmonk currency: ' . $error->getMessage());
            }
        }
        if (!self::$currencies) self::$currencies = ['INR' => ['id' => 1, 'code' => 'INR', 'prefix' => '₹', 'suffix' => ' INR', 'rate' => 1.0, 'default' => true]];
        return self::$currencies;
    }

    public static function default_currency() {
        foreach (self::all() as $currency) if ($currency['default']) return $currency;
        $all = self::all();
        return reset($all);
    }

    public static function get($code) {
        $all = self::all();
        return $all[strtoupper((string) $code)] ?? null;
    }

    public static function by_id($id) {
        foreach (self::all() as $currency) if ($currency['id'] === (int) $id) return $currency;
        return null;
    }

    /** Currency to show prices in for this request. */
    public static function current() {
        if (self::$current !== null) return self::$current;
        $currency = null;
        // Catalogue screens in wp-admin keep working in the WHMCS default currency.
        if (is_admin() && !wp_doing_ajax()) $currency = self::default_currency();
        if (!$currency && current_user_can('manage_options') && isset($_COOKIE[self::PREVIEW_COOKIE]) && is_string($_COOKIE[self::PREVIEW_COOKIE])) $currency = self::get(sanitize_text_field(wp_unslash($_COOKIE[self::PREVIEW_COOKIE])));
        if (!$currency && is_user_logged_in()) $currency = self::account_currency(get_current_user_id());
        if (!$currency) {
            $country = self::country();
            if ($country !== '') $currency = self::get($country === self::DOMESTIC_COUNTRY ? self::DOMESTIC_CURRENCY : self::FOREIGN_CURRENCY);
        }
        return self::$current = ($currency ?: self::default_currency());
    }

    /** Billing currency of a client account: WHMCS first (it can be changed there), then the one chosen at sign-up. */
    public static function account_currency($user_id) {
        $client_id = (int) get_user_meta($user_id, '_rackmonk_whmcs_client_id', true);
        $pdo = $client_id && class_exists('Rackmonk_Plans_Manager') ? Rackmonk_Plans_Manager::get_instance()->get_whmcs_db() : null;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT currency FROM tblclients WHERE id = ? LIMIT 1');
                $stmt->execute([$client_id]);
                $currency = self::by_id((int) $stmt->fetchColumn());
                if ($currency) return $currency;
            } catch (Throwable $error) {
                error_log('Rackmonk currency: ' . $error->getMessage());
            }
        }
        $chosen = (int) get_user_meta($user_id, '_rackmonk_whmcs_currency_id', true);
        return $chosen ? self::by_id($chosen) : null;
    }

    /** Administrators: ?rm_currency=USD|INR previews the site in that currency, ?rm_currency=auto goes back to normal. */
    public static function preview_switch() {
        if (!isset($_GET['rm_currency']) || !is_string($_GET['rm_currency']) || !current_user_can('manage_options')) return;
        $code = strtoupper(sanitize_key(wp_unslash($_GET['rm_currency'])));
        $secure = is_ssl();
        if ($code === 'AUTO' || !self::get($code)) {
            setcookie(self::PREVIEW_COOKIE, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, true);
            unset($_COOKIE[self::PREVIEW_COOKIE]);
        } else {
            setcookie(self::PREVIEW_COOKIE, $code, time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, true);
            $_COOKIE[self::PREVIEW_COOKIE] = $code;
        }
        self::reset();
    }

    /* ------------------------------------------------------------- amounts */

    /** Convert an amount in the WHMCS default currency with the WHMCS exchange rate. */
    public static function convert($amount, $currency = null) {
        $currency = $currency ?: self::current();
        $default = self::default_currency();
        if ($currency['code'] === $default['code']) return (float) $amount;
        return round((float) $amount * $currency['rate'] / ($default['rate'] > 0 ? $default['rate'] : 1), 2);
    }

    /** Number as shown on the site: INR without paise (as before), other currencies with cents when needed. */
    public static function number($amount, $currency = null, $decimals = null) {
        $currency = $currency ?: self::current();
        if ($decimals === null) {
            $decimals = ($currency['code'] === self::DOMESTIC_CURRENCY || abs($amount - round($amount)) < 0.005) ? 0 : 2;
        }
        return number_format((float) $amount, $decimals);
    }

    public static function format($amount, $currency = null, $decimals = null) {
        $currency = $currency ?: self::current();
        return $currency['prefix'] . self::number($amount, $currency, $decimals);
    }

    /* ----------------------------------------------------------- location */

    /** Two-letter country of the visitor, or '' when it cannot be known (local network). */
    public static function country() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        if (self::in_cidrs($ip, self::CLOUDFLARE)) {
            $cf = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
            if (preg_match('/^[A-Z]{2}$/D', $cf) && !in_array($cf, ['XX', 'T1'], true)) return $cf;
            $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $ip);
        }
        // Country set by the web server itself (e.g. a GeoIP module); visitors cannot send this.
        $server = strtoupper((string) ($_SERVER['GEOIP_COUNTRY_CODE'] ?? ''));
        if (preg_match('/^[A-Z]{2}$/D', $server)) return $server;
        $ip = (string) apply_filters('rackmonk_visitor_ip', $ip);
        $packed = @inet_pton($ip);
        if ($packed !== false && strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") $ip = inet_ntop(substr($packed, 12));
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return '';
        // Prefer the installed geolocation database; allocation country is only a fallback.
        if (class_exists('WC_Geolocation') && is_callable(['WC_Geolocation', 'geolocate_ip'])) {
            $location = WC_Geolocation::geolocate_ip($ip, false, false);
            if (preg_match('/^[A-Z]{2}$/D', $location['country'] ?? '')) return $location['country'];
        }
        return self::is_india_ip($ip) ? self::DOMESTIC_COUNTRY : 'ZZ';
    }

    /** Binary search in data/india-ip.bin (built from APNIC's delegation list by tools/build-india-ip.php). */
    public static function is_india_ip($ip) {
        $packed = @inet_pton($ip);
        if ($packed === false) return false;
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") $packed = substr($packed, 12);
        $file = RACKMONK_WHMCS_DIR . 'data/india-ip.bin';
        $fh = @fopen($file, 'rb');
        if (!$fh) return false;
        $found = false;
        $header = fread($fh, 9);
        if (strlen($header) === 9 && substr($header, 0, 5) === 'RMIP1') {
            $v4_count = unpack('N', substr($header, 5, 4))[1];
            if ($v4_count > 1000000 || fstat($fh)['size'] < 13 + $v4_count * 8) { fclose($fh); return false; }
            $width = 4;
            $offset = 9;
            $count = $v4_count;
            if (strlen($packed) === 16) {
                fseek($fh, 9 + $v4_count * 8);
                $count = unpack('N', fread($fh, 4))[1];
                $width = 16;
                $offset = 13 + $v4_count * 8;
            }
            $lo = 0; $hi = $count - 1;
            while ($lo <= $hi) {
                $mid = intdiv($lo + $hi, 2);
                fseek($fh, $offset + $mid * $width * 2);
                $pair = fread($fh, $width * 2);
                if (strlen($pair) !== $width * 2) break;
                $start = substr($pair, 0, $width);
                $end = substr($pair, $width);
                if (strcmp($packed, $start) < 0) $hi = $mid - 1;
                elseif (strcmp($packed, $end) > 0) $lo = $mid + 1;
                else { $found = true; break; }
            }
        }
        fclose($fh);
        return $found;
    }

    private static function in_cidrs($ip, array $cidrs) {
        $packed = @inet_pton($ip);
        if ($packed === false) return false;
        foreach ($cidrs as $cidr) {
            [$net, $bits] = explode('/', $cidr);
            $net = inet_pton($net);
            if (strlen($net) !== strlen($packed)) continue;
            $bytes = intdiv((int) $bits, 8);
            $rest = (int) $bits % 8;
            if (substr($packed, 0, $bytes) !== substr($net, 0, $bytes)) continue;
            if ($rest === 0) return true;
            $mask = (0xFF << (8 - $rest)) & 0xFF;
            if ((ord($packed[$bytes]) & $mask) === (ord($net[$bytes]) & $mask)) return true;
        }
        return false;
    }

    /* --------------------------------------------------------- page tokens */

    /**
     * Price tokens for any Elementor text, e.g. "VPS Hosting — Starting From {from_price:vps}/mo".
     * {from_price:<group>} is the lowest per-month price (48-month term) of a WHMCS product group,
     * matched by name or slug (shared, cloud, vps, wordpress ...). {from_price} uses all plans.
     */
    public static function replace_tokens($content) {
        if (!class_exists('Rackmonk_Plans_Manager')) return $content;
        return preg_replace_callback('/\{(from_price|monthly_price)(?::([a-z0-9-]+))?\}/i', static function ($m) {
            $price = self::from_price(strtolower($m[2] ?? ''), strtolower($m[1]) === 'monthly_price');
            return $price === null ? esc_html('Unavailable') : esc_html(self::format($price));
        }, $content);
    }

    public static function from_price($group = '', $monthly_only = false) {
        $cache_key = $group . ($monthly_only ? ':monthly' : ':from');
        if (array_key_exists($cache_key, self::$from_prices)) return self::$from_prices[$cache_key];
        $manager = Rackmonk_Plans_Manager::get_instance();
        $gid = null;
        if ($group !== '') {
            foreach ($manager->get_categories() as $category) {
                $names = [sanitize_title($category['slug'] ?? ''), sanitize_title($category['name'] ?? '')];
                foreach ($names as $name) if ($name !== '' && ($name === $group || strpos($name, $group) === 0)) { $gid = (int) $category['id']; break 2; }
            }
            if (!$gid) return self::$from_prices[$cache_key] = null;
        }
        $best = null;
        $plans = $manager->get_plans($gid);
        if (method_exists($manager, 'get_lowest_cart_rate')) {
            $ids = array_map('intval', array_column($plans, 'id'));
            $rate = $manager->get_lowest_cart_rate(null, $ids, $monthly_only ? 1 : 48);
            if (!$rate && !$monthly_only) $rate = $manager->get_lowest_cart_rate(null, $ids, 1);
            if ($rate) return self::$from_prices[$cache_key] = ceil($rate['cents'] / $rate['months']) / 100;
        }
        foreach ($plans as $plan) {
            if (!empty($plan['is_configurable'])) continue;
            $cycle = $monthly_only ? null : ($plan['cycles']['48'] ?? null);
            $monthly = $plan['cycles']['1'] ?? [];
            $value = ($cycle && !empty($cycle['available'])) ? (float) $cycle['price_per_month'] : (!empty($monthly['available']) ? (float) $monthly['price_per_month'] : 0);
            if ($value > 0 && ($best === null || $value < $best)) $best = $value;
        }
        return self::$from_prices[$cache_key] = $best;
    }
}
