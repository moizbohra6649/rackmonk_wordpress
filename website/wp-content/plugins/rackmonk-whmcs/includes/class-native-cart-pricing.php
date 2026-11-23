<?php
/** Read-only adapter for WHMCS recurring products and linked dropdown options. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Native_Cart_Pricing {
    const CYCLES = [
        1 => ['monthly', 'msetupfee'],
        12 => ['annually', 'asetupfee'],
        24 => ['biennially', 'bsetupfee'],
        36 => ['triennially', 'tsetupfee'],
        48 => ['quadrennially', 'quadsetupfee'],
    ];

    private static function rows($pdo, $sql, $args = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function amounts($row) {
        $result = [];
        foreach (self::CYCLES as $months => [$cycle, $setup]) {
            $price = (string) ($row[$cycle] ?? '');
            $fee = (string) ($row[$setup] ?? '');
            if ($months === 48 && $price === '') { $result[$months] = null; continue; }
            if ($price === '-1' || $price === '-1.00' || $price === '-1.0') { $result[$months] = null; continue; }
            if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $fee)) throw new RuntimeException('Unsupported native pricing. Contact support.');
            $result[$months] = ['recurring' => number_format((float) $price, 2, '.', ''), 'setup' => number_format((float) $fee, 2, '.', '')];
        }
        return $result;
    }

    private static function label($value) {
        $parts = explode('|', (string) $value, 2);
        $label = trim(end($parts));
        if ($label === '') throw new RuntimeException('Native option has an empty display label.');
        return $label;
    }

    private static function pricing($pdo, $type, $id, $default, $currency, &$own = false, &$disabled48 = false) {
        $rows = self::rows($pdo, "SELECT * FROM tblpricing WHERE type = '$type' AND relid = ? AND currency = ? ORDER BY id", [$id, $currency['id']]);
        if (count($rows) > 1) throw new RuntimeException('Duplicate native pricing. Contact support.');
        $own = (bool) $rows;
        $disabled48 = $own && $currency['code'] !== $default['code'] && isset($rows[0]['quadrennially']) && (float) $rows[0]['quadrennially'] < 0;
        if ($rows) return self::amounts($rows[0]);
        if ($currency['code'] === $default['code']) throw new RuntimeException('Missing native pricing. Contact support.');
        $rows = self::rows($pdo, "SELECT * FROM tblpricing WHERE type = '$type' AND relid = ? AND currency = ? ORDER BY id", [$id, $default['id']]);
        if (count($rows) !== 1) throw new RuntimeException('Missing or duplicate native default pricing.');
        $amounts = self::amounts($rows[0]);
        // Derive legacy terms before conversion, avoiding multiplication of rounded monthly FX amounts.
        if ($amounts[48] === null) {
            if ($type === 'product' && $amounts[1] !== null) $amounts[48] = ['recurring' => number_format((float) $amounts[1]['recurring'] * 0.32 * 48, 2, '.', ''), 'setup' => '0.00'];
            if ($type === 'configoptions') {
                $months = $amounts[36] !== null ? 36 : 1;
                if ($amounts[$months] !== null) $amounts[48] = ['recurring' => number_format((float) $amounts[$months]['recurring'] / $months * 48, 2, '.', ''), 'setup' => $amounts[$months]['setup']];
            }
        }
        return array_map(function ($amount) use ($currency) { return $amount === null ? null : self::converted($amount, $currency); }, $amounts);
    }

    private static function converted($amount, $currency) {
        if (!class_exists('Rackmonk_Currency')) throw new RuntimeException('Currency conversion is unavailable.');
        foreach (['recurring', 'setup'] as $key) $amount[$key] = number_format(Rackmonk_Currency::convert($amount[$key], $currency), 2, '.', '');
        return $amount;
    }

    public static function config($pdo, $pid, $settings, $selected_currency = null) {
        if (!$pdo) throw new RuntimeException('WHMCS pricing is unavailable.');
        try {
            $currencies = self::rows($pdo, 'SELECT id, code, prefix FROM tblcurrencies WHERE `default` = 1 ORDER BY id');
            $products = self::rows($pdo, 'SELECT id, name, paytype, hidden, retired FROM tblproducts WHERE id = ?', [$pid]);
            if (count($currencies) !== 1 || !preg_match('/^[A-Z]{3}$/D', $currencies[0]['code'] ?? '') || count($products) !== 1 || ($products[0]['paytype'] ?? '') !== 'recurring' || !empty($products[0]['hidden']) || !empty($products[0]['retired'])) throw new RuntimeException('This native plan or its default currency is unavailable or unsupported.');
            $default = $currencies[0];
            $currency = $selected_currency ?: $default;
            if (!is_array($currency) || empty($currency['id']) || !preg_match('/^[A-Z]{3}$/D', $currency['code'] ?? '')) throw new RuntimeException('Unsupported currency.');
            if ($currency['code'] !== $default['code'] && (!isset($currency['rate']) || !is_finite((float) $currency['rate']) || $currency['rate'] <= 0)) throw new RuntimeException('Currency exchange rate is unavailable.');
            $config = ['pid' => (int) $pid, 'name' => $products[0]['name'], 'enabled' => true, 'currency' => $currency['code'], 'symbol' => $currency['prefix'], 'native_pricing' => true, 'prices' => self::pricing($pdo, 'product', $pid, $default, $currency, $ownBase, $disabledBase48), 'resources' => [], 'demo_pricing' => false];
            if ($selected_currency && class_exists('Rackmonk_Currency')) $config['currency_context'] = [$currency, Rackmonk_Currency::default_currency()];
            $manual = $settings['manual_48'] ?? [];
            if (!is_array($manual) || ($manual['currency'] ?? '') !== $default['code']) $manual = [];
            $manualBase = self::manual_amount($manual['base'] ?? null);
            if ($manualBase && $currency['code'] !== $default['code']) $manualBase = self::converted($manualBase, $currency);
            if ($disabledBase48) {
                $config['prices'][48] = null;
            } elseif ($manualBase && !($ownBase && $currency['code'] !== $default['code'] && $config['prices'][48] !== null)) {
                $config['prices'][48] = $manualBase;
            } elseif ($config['prices'][48] !== null) {
                // Already read directly from WHMCS quadrennially column
            } elseif ($config['prices'][1] !== null) {
                $mPrice = (float) $config['prices'][1]['recurring'];
                $config['prices'][48] = [
                    'recurring' => number_format(round($mPrice * 0.32 * 48, 2), 2, '.', ''),
                    'setup'     => '0.00',
                ];
            }
            $config['base_price'] = $config['prices'][1]['recurring'] ?? '0.00';
            $options = self::rows($pdo, 'SELECT o.* FROM tblproductconfiglinks l JOIN tblproductconfigoptions o ON o.gid = l.gid WHERE l.pid = ? ORDER BY o.gid, o.`order`, o.id', [$pid]);
            foreach ($options as $option) {
                $key = 'option_' . (int) $option['id'];
                if (isset($config['resources'][$key]) || (int) $option['optiontype'] !== 1 || !empty($option['hidden'])) throw new RuntimeException('This plan has duplicate, hidden or unsupported native options.');
                $resource = ['label' => self::label($option['optionname']), 'type' => 'select', 'choices' => []];
                $choices = self::rows($pdo, 'SELECT * FROM tblproductconfigoptionssub WHERE configid = ? ORDER BY sortorder, id', [$option['id']]);
                foreach ($choices as $choice) {
                    if (!empty($choice['hidden'])) continue;
                    $amounts = self::pricing($pdo, 'configoptions', $choice['id'], $default, $currency, $ownChoice, $disabledChoice48);
                    $manualChoice = self::manual_amount($manual['choices'][(int) $option['id']][(int) $choice['id']] ?? null);
                    if ($manualChoice && $currency['code'] !== $default['code']) $manualChoice = self::converted($manualChoice, $currency);
                    if ($disabledChoice48) {
                        $amounts[48] = null;
                    } elseif ($manualChoice && !($ownChoice && $currency['code'] !== $default['code'] && $amounts[48] !== null)) {
                        $amounts[48] = $manualChoice;
                    } elseif ($amounts[48] !== null) {
                        // Keep an explicit native 48-month amount, including a free option.
                    } elseif (isset($amounts[36]) && $amounts[36]) {
                        $amounts[48] = [
                            'recurring' => number_format(round(((float) $amounts[36]['recurring'] / 36) * 48, 2), 2, '.', ''),
                            'setup'     => $amounts[36]['setup'] ?? '0.00',
                        ];
                    } elseif ($amounts[1] !== null) {
                        $optMonthly = (float) $amounts[1]['recurring'];
                        $amounts[48] = [
                            'recurring' => number_format(round($optMonthly * 48, 2), 2, '.', ''),
                            'setup'     => $amounts[1]['setup'] ?? '0.00',
                        ];
                    }
                    $resource['choices'][] = ['id' => (string) $choice['id'], 'label' => self::label($choice['optionname']), 'price' => $amounts[1]['recurring'] ?? '0.00', 'prices' => $amounts];
                }
                if (!$resource['choices']) throw new RuntimeException('A native option has no visible choices.');
                $config['resources'][$key] = $resource;
            }
            return $config;
        } catch (PDOException $error) {
            throw new RuntimeException('WHMCS pricing is unavailable.', 0, $error);
        }
    }

    private static function manual_amount($amount) {
        if (!is_array($amount)) return null;
        foreach (['recurring', 'setup'] as $key) {
            if (!isset($amount[$key]) || !is_string($amount[$key]) || !preg_match('/^\d{1,7}\.\d{2}$/D', $amount[$key])) return null;
        }
        return ['recurring' => $amount['recurring'], 'setup' => $amount['setup']];
    }
}
