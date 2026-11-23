<?php
/**
 * Rackmonk WHMCS Plans Manager
 * Handles dynamic fetching, formatting, and management of WHMCS products & pricing.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_Plans_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get DB connection to WHMCS
     */
    public function get_whmcs_db() {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $whmcs_config_file = dirname(ABSPATH) . '/whmcs/configuration.php';
                $db_host = 'localhost';
                $db_name = 'rackmonk_whmcs';
                $db_username = 'root';
                $db_password = '';

                if (file_exists($whmcs_config_file)) {
                    @include $whmcs_config_file;
                }

                $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_username, $db_password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (Exception $e) {
                error_log('WHMCS DB Connection Error: ' . $e->getMessage());
                $pdo = false;
            }
        }
        return $pdo;
    }

    /** Lowest available website-cart rate, including required native options. */
    public function get_lowest_inr_cart_rate() {
        return $this->get_lowest_cart_rate('INR');
    }

    public function get_lowest_cart_rate($code = null, $product_ids = null, $term = 48) {
        $code = $code ?: (class_exists('Rackmonk_Currency') ? Rackmonk_Currency::current()['code'] : 'INR');
        $settings = get_option(Rackmonk_Website_Cart::OPTION, []);
        if (!is_array($settings)) return null;
        $best = null;
        foreach ($settings as $pid => $setting) {
            if ($product_ids !== null && !in_array((int) $pid, $product_ids, true)) continue;
            if (empty($setting['enabled'])) continue;
            try {
                $plan = Rackmonk_Website_Cart::runtime_config((int) $pid);
            } catch (Throwable $error) {
                continue;
            }
            if (!$plan || $plan['currency'] !== $code) continue;
            $rates = [];
            foreach ([1, 48] as $months) {
                $base = $plan['prices'][$months] ?? null;
                if (!$base) continue;
                $cents = (int) round((float) $base['recurring'] * 100);
                $setup = (int) round((float) $base['setup'] * 100);
                foreach ($plan['resources'] as $resource) {
                    $choice = null;
                    foreach ($resource['choices'] as $item) {
                        $price = $item['prices'][$months] ?? null;
                        if (!$price) continue;
                        $amount = (int) round((float) $price['recurring'] * 100);
                        $fee = (int) round((float) $price['setup'] * 100);
                        if ($choice === null || $amount < $choice[0] || ($amount === $choice[0] && $fee < $choice[1])) $choice = [$amount, $fee];
                    }
                    if ($choice === null) continue 2;
                    $cents += $choice[0];
                    $setup += $choice[1];
                }
                $rates[$months] = ['cents' => $cents, 'setup' => $setup];
            }
            foreach ($rates as $months => $rate) {
                if ($months !== $term) continue;
                if ($best !== null && $rate['cents'] * $best['months'] >= $best['cents'] * $months) continue;
                $best = ['cents' => $rate['cents'], 'months' => $months, 'setup' => $rate['setup'], 'monthly' => $rates[1]['cents'] ?? null];
            }
        }
        return $best;
    }

    /**
     * Get WHMCS System URL for cart links
     */
    public function get_whmcs_system_url() {
        $pdo = $this->get_whmcs_db();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT value FROM tblconfiguration WHERE setting = 'SystemURL' LIMIT 1");
                $stmt->execute();
                $val = $stmt->fetchColumn();
                if (!empty($val)) {
                    return rtrim($val, '/') . '/';
                }
            } catch (Exception $e) {}
        }
        return 'http://localhost/rackmonk_wordpress/whmcs/';
    }

    /**
     * Get default currency info
     */
    public function get_default_currency() {
        $pdo = $this->get_whmcs_db();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM tblcurrencies WHERE `default` = 1 LIMIT 1");
                $row = $stmt->fetch();
                if ($row) {
                    return $row;
                }
            } catch (Exception $e) {}
        }
        return [
            'id'     => 1,
            'code'   => 'INR',
            'prefix' => '₹',
            'suffix' => ' INR',
            'rate'   => 1.0,
        ];
    }

    /**
     * Get all active categories / groups from WHMCS
     */
    public function get_categories() {
        $pdo = $this->get_whmcs_db();
        if ($pdo) {
            try {
                $stmt = $pdo->query("
                    SELECT id, name, slug, headline, tagline 
                    FROM tblproductgroups 
                    WHERE (hidden IS NULL OR (hidden != 'on' AND hidden != 1)) 
                    ORDER BY `order` ASC, id ASC
                ");
                $rows = $stmt->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {
                error_log('WHMCS Categories Error: ' . $e->getMessage());
            }
        }

        return [
            ['id' => 1, 'name' => 'Shared Hosting', 'slug' => 'shared-hosting'],
            ['id' => 2, 'name' => 'Cloud Hosting', 'slug' => 'cloud-hosting'],
            ['id' => 3, 'name' => 'VPS Hosting', 'slug' => 'vps-hosting'],
            ['id' => 4, 'name' => 'WordPress Hosting', 'slug' => 'wordpress-hosting'],
            ['id' => 5, 'name' => 'Dedicated Servers', 'slug' => 'dedicated-servers'],
        ];
    }

    /**
     * Get dynamic plans by category or all from WHMCS
     */
    public function get_plans($group_id = null) {
        $pdo = $this->get_whmcs_db();
        $plans = [];

        if ($pdo) {
            try {
                $curr = $this->get_default_currency();
                $curr_id = intval($curr['id'] ?? 1);

                $sql = "SELECT p.*, g.name AS group_name, g.slug AS group_slug,
                               pr.msetupfee, pr.monthly, pr.quarterly, pr.semiannually, pr.annually, pr.biennially, pr.triennially,
                               pr.quadsetupfee, pr.quadrennially,
                               c.code AS currency_code, c.prefix AS currency_prefix, c.suffix AS currency_suffix
                        FROM tblproducts p 
                        LEFT JOIN tblproductgroups g ON p.gid = g.id 
                        LEFT JOIN tblpricing pr ON pr.relid = p.id AND pr.type = 'product' AND pr.currency = ?
                        LEFT JOIN tblcurrencies c ON c.id = pr.currency
                        WHERE (p.hidden = 0 OR p.hidden IS NULL OR p.hidden = '0' OR p.hidden != 'on')
                          AND (p.retired = 0 OR p.retired IS NULL OR p.retired = '0')
                          AND p.paytype != 'onetime'";
                $params = [$curr_id];

                if (!empty($group_id)) {
                    $sql .= " AND p.gid = ?";
                    $params[] = intval($group_id);
                }

                $sql .= " ORDER BY g.`order` ASC, p.`order` ASC, p.id ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();

                $display = class_exists('Rackmonk_Currency') ? Rackmonk_Currency::current() : null;
                foreach ($rows as $r) {
                    if ($display && (int) $display['id'] !== $curr_id) $r = $this->localize_row($r, $pdo, $display);
                    $plans[] = $this->format_product($r);
                }
            } catch (Exception $e) {
                error_log('WHMCS Get Plans Error: ' . $e->getMessage());
            }
        }

        if (empty($plans)) {
            $plans = $this->get_fallback_plans($group_id);
        }

        return $plans;
    }

    /**
     * Re-price a default-currency product row in the visitor's currency: the product's own WHMCS prices in that
     * currency when they are set, otherwise the default prices converted with the WHMCS exchange rate.
     */
    private function localize_row(array $row, $pdo, array $display) {
        $cols = ['msetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially', 'quadsetupfee', 'quadrennially'];
        $own = null;
        try {
            $stmt = $pdo->prepare('SELECT ' . implode(', ', $cols) . " FROM tblpricing WHERE type = 'product' AND relid = ? AND currency = ? LIMIT 1");
            $stmt->execute([(int) ($row['id'] ?? 0), (int) $display['id']]);
            $own = $stmt->fetch() ?: null;
        } catch (Throwable $error) {
            error_log('WHMCS currency pricing: ' . $error->getMessage());
        }
        $has_own = $own !== null;
        foreach ($cols as $col) {
            if ($has_own) { $row[$col] = $own[$col]; continue; }
            $value = (float) ($row[$col] ?? 0);
            if ($value > 0) $row[$col] = Rackmonk_Currency::convert($value, $display);
        }
        $row['currency_code'] = $display['code'];
        $row['currency_prefix'] = $display['prefix'];
        $row['currency_suffix'] = $display['suffix'];
        return $row;
    }

    /** Native 48-month base package only; never substitute a recurring billing cycle. */
    public function get_48_month_package($source_pid) {
        try {
            $pdo = $this->get_whmcs_db();
            if (!$pdo) return null;
            $currency = $this->get_default_currency();
            $stmt = $pdo->prepare("SELECT p.id, pr.monthly, pr.msetupfee
                FROM tblproducts p
                JOIN tblpricing pr ON pr.relid = p.id AND pr.type = 'product' AND pr.currency = ?
                WHERE p.slug = ? AND p.paytype = 'onetime'
                  AND (p.hidden = 0 OR p.hidden IS NULL OR p.hidden = '')
                  AND (p.retired = 0 OR p.retired IS NULL OR p.retired = '')
                LIMIT 2");
            $stmt->execute([(int) $currency['id'], 'rackmonk-48-month-onetime-' . (int) $source_pid]);
            $rows = $stmt->fetchAll();
            if (count($rows) !== 1 || !is_numeric($rows[0]['monthly']) || (float) $rows[0]['monthly'] < 0
                || !is_numeric($rows[0]['msetupfee']) || (float) $rows[0]['msetupfee'] < 0) return null;
            $package = $rows[0];
            $package['order_url'] = add_query_arg('currency', (int) $currency['id'],
                add_query_arg('pid', (int) $package['id'], $this->get_whmcs_system_url() . 'cart.php?a=add'));
            return $package;
        } catch (Throwable $error) {
            return null;
        }
    }

    /** Format source product pricing and its separately mapped one-time package. */
    public function format_product($row) {
        $currency_symbol = !empty($row['currency_prefix']) ? $row['currency_prefix'] : '₹';
        $money = class_exists('Rackmonk_Currency') ? (Rackmonk_Currency::get($row['currency_code'] ?? '') ?: Rackmonk_Currency::default_currency()) : null;
        $num = static function ($amount) use ($money) { return $money ? Rackmonk_Currency::number($amount, $money) : number_format($amount, 0); };

        // Base regular monthly price
        $monthly_regular = floatval($row['monthly'] ?? 0);
        if ($monthly_regular <= 0 && floatval($row['annually'] ?? 0) > 0) {
            $monthly_regular = round(floatval($row['annually']) / 12, 2);
        }
        if ($monthly_regular <= 0) {
            $monthly_regular = 249.00;
        }

        // 48 Months (read from dedicated quadrennially column in WHMCS)
        $qPrice = floatval($row['quadrennially'] ?? 0);
        $qSetup = floatval($row['quadsetupfee'] ?? 0);
        if ($qPrice > 0) {
            $total_48m = $qPrice;
            $setup_48m = $qSetup;
            $price_48m_monthly = round($total_48m / 48, 2);
            $discount_48m = round((($monthly_regular * 48 - $total_48m) / ($monthly_regular * 48)) * 100);
        } else {
            $price_48m_monthly = round($monthly_regular * 0.32, 2);
            $total_48m = round($price_48m_monthly * 48, 2);
            $setup_48m = 0;
            $discount_48m = 68;
        }

        // 36 Months (triennially in WHMCS)
        $tPrice = floatval($row['triennially'] ?? 0);
        $tSetup = floatval($row['tsetupfee'] ?? 0);
        if ($tPrice > 0) {
            $total_36m = $tPrice;
            $setup_36m = $tSetup;
            $price_36m_monthly = round($total_36m / 36, 2);
            $discount_36m = round((($monthly_regular * 36 - $total_36m) / ($monthly_regular * 36)) * 100);
        } else {
            $price_36m_monthly = round($monthly_regular * 0.40, 2);
            $total_36m = round($price_36m_monthly * 36, 2);
            $setup_36m = 0;
            $discount_36m = 60;
        }

        // 24 Months (biennially in WHMCS)
        $bPrice = floatval($row['biennially'] ?? 0);
        if ($bPrice > 0) {
            $total_24m = $bPrice;
            $price_24m_monthly = round($total_24m / 24, 2);
            $discount_24m = round((($monthly_regular * 24 - $total_24m) / ($monthly_regular * 24)) * 100);
        } else {
            $price_24m_monthly = round($monthly_regular * 0.45, 2);
            $total_24m = round($price_24m_monthly * 24, 2);
            $discount_24m = 55;
        }

        // 12 Months (annually in WHMCS)
        $aPrice = floatval($row['annually'] ?? 0);
        if ($aPrice > 0) {
            $total_12m = $aPrice;
            $price_12m_monthly = round($total_12m / 12, 2);
            $discount_12m = round((($monthly_regular * 12 - $total_12m) / ($monthly_regular * 12)) * 100);
        } else {
            $price_12m_monthly = round($monthly_regular * 0.60, 2);
            $total_12m = round($price_12m_monthly * 12, 2);
            $discount_12m = 40;
        }

        // 1 Month
        $total_1m = $monthly_regular;
        $price_1m = $monthly_regular;

        // Parse features list from WHMCS description
        $raw_features = explode("\n", str_replace(["\r\n", "\r"], "\n", $row['description'] ?? ''));
        $features = [];
        foreach ($raw_features as $f) {
            $f = trim($f, " \t\n\r\0\x0B*-•");
            if (!empty($f)) {
                $features[] = $f;
            }
        }
        if (empty($features)) {
            $features = [
                'High Performance NVMe SSD',
                'Free SSL Certificate Included',
                'Unlimited Bandwidth & Emails',
                '24/7 Expert Technical Support',
                '99.9% Guaranteed Uptime'
            ];
        }

        // Determine if popular / featured
        $is_popular = !empty($row['is_featured']) 
                   || (stripos($row['name'] ?? '', 'Premium') !== false) 
                   || (stripos($row['name'] ?? '', 'Pro') !== false) 
                   || (stripos($row['name'] ?? '', 'VPS 2') !== false);

        $tagline = !empty($row['tagline']) ? $row['tagline'] : (!empty($row['short_description']) ? $row['short_description'] : 'High performance hosting architecture');

        $pid = intval($row['id'] ?? 1);
        $cart_base = Rackmonk_Website_Cart::get_cart_url($pid);
        $is_configurable = Rackmonk_Website_Cart::enabled($pid);

        $plan = [
            'id'            => $pid,
            'is_configurable' => $is_configurable,
            'gid'           => intval($row['gid'] ?? 1),
            'group_name'    => $row['group_name'] ?? 'Hosting',
            'name'          => $row['name'] ?? 'Custom Plan',
            'tagline'       => $tagline,
            'is_popular'    => $is_popular,
            'popular_badge' => 'Most Popular',
            'currency'      => $currency_symbol,
            'currency_code' => $money ? $money['code'] : ($row['currency_code'] ?? 'INR'),
            'regular_price' => $monthly_regular,
            'features'      => $features,
            'default_cycle' => '48',
            'cycles' => [
                '48' => [
                    'label'           => '48 Months',
                    'term_months'     => 48,
                    'price_per_month' => $price_48m_monthly,
                    'total_price'     => $total_48m,
                    'setup_fee'       => $setup_48m,
                    'available'       => true,
                    'order_url'       => add_query_arg('term', 48, $cart_base),
                    'regular_price'   => $monthly_regular,
                    'discount_pct'    => $discount_48m,
                    'discount_text'   => $discount_48m > 0 ? ('SAVE ' . $discount_48m . '%') : '',
                    'billing_note'    => 'Billed ' . $currency_symbol . $num($total_48m) . ' for 48 months',
                    'whmcs_cycle'     => 'quadrennially',
                ],
                '36' => [
                    'label'           => '36 Months',
                    'term_months'     => 36,
                    'price_per_month' => $price_36m_monthly,
                    'total_price'     => $total_36m,
                    'setup_fee'       => $setup_36m,
                    'available'       => true,
                    'order_url'       => add_query_arg('term', 36, $cart_base),
                    'regular_price'   => $monthly_regular,
                    'discount_pct'    => $discount_36m,
                    'discount_text'   => $discount_36m > 0 ? ('SAVE ' . $discount_36m . '%') : '',
                    'billing_note'    => 'Billed ' . $currency_symbol . $num($total_36m) . ' for 36 months',
                    'whmcs_cycle'     => 'triennially',
                ],
                '24' => [
                    'label'           => '24 Months',
                    'term_months'     => 24,
                    'price_per_month' => $price_24m_monthly,
                    'total_price'     => $total_24m,
                    'regular_price'   => $monthly_regular,
                    'discount_pct'    => $discount_24m,
                    'discount_text'   => $discount_24m > 0 ? ('SAVE ' . $discount_24m . '%') : '',
                    'billing_note'    => 'Billed ' . $currency_symbol . $num($total_24m) . ' for 24 months',
                    'whmcs_cycle'     => 'biennially',
                    'order_url'       => add_query_arg('term', 24, $cart_base),
                ],
                '12' => [
                    'label'           => '12 Months',
                    'term_months'     => 12,
                    'price_per_month' => $price_12m_monthly,
                    'total_price'     => $total_12m,
                    'regular_price'   => $monthly_regular,
                    'discount_pct'    => $discount_12m,
                    'discount_text'   => $discount_12m > 0 ? ('SAVE ' . $discount_12m . '%') : '',
                    'billing_note'    => 'Billed ' . $currency_symbol . $num($total_12m) . ' for 12 months',
                    'whmcs_cycle'     => 'annually',
                    'order_url'       => add_query_arg('term', 12, $cart_base),
                ],
                '1' => [
                    'label'           => '1 Month',
                    'term_months'     => 1,
                    'price_per_month' => $price_1m,
                    'total_price'     => $total_1m,
                    'regular_price'   => $monthly_regular,
                    'discount_pct'    => 0,
                    'discount_text'   => '',
                    'billing_note'    => 'Billed monthly',
                    'whmcs_cycle'     => 'monthly',
                    'order_url'       => add_query_arg('term', 1, $cart_base),
                ],
            ],
            'order_url' => add_query_arg('term', 48, $cart_base),
        ];
        // Use the same configured amounts as the server-side cart, including manual terms.
        if ($is_configurable && method_exists('Rackmonk_Website_Cart', 'runtime_config')) {
            try {
                $config = Rackmonk_Website_Cart::runtime_config($pid);
                if (!$config || $config['currency'] !== $plan['currency_code']) throw new RuntimeException('Currency pricing unavailable.');
                foreach ($plan['cycles'] as $term => &$cycle) {
                    $amount = $config['prices'][$term] ?? null;
                    $cycle['available'] = $amount !== null;
                    $cycle['total_price'] = $amount ? (float) $amount['recurring'] : 0.0;
                    $cycle['setup_fee'] = $amount ? (float) $amount['setup'] : 0.0;
                    $cycle['price_per_month'] = $cycle['total_price'] / (int) $term;
                    $cycle['regular_price'] = (float) ($config['prices'][1]['recurring'] ?? 0);
                    $cycle['discount_pct'] = $cycle['regular_price'] > 0 ? max(0, round((1 - $cycle['price_per_month'] / $cycle['regular_price']) * 100)) : 0;
                    $cycle['discount_text'] = $cycle['discount_pct'] > 0 ? 'SAVE ' . $cycle['discount_pct'] . '%' : '';
                    $cycle['billing_note'] = $amount ? 'Billed ' . $currency_symbol . $num($cycle['total_price']) . ' for ' . $term . ' months' : 'Unavailable';
                    if (!$amount) $cycle['order_url'] = '';
                }
                unset($cycle);
            } catch (RuntimeException $error) {
                foreach ($plan['cycles'] as &$cycle) { $cycle['available'] = false; $cycle['order_url'] = ''; }
                unset($cycle);
            }
        }
        foreach ($plan['cycles'] as &$cycle) {
            $cycle['display_price'] = $num($cycle['price_per_month']);
            $cycle['display_regular'] = $num($cycle['regular_price']);
        }
        unset($cycle);
        $plan['order_url'] = $plan['cycles']['48']['order_url'];
        return $plan;
    }

    public function money_number($amount, $plan) {
        return class_exists('Rackmonk_Currency')
            ? Rackmonk_Currency::number($amount, Rackmonk_Currency::get($plan['currency_code'] ?? '') ?: Rackmonk_Currency::default_currency())
            : number_format($amount, 0);
    }

    /**
     * Convert WHMCS plans to Hostinkar Elementor pricing widget item structure
     */
    public function get_elementor_pricing_items($gid = null) {
        $plans = $this->get_plans($gid);
        $items = [];

        foreach ($plans as $p) {
            $p48 = $p['cycles']['48'];
            $p12 = $p['cycles']['12'];

            $lists = [];
            foreach ($p['features'] as $f) {
                $lists[] = [
                    'marked_hide' => 'no',
                    'info_title'  => '',
                    'icon'        => [
                        'value'   => 'fas fa-check',
                        'library' => 'fa-solid',
                    ],
                    'title'       => $f,
                ];
            }

            $items[] = [
                '_id'           => 'rm_' . $p['id'],
                'is_populer'    => $p['is_popular'] ? 'yes' : 'no',
                'populer_badge' => 'Most Popular',
                'title'         => $p['name'],
                'text'          => $p['tagline'] ?? 'High performance hosting architecture',
                'm_price'       => !empty($p48['available']) ? $p['currency'] . $this->money_number($p48['price_per_month'], $p) : 'Unavailable',
                'm_period'      => '/mo',
                'y_price'       => !empty($p12['available']) ? $p['currency'] . $this->money_number($p12['price_per_month'], $p) : 'Unavailable',
                'y_period'      => '/mo (12M)',
                'p_text'        => $p48['billing_note'],
                'd_text'        => $p48['discount_text'],
                'rackmonk_cycles' => ['48' => $p48, '12' => $p12],
                'active_btn'    => $p['is_popular'] ? 'yes' : 'no',
                'btn_label'     => 'Choose Plan',
                'link'          => [
                    'url'         => $p['order_url'],
                    'is_external' => false,
                    'nofollow'    => false,
                    'custom_attributes' => '',
                ],
                'lists'         => $lists,
            ];
        }

        return $items;
    }

    /**
     * Create a new custom plan directly into WHMCS database
     */
    public function create_whmcs_plan($data) {
        $pdo = $this->get_whmcs_db();
        if (!$pdo) {
            return new WP_Error('db_error', 'Cannot connect to WHMCS database');
        }

        $started_transaction = false;
        try {
            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid plan data.');
            }
            foreach (['name', 'tagline', 'description'] as $field) {
                if (array_key_exists($field, $data) && !is_string($data[$field])) {
                    throw new InvalidArgumentException('Invalid plan ' . $field . '.');
                }
            }
            $name = sanitize_text_field($data['name'] ?? '');
            $tagline = sanitize_text_field($data['tagline'] ?? '');
            $desc = sanitize_textarea_field($data['description'] ?? '');
            if ($name === '' || strlen($name) > 255 || strlen($tagline) > 255 || strlen($desc) > 65535) {
                throw new InvalidArgumentException('Enter a plan name and keep the plan text within its field limits.');
            }
            $gid = array_key_exists('gid', $data) ? $data['gid'] : 1;
            if ((!is_int($gid) && !is_string($gid)) || filter_var($gid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw new InvalidArgumentException('Select a valid product category.');
            }
            $gid = (int) $gid;
            $stmt = $pdo->prepare('SELECT id FROM tblproductgroups WHERE id = ?');
            $stmt->execute([$gid]);
            if (!$stmt->fetchColumn()) {
                throw new InvalidArgumentException('Select an existing product category.');
            }
            $is_featured = array_key_exists('is_featured', $data) ? $data['is_featured'] : 0;
            if (!in_array($is_featured, [false, true, 0, 1, '0', '1'], true)) {
                throw new InvalidArgumentException('Invalid featured flag.');
            }
            $is_featured = (int) $is_featured;
            $slug = sanitize_title($name);

            $currencies = [];
            $default_code = null;
            foreach ($pdo->query('SELECT id, code, `default` FROM tblcurrencies')->fetchAll(PDO::FETCH_ASSOC) as $currency) {
                $code = $currency['code'];
                if (!preg_match('/^[A-Z]{3}$/D', $code) || isset($currencies[$code]) || (int) $currency['id'] < 1) {
                    throw new RuntimeException('Invalid WHMCS currency configuration.');
                }
                $currencies[$code] = (int) $currency['id'];
                if ((int) $currency['default'] === 1) {
                    if ($default_code !== null) {
                        throw new RuntimeException('WHMCS must have exactly one default currency.');
                    }
                    $default_code = $code;
                }
            }
            if ($default_code === null) {
                throw new RuntimeException('WHMCS default currency is not configured.');
            }
            $money = function ($value, $label) {
                if (!is_string($value) && !is_int($value) && !is_float($value)) {
                    throw new InvalidArgumentException($label . ': enter a decimal price.');
                }
                $value = trim((string) $value);
                if ($value === '') return null;
                if (!preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', $value)) {
                    throw new InvalidArgumentException($label . ': use a price from 0 to 99999999.99 with up to two decimals.');
                }
                return number_format((float) $value, 2, '.', '');
            };
            $parse_prices = function ($prices, $code, $optional = false) use ($money) {
                $terms = ['monthly', 'annually', 'biennially', 'triennially'];
                if (!is_array($prices) || ($optional && array_diff(array_keys($prices), $terms))) {
                    throw new InvalidArgumentException($code . ': invalid currency price fields.');
                }
                $values = [];
                foreach ($terms as $term) {
                    $values[$term] = $money(array_key_exists($term, $prices) ? $prices[$term] : '', $code . ' ' . $term);
                }
                if ($optional && !array_filter($values, function ($value) { return $value !== null; })) return null;
                if ($values['monthly'] === null) {
                    throw new InvalidArgumentException($code . ': an explicit monthly price is required.');
                }
                // Retain the existing 12/24/48-month plan discount conventions for blank terms.
                foreach (['annually' => 12 * 0.60, 'biennially' => 24 * 0.45, 'triennially' => 48 * 0.35] as $term => $factor) {
                    if ($values[$term] === null) {
                        $values[$term] = $money(number_format((float) $values['monthly'] * $factor, 2, '.', ''), $code . ' ' . $term);
                    }
                }
                return $values;
            };
            // Top-level amounts belong only to the actual WHMCS default currency.
            $prices_by_currency = [$currencies[$default_code] => $parse_prices($data, $default_code)];
            $currency_prices = array_key_exists('currency_prices', $data) ? $data['currency_prices'] : [];
            if (!is_array($currency_prices)) {
                throw new InvalidArgumentException('Invalid currency prices.');
            }
            foreach ($currency_prices as $code => $prices) {
                if (!is_string($code) || !isset($currencies[$code]) || $code === $default_code) {
                    throw new InvalidArgumentException('Use a configured nondefault currency code for separate prices.');
                }
                $values = $parse_prices($prices, $code, true);
                if ($values !== null) $prices_by_currency[$currencies[$code]] = $values;
            }

            if (!$pdo->beginTransaction()) {
                throw new RuntimeException('Cannot start plan creation transaction.');
            }
            $started_transaction = true;

            // 1. Insert into tblproducts
            $stmt = $pdo->prepare("
                INSERT INTO tblproducts 
                (type, gid, name, slug, description, tagline, paytype, is_featured, hidden, retired, `order`, created_at, updated_at)
                VALUES ('hostingaccount', ?, ?, ?, ?, ?, 'recurring', ?, 0, 0, 0, NOW(), NOW())
            ");
            if (!$stmt->execute([$gid, $name, $slug, $desc, $tagline, $is_featured])) {
                throw new RuntimeException('Cannot insert the WHMCS product.');
            }
            $pid = $pdo->lastInsertId();
            if (!$pid) {
                throw new RuntimeException('Cannot determine the new WHMCS product ID.');
            }

            // 2. Insert only supplied currencies, without copying or converting other amounts.
            $stmtPrice = $pdo->prepare("
                INSERT INTO tblpricing 
                (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, quadsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially, quadrennially)
                VALUES ('product', ?, ?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, ?, -1.00, -1.00, ?, ?, ?, ?)
            ");

            foreach ($prices_by_currency as $currId => $prices) {
                $quadPrice = isset($prices['quadrennially']) ? $prices['quadrennially'] : round((float)$prices['monthly'] * 0.32 * 48, 2);
                if (!$stmtPrice->execute([$currId, $pid, $prices['monthly'], $prices['annually'], $prices['biennially'], $prices['triennially'], $quadPrice])) {
                    throw new RuntimeException('Cannot insert the WHMCS currency prices.');
                }
            }
            if (!$pdo->commit()) {
                throw new RuntimeException('Cannot commit plan creation.');
            }

            return $pid;
        } catch (Throwable $e) {
            if ($started_transaction && $pdo->inTransaction()) $pdo->rollBack();
            return new WP_Error($e instanceof InvalidArgumentException ? 'invalid_data' : 'insert_error', $e->getMessage());
        }
    }

    /**
     * Delete / Retire plan in WHMCS
     */
    public function delete_whmcs_plan($pid) {
        if (Rackmonk_Website_Cart::get_config($pid)) return false;
        $pdo = $this->get_whmcs_db();
        if (!$pdo) {
            return false;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id FROM tblproducts WHERE id = ? FOR UPDATE');
            $stmt->execute([intval($pid)]);
            if (!$stmt->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }
            // Native services and configurable-option IDs must not be orphaned by dashboard deletion.
            foreach (['SELECT COUNT(*) FROM tblhosting WHERE packageid = ?', 'SELECT COUNT(*) FROM tblproductconfiglinks WHERE pid = ?'] as $query) {
                $stmt = $pdo->prepare($query);
                $stmt->execute([intval($pid)]);
                if ($stmt->fetchColumn()) {
                    $pdo->rollBack();
                    return false;
                }
            }
            $stmt = $pdo->prepare("DELETE FROM tblproducts WHERE id = ?");
            $stmt->execute([intval($pid)]);
            $stmtP = $pdo->prepare("DELETE FROM tblpricing WHERE type = 'product' AND relid = ?");
            $stmtP->execute([intval($pid)]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return false;
        }
    }

    /**
     * Create a new category / product group in WHMCS
     */
    public function create_whmcs_group($name, $tagline = '') {
        $pdo = $this->get_whmcs_db();
        if (!$pdo) {
            return false;
        }

        try {
            $slug = sanitize_title($name);
            $stmt = $pdo->prepare("
                INSERT INTO tblproductgroups (name, slug, headline, tagline, `order`, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, NOW(), NOW())
            ");
            $stmt->execute([$name, $slug, $name, $tagline]);
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    private function get_fallback_plans($group_id = 1) {
        return [
            $this->format_product([
                'id' => 1,
                'gid' => 1,
                'group_name' => 'Shared Hosting',
                'name' => 'Starter Shared',
                'description' => "1 Website Hosted\n25 GB NVMe SSD Storage\nFree SSL Certificate\n100 GB Bandwidth\n1 Email Account\nDirectAdmin / cPanel\n99.9% Uptime Guarantee",
                'monthly' => 249.00,
                'annually' => 1668.00,
                'biennially' => 2616.00,
                'triennially' => 3792.00,
            ]),
            $this->format_product([
                'id' => 2,
                'gid' => 1,
                'group_name' => 'Shared Hosting',
                'name' => 'Premium Shared',
                'description' => "100 Websites Hosted\n100 GB High Speed NVMe Storage\nFree .COM / .IN Domain Included\nFree Unlimited SSL Certificates\nUnlimited Bandwidth\nFree Professional Email Accounts\nDaily Automatic Backups\n24/7 Priority Support",
                'monthly' => 499.00,
                'annually' => 2988.00,
                'biennially' => 4776.00,
                'triennially' => 7152.00,
            ]),
            $this->format_product([
                'id' => 3,
                'gid' => 1,
                'group_name' => 'Shared Hosting',
                'name' => 'Business Shared',
                'description' => "300 Websites Hosted\n200 GB High Speed NVMe Storage\nFree Domain Name Included\nFree Unlimited SSL Certificates\nUnlimited Bandwidth\nDedicated IP Address Included\nFree CDN & Daily Backups\nImunify360 Security Suite",
                'monthly' => 799.00,
                'annually' => 4788.00,
                'biennially' => 7896.00,
                'triennially' => 11952.00,
            ])
        ];
    }
}
