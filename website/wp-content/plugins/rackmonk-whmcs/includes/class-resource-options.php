<?php
/** Native WHMCS configurable options owned by the Rackmonk resource editor. */
if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_Resource_Options {
    const RESOURCES = [
        'os' => 'Operating System', 'ram' => 'Memory', 'storage' => 'Primary Disk',
        'secondary_disk' => 'Secondary Disk', 'backup_disk' => 'Backup Disk (Recommend)',
        'raid' => 'RAID Configuration', 'control_panel' => 'Control Panel', 'antivirus' => 'Premium Antivirus',
        'firewall' => 'Firewall', 'ip_addresses' => 'IP Addresses', 'port_speed' => 'Port Speed', 'data_transfer' => 'DataTransfer',
        'cpu' => 'CPU', 'websites' => 'Websites',
    ];

    public static function is_configurable($pdo, $pid) {
        static $products = null;
        if (!$pdo) return false;
        if ($products === null) {
            $products = $pdo->query("SELECT DISTINCT l.pid FROM tblproductconfiglinks l JOIN tblproductconfiggroups g ON g.id = l.gid JOIN tblproductconfigoptions o ON o.gid = g.id JOIN tblproductconfigoptionssub s ON s.configid = o.id WHERE g.description = CONCAT('rackmonk-resource-builder:product:', l.pid) AND o.hidden = 0 AND s.hidden = 0")->fetchAll(PDO::FETCH_COLUMN);
        }
        return in_array((string) $pid, array_map('strval', $products), true);
    }

    public static function get_config($pdo, $pid) {
        $result = ['group_id' => 0, 'resources' => []];
        if (!$pdo) {
            return $result;
        }
        $stmt = $pdo->prepare('SELECT g.id FROM tblproductconfiggroups g JOIN tblproductconfiglinks l ON l.gid = g.id WHERE l.pid = ? AND g.description = ?');
        $stmt->execute([$pid, 'rackmonk-resource-builder:product:' . $pid]);
        $groups = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($groups) > 1) {
            throw new RuntimeException('Multiple resource groups found. Review this product in WHMCS before editing.');
        }
        $result['group_id'] = (int) ($groups[0] ?? 0);
        if (!$result['group_id']) {
            return $result;
        }
        $stmt = $pdo->prepare('SELECT o.id, o.optionname, o.optiontype, o.qtyminimum, o.qtymaximum, o.hidden, s.id AS choice_id, s.optionname AS label, s.hidden AS choice_hidden, pr.monthly AS price FROM tblproductconfigoptions o LEFT JOIN tblproductconfigoptionssub s ON s.configid = o.id LEFT JOIN tblpricing pr ON pr.relid = s.id AND pr.type = \'configoptions\' AND pr.currency = (SELECT id FROM tblcurrencies WHERE `default` = 1 LIMIT 1) WHERE o.gid = ? ORDER BY o.`order`, o.id, s.sortorder, s.id');
        $stmt->execute([$result['group_id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = array_search($row['optionname'], self::RESOURCES, true);
            if ($key === false) {
                throw new RuntimeException('This resource group contains options not managed by this editor. Edit it in WHMCS.');
            }
            if ((int) $row['optiontype'] !== 1 || (int) $row['qtyminimum'] !== 0 || (int) $row['qtymaximum'] !== 0) {
                throw new RuntimeException('This group contains quantity or other unsupported option types. Edit these options in WHMCS.');
            }
            if (isset($result['resources'][$key]) && $result['resources'][$key]['id'] !== (int) $row['id']) {
                throw new RuntimeException('Duplicate resource names found. Review this product in WHMCS.');
            }
            if (!isset($result['resources'][$key])) {
                $result['resources'][$key] = ['id' => (int) $row['id'], 'hidden' => (bool) $row['hidden'], 'choices' => []];
            }
            if ($row['choice_id']) {
                $result['resources'][$key]['choices'][] = ['id' => (int) $row['choice_id'], 'label' => $row['label'], 'price' => $row['price'], 'hidden' => (bool) $row['choice_hidden']];
            }
        }
        $stmt = $pdo->prepare("SELECT pr.* FROM tblpricing pr JOIN tblproductconfigoptionssub s ON s.id = pr.relid JOIN tblproductconfigoptions o ON o.id = s.configid WHERE o.gid = ? AND pr.type = 'configoptions' ORDER BY pr.id");
        $stmt->execute([$result['group_id']]);
        $result['native_prices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function validate($input, $config) {
        if (!is_array($input)) {
            throw new InvalidArgumentException('Invalid resource configuration.');
        }
        $clean = [];
        $seen = [];
        foreach (self::RESOURCES as $key => $name) {
            $rows = $input[$key] ?? [];
            if (!is_array($rows) || count($rows) > 30) {
                throw new InvalidArgumentException($name . ': use at most 30 choices.');
            }
            $allowed_ids = array_column($config['resources'][$key]['choices'] ?? [], 'id');
            $labels = [];
            $clean[$key] = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_scalar($row['label'] ?? '') || !is_scalar($row['price'] ?? '') || !is_scalar($row['id'] ?? '0')) {
                    throw new InvalidArgumentException($name . ': invalid choice.');
                }
                $id_text = (string) ($row['id'] ?? '0');
                if (!ctype_digit($id_text)) {
                    throw new InvalidArgumentException($name . ': invalid choice ID.');
                }
                $id = (int) $id_text;
                if ($id && (!in_array($id, $allowed_ids, true) || isset($seen[$id]))) {
                    throw new InvalidArgumentException($name . ': choice does not belong to this resource or is duplicated.');
                }
                if ($id) $seen[$id] = true;
                $label = sanitize_text_field((string) ($row['label'] ?? ''));
                $price = trim((string) ($row['price'] ?? ''));
                if (!$id && $label === '' && $price === '') continue;
                if ($label === '' || strlen($label) > 100 || strpos($label, '|') !== false || isset($labels[strtolower($label)])) {
                    throw new InvalidArgumentException($name . ': use unique labels of 1-100 characters without a pipe (|).');
                }
                // No exponents, negatives or client-supplied totals; store decimal monthly rates.
                if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/D', $price)) {
                    throw new InvalidArgumentException($name . ': enter a monthly price from 0 to 9999999.99 with up to two decimals.');
                }
                $labels[strtolower($label)] = true;
                $hidden = $row['hidden'] ?? false;
                if (!in_array($hidden, [false, true, 0, 1, '0', '1'], true)) throw new InvalidArgumentException($name . ': invalid hidden flag.');
                $clean[$key][] = ['id' => $id, 'label' => $label, 'price' => number_format((float) $price, 2, '.', ''), 'hidden' => (bool) $hidden];
            }
            if ($clean[$key] && !array_filter($clean[$key], function ($choice) { return !$choice['hidden']; })) throw new InvalidArgumentException($name . ': keep at least one choice visible.');
        }
        return $clean;
    }

    public static function save($pdo, $pid, $input, $version, $group_name = '') {
        if (!$pdo) throw new RuntimeException('WHMCS is disconnected. No options were saved.');
        $pdo->beginTransaction();
        try {
            // Serialize edits per product, including concurrent first-time group creation.
            $stmt = $pdo->prepare('SELECT id FROM tblproducts WHERE id = ? FOR UPDATE');
            $stmt->execute([$pid]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Product no longer exists.');
            $config = self::get_config($pdo, $pid);
            if (!is_string($version) || !hash_equals(hash('sha256', serialize($config)), $version)) {
                throw new RuntimeException('Options changed since this page was loaded. Reload the page before saving.');
            }
            $clean = self::validate($input, $config);
            $currencies = $pdo->query('SELECT id, rate, `default` FROM tblcurrencies')->fetchAll(PDO::FETCH_ASSOC);
            $default_rate = 0;
            foreach ($currencies as $currency) {
                if ($currency['default']) $default_rate = (float) $currency['rate'];
                if ((float) $currency['rate'] <= 0) throw new RuntimeException('WHMCS currency rates must be positive.');
            }
            if (!$default_rate) throw new RuntimeException('Set a default WHMCS currency first.');
            $gid = $config['group_id'];
            if (!$gid && !array_filter($clean)) {
                $pdo->commit();
                return;
            }
            if (!$gid) {
                $stmt = $pdo->prepare('SELECT l.gid FROM tblproductconfiglinks l JOIN tblproductconfigoptions o ON o.gid = l.gid WHERE l.pid = ? LIMIT 1');
                $stmt->execute([$pid]);
                if ($stmt->fetchColumn()) throw new RuntimeException('This product already has populated native configurable option groups. Review them in WHMCS before adding another group.');
                $stmt = $pdo->prepare('INSERT INTO tblproductconfiggroups (name, description) VALUES (?, ?)');
                $name = $group_name !== '' ? substr(sanitize_text_field($group_name), 0, 100) : 'Rackmonk Resources - Product #' . $pid;
                $stmt->execute([$name, 'rackmonk-resource-builder:product:' . $pid]);
                $gid = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO tblproductconfiglinks (gid, pid) VALUES (?, ?)')->execute([$gid, $pid]);
            } else {
                $stmt = $pdo->prepare('SELECT pid FROM tblproductconfiglinks WHERE gid = ?');
                $stmt->execute([$gid]);
                $links = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($links) !== 1 || (int) $links[0] !== $pid) throw new RuntimeException('This resource group is shared. Edit it in WHMCS to avoid changing other products.');
            }
            $position = 0;
            foreach (self::RESOURCES as $key => $name) {
                $option_id = $config['resources'][$key]['id'] ?? 0;
                if (!$option_id && !$clean[$key]) continue;
                if (!$option_id) {
                    $stmt = $pdo->prepare('INSERT INTO tblproductconfigoptions (gid, optionname, optiontype, qtyminimum, qtymaximum, `order`, hidden) VALUES (?, ?, 1, 0, 0, ?, 0)');
                    $stmt->execute([$gid, $name, $position]);
                    $option_id = (int) $pdo->lastInsertId();
                }
                $pdo->prepare('UPDATE tblproductconfigoptions SET hidden = ?, `order` = ? WHERE id = ?')->execute([empty($clean[$key]) ? 1 : 0, $position++, $option_id]);
                // Keep historical IDs/prices for existing services rather than deleting removed choices.
                $pdo->prepare('UPDATE tblproductconfigoptionssub SET hidden = 1 WHERE configid = ?')->execute([$option_id]);
                foreach ($clean[$key] as $order => $choice) {
                    $choice_id = $choice['id'];
                    if ($choice_id) {
                        $pdo->prepare('UPDATE tblproductconfigoptionssub SET optionname = ?, sortorder = ?, hidden = ? WHERE id = ? AND configid = ?')->execute([$choice['label'], $order, (int) $choice['hidden'], $choice_id, $option_id]);
                    } else {
                        $pdo->prepare('INSERT INTO tblproductconfigoptionssub (configid, optionname, sortorder, hidden) VALUES (?, ?, ?, ?)')->execute([$option_id, $choice['label'], $order, (int) $choice['hidden']]);
                        $choice_id = (int) $pdo->lastInsertId();
                    }
                    foreach ($currencies as $currency) {
                        $monthly = round((float) $choice['price'] * (float) $currency['rate'] / $default_rate, 2);
                        $amounts = [];
                        foreach ([1, 3, 6, 12, 24, 36] as $months) $amounts[] = number_format($monthly * $months, 2, '.', '');
                        $stmt = $pdo->prepare("SELECT id FROM tblpricing WHERE type = 'configoptions' AND currency = ? AND relid = ?");
                        $stmt->execute([$currency['id'], $choice_id]);
                        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        if (count($ids) > 1) throw new RuntimeException('Duplicate WHMCS option prices found. Resolve these in WHMCS.');
                        if ($ids) {
                            $pdo->prepare('UPDATE tblpricing SET monthly = ?, quarterly = ?, semiannually = ?, annually = ?, biennially = ?, triennially = ? WHERE id = ?')->execute(array_merge($amounts, [$ids[0]]));
                        } else {
                            $pdo->prepare("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('configoptions', ?, ?, 0, 0, 0, 0, 0, 0, ?, ?, ?, ?, ?, ?)")->execute(array_merge([$currency['id'], $choice_id], $amounts));
                        }
                    }
                }
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function render_editor($plans, $pdo, $currency, $whmcs_url) {
        $pid = absint($_GET['resource_plan'] ?? ($plans[0]['id'] ?? 0));
        $plan = null;
        foreach ($plans as $candidate) if ((int) $candidate['id'] === $pid) $plan = $candidate;
        if (!$plan) $pid = 0;
        $error = '';
        try {
            $config = self::get_config($pdo, $pid);
        } catch (Throwable $exception) {
            $config = ['group_id' => 0, 'resources' => []];
            $error = $exception->getMessage();
        }
        ?>
        <section id="resource-builder" class="rm-panel rm-resource-panel">
            <div class="rm-section-heading"><div><p class="rm-eyebrow">LET YOUR CLIENTS BUILD THEIR PLAN</p><h2>Customizable resources</h2><p>Set the choices and monthly upgrade prices. Clients configure their own plan at checkout.</p></div><span class="rm-heading-icon dashicons dashicons-admin-settings" aria-hidden="true"></span></div>
            <form method="get" class="rm-resource-picker"><input type="hidden" name="page" value="rackmonk-whmcs-plans"><label for="rm-resource-plan">Choose a base plan</label><select name="resource_plan" id="rm-resource-plan"><?php foreach ($plans as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($pid, $item['id']); ?>><?php echo esc_html($item['name'] . ' (#' . $item['id'] . ')'); ?></option><?php endforeach; ?></select><button class="rm-button rm-button-secondary" type="submit">Load resources</button><?php if ($plan): ?><a class="rm-button rm-button-secondary" href="<?php echo esc_url($whmcs_url . 'cart.php?a=add&pid=' . $pid . '&carttpl=standard_cart'); ?>" target="_blank" rel="noopener noreferrer">Preview client checkout <span class="dashicons dashicons-external" aria-hidden="true"></span></a><?php endif; ?></form>
            <?php if ($error): ?><div class="rm-alert" role="alert"><?php echo esc_html($error); ?></div><?php endif; ?>
            <form method="post" class="rm-resource-editor">
                <?php wp_nonce_field('rackmonk_save_resources_' . $pid); ?>
                <input type="hidden" name="rackmonk_action" value="save_resources"><input type="hidden" name="resource_pid" value="<?php echo esc_attr($pid); ?>"><input type="hidden" name="resource_version" value="<?php echo esc_attr(hash('sha256', serialize($config))); ?>">
                <div class="rm-resource-explanation"><strong>Prices are additional to the base plan.</strong> Use <code>0.00</code> for an included choice. First choice is selected by default. Leave a resource empty to hide it. Rates below are in <?php echo esc_html($currency['code']); ?> per month; longer terms multiply by their actual month count, and other currencies use current WHMCS exchange rates.</div>
                <div class="rm-resource-grid">
                <?php foreach (self::RESOURCES as $key => $name):
                    $choices = array_values(array_filter($config['resources'][$key]['choices'] ?? [], function($choice) { return !$choice['hidden']; }));
                    if (!$choices) $choices = [['id' => 0, 'label' => '', 'price' => '']];
                ?>
                    <fieldset class="rm-resource-card" data-resource="<?php echo esc_attr($key); ?>" data-next-index="<?php echo count($choices); ?>"><legend><?php echo esc_html($name); ?></legend><div class="rm-resource-column-head"><span>Client-visible choice</span><span>Extra / month</span><span></span></div><div data-resource-rows>
                    <?php foreach ($choices as $index => $choice): ?>
                        <div class="rm-resource-choice"><input type="hidden" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][id]" value="<?php echo esc_attr($choice['id']); ?>"><input type="text" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][label]" value="<?php echo esc_attr($choice['label']); ?>" maxlength="100" placeholder="<?php echo esc_attr('e.g. ' . $name . ' choice'); ?>" aria-label="<?php echo esc_attr($name . ' choice label'); ?>"><input type="number" min="0" max="9999999.99" step="0.01" name="resources[<?php echo esc_attr($key); ?>][<?php echo $index; ?>][price]" value="<?php echo esc_attr($choice['price']); ?>" placeholder="0.00" aria-label="<?php echo esc_attr($name . ' monthly additional price'); ?>"><button type="button" class="rm-remove-choice" data-remove-choice aria-label="<?php echo esc_attr('Remove ' . $name . ' choice'); ?>">&times;</button></div>
                    <?php endforeach; ?>
                    </div><button class="rm-add-choice" type="button" data-add-choice>+ Add choice</button></fieldset>
                <?php endforeach; ?>
                </div>
                <div class="rm-resource-explanation"><strong>Native WHMCS checkout:</strong> resource choices and prices are saved as real configurable options, not browser-only totals. WHMCS supports 1, 3, 6, 12, 24 and 36-month billing. Existing service IDs are retained when choices are removed. These options do not automatically map server provisioning limits; configure your WHMCS provisioning module separately.</div>
                <div class="rm-form-footer"><span>Existing resource prices affect new orders; no client invoices are created here.</span><button type="submit" class="rm-button rm-button-primary" <?php disabled(!$pdo || !$pid || (bool) $error); ?>>Save resource options</button></div>
            </form>
        </section>
        <?php
    }
}
