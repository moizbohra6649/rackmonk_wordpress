<?php
/**
 * Rackmonk WHMCS 48-Month (Quadrennially) Pricing Integration
 * Stores 48-month pricing in tblpricing.quadrennially and tblpricing.quadsetupfee
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// 1. Add 48 Months fields to WHMCS Product Configuration
add_hook('AdminProductConfigFields', 1, function ($vars) {
    $pid = (int) ($vars['pid'] ?? 0);
    if ($pid <= 0) {
        return [];
    }

    try {
        $db = \WHMCS\Database\Capsule::connection()->getPdo();
        $stmt = $db->prepare("SELECT currency, quadrennially, quadsetupfee FROM tblpricing WHERE type = 'product' AND relid = ?");
        $stmt->execute([$pid]);
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $currencies = $db->query("SELECT id, code, prefix FROM tblcurrencies ORDER BY `default` DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $fields = [];
        foreach ($currencies as $curr) {
            $row = null;
            foreach ($prices as $p) {
                if ((int)$p['currency'] === (int)$curr['id']) {
                    $row = $p;
                    break;
                }
            }
            $qPrice = $row ? $row['quadrennially'] : '0.00';
            $qSetup = $row ? $row['quadsetupfee'] : '0.00';

            $html = '<div style="display:flex;gap:15px;align-items:center;">'
                  . '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px;">48 Months Total (' . htmlspecialchars($curr['prefix']) . ')</label>'
                  . '<input type="number" step="0.01" name="rm_quadrennially[' . (int)$curr['id'] . ']" value="' . htmlspecialchars($qPrice) . '" class="form-control" style="width:140px;display:inline-block;"></div>'
                  . '<div><label style="font-size:12px;font-weight:600;display:block;margin-bottom:3px;">48 Months Setup Fee (' . htmlspecialchars($curr['prefix']) . ')</label>'
                  . '<input type="number" step="0.01" name="rm_quadsetupfee[' . (int)$curr['id'] . ']" value="' . htmlspecialchars($qSetup) . '" class="form-control" style="width:140px;display:inline-block;"></div>'
                  . '</div><span class="text-muted" style="font-size:11px;">Directly saved to <code>tblpricing.quadrennially</code> for 48 months pricing.</span>';

            $fields['48 Months Price (' . $curr['code'] . ')'] = $html;
        }

        return $fields;
    } catch (\Throwable $e) {
        return [];
    }
});

// 2. Save 48 Months pricing on product save
add_hook('ProductEdit', 1, function ($vars) {
    $pid = (int) ($vars['pid'] ?? 0);
    if ($pid <= 0) {
        return;
    }

    try {
        $db = \WHMCS\Database\Capsule::connection()->getPdo();

        if (!empty($_POST['rm_quadrennially']) && is_array($_POST['rm_quadrennially'])) {
            foreach ($_POST['rm_quadrennially'] as $currId => $val) {
                $currId = (int) $currId;
                $price = floatval($val);
                $setup = isset($_POST['rm_quadsetupfee'][$currId]) ? floatval($_POST['rm_quadsetupfee'][$currId]) : 0.00;

                $stmt = $db->prepare("UPDATE tblpricing SET quadrennially = ?, quadsetupfee = ? WHERE type = 'product' AND relid = ? AND currency = ?");
                $stmt->execute([$price, $setup, $pid, $currId]);
            }
        }
    } catch (\Throwable $e) {
        logActivity('Rackmonk: Error saving 48-month pricing for product #' . $pid . ': ' . $e->getMessage());
    }
});

