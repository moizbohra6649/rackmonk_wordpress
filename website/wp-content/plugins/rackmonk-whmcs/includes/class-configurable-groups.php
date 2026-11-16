<?php
/** Native group overview and explicit, preservation-aware editing of one existing choice. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Configurable_Groups {
    const CYCLES = [
        'monthly' => ['label' => '1 Month', 'setup' => 'msetupfee'],
        'quarterly' => ['label' => '3 Months', 'setup' => 'qsetupfee'],
        'semiannually' => ['label' => '6 Months', 'setup' => 'ssetupfee'],
        'annually' => ['label' => '12 Months', 'setup' => 'asetupfee'],
        'biennially' => ['label' => '24 Months', 'setup' => 'bsetupfee'],
        'triennially' => ['label' => '36 Months', 'setup' => 'tsetupfee'],
        'quadrennially' => ['label' => '48 Months', 'setup' => 'quadsetupfee'],
    ];
    const TYPES = [1 => 'Dropdown', 2 => 'Radio', 3 => 'Yes / No', 4 => 'Quantity'];

    public static function overview($pdo) {
        if (!$pdo) return [];
        $groups = $pdo->query('SELECT g.*, (SELECT COUNT(*) FROM tblproductconfigoptions o WHERE o.gid = g.id) AS option_count, (SELECT COUNT(*) FROM tblproductconfigoptionssub s JOIN tblproductconfigoptions o ON o.id = s.configid WHERE o.gid = g.id) AS choice_count FROM tblproductconfiggroups g ORDER BY g.id')->fetchAll(PDO::FETCH_ASSOC);
        $links = $pdo->query('SELECT l.gid, p.id, p.name, p.gid AS category_id FROM tblproductconfiglinks l JOIN tblproducts p ON p.id = l.pid ORDER BY l.gid, p.id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($groups as &$group) {
            $group['products'] = array_values(array_filter($links, function ($link) use ($group) { return (int) $link['gid'] === (int) $group['id']; }));
        }
        unset($group);
        return $groups;
    }

    public static function group($pdo, $gid) {
        if (!$pdo) throw new RuntimeException('WHMCS is disconnected. Native customization is unavailable.');
        $stmt = $pdo->prepare('SELECT * FROM tblproductconfiggroups WHERE id = ?');
        $stmt->execute([$gid]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) throw new InvalidArgumentException('This configuration group no longer exists. Reload the group list.');
        $stmt = $pdo->prepare('SELECT l.pid, p.name FROM tblproductconfiglinks l JOIN tblproducts p ON p.id = l.pid WHERE l.gid = ? ORDER BY l.pid');
        $stmt->execute([$gid]);
        $state = ['group' => $group, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'options' => []];
        $stmt = $pdo->prepare('SELECT * FROM tblproductconfigoptions WHERE gid = ? ORDER BY `order`, id');
        $stmt->execute([$gid]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $option) {
            $option['choices'] = [];
            $state['options'][(int) $option['id']] = $option;
        }
        $stmt = $pdo->prepare('SELECT s.* FROM tblproductconfigoptionssub s JOIN tblproductconfigoptions o ON o.id = s.configid WHERE o.gid = ? ORDER BY s.sortorder, s.id');
        $stmt->execute([$gid]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $choice) $state['options'][(int) $choice['configid']]['choices'][(int) $choice['id']] = $choice;
        $stmt = $pdo->prepare("SELECT pr.* FROM tblpricing pr JOIN tblproductconfigoptionssub s ON s.id = pr.relid JOIN tblproductconfigoptions o ON o.id = s.configid WHERE o.gid = ? AND pr.type = 'configoptions' ORDER BY pr.id");
        $stmt->execute([$gid]);
        $state['prices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $state['currencies'] = $pdo->query('SELECT id, code, prefix, `default` FROM tblcurrencies ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        return $state;
    }

    public static function version($state) { return hash('sha256', serialize($state)); }

    public static function clean_prices($amounts) {
        $columns = [];
        foreach (self::CYCLES as $column => $cycle) { $columns[] = $column; $columns[] = $cycle['setup']; }
        if (!is_array($amounts) || count($amounts) !== count($columns) || array_diff(array_keys($amounts), $columns)) throw new InvalidArgumentException('Incomplete or invalid billing fields. Nothing was saved.');
        $clean = [];
        foreach ($columns as $column) {
            $amount = $amounts[$column] ?? null;
            if (!is_scalar($amount) || !preg_match('/^(?:-1(?:\.0{1,2})?|\d{1,8}(?:\.\d{1,2})?)$/D', (string) $amount)) throw new InvalidArgumentException('Use prices from 0 to 99999999.99, or -1 for a disabled native billing value.');
            $clean[$column] = number_format((float) $amount, 2, '.', '');
        }
        return $clean;
    }

    public static function save_choice($pdo, $gid, $oid, $cid, $input, $version) {
        if (!$pdo) throw new RuntimeException('WHMCS is disconnected. Nothing was saved.');
        if (!is_array($input)) throw new InvalidArgumentException('Invalid native choice settings.');
        $pdo->beginTransaction();
        try {
            // Lock the edited records before checking the revision, including native admin price edits.
            foreach ([
                ['SELECT id FROM tblproductconfiggroups WHERE id = ? FOR UPDATE', [$gid]],
                ['SELECT id FROM tblproductconfigoptions WHERE id = ? AND gid = ? FOR UPDATE', [$oid, $gid]],
                ['SELECT id FROM tblproductconfigoptionssub WHERE id = ? AND configid = ? FOR UPDATE', [$cid, $oid]],
            ] as [$sql, $args]) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($args);
                if (!$stmt->fetchColumn()) throw new InvalidArgumentException('This choice does not belong to the selected option and group.');
            }
            $stmt = $pdo->prepare("SELECT id FROM tblpricing WHERE type = 'configoptions' AND relid = ? ORDER BY id FOR UPDATE");
            $stmt->execute([$cid]);
            $stmt->fetchAll(PDO::FETCH_COLUMN);
            $state = self::group($pdo, $gid);
            if (!is_string($version) || !hash_equals(self::version($state), $version)) throw new RuntimeException('This group changed since you opened it. Reload before saving.');
            $option = $state['options'][$oid];
            if ((int) $option['optiontype'] !== 1) throw new InvalidArgumentException('Edit non-dropdown option types in native WHMCS.');
            $choice = $option['choices'][$cid];
            if (!is_string($input['label'] ?? null)) throw new InvalidArgumentException('Enter a choice label.');
            // Native values may contain provisioning tokens (%xx or value|label). Never normalize an untouched value.
            $label = $input['label'] === $choice['optionname'] ? $choice['optionname'] : sanitize_text_field($input['label']);
            if ($label === '' || strlen($label) > 255) throw new InvalidArgumentException('Use a choice label of 1-255 characters.');
            foreach ($option['choices'] as $other) {
                if ((int) $other['id'] !== $cid && strcasecmp($label, $other['optionname']) === 0) throw new InvalidArgumentException('This option already has a choice with that label.');
            }
            $sort = $input['sortorder'] ?? null;
            if (!is_scalar($sort) || !ctype_digit((string) $sort) || (int) $sort > 9999) throw new InvalidArgumentException('Sort order must be a whole number from 0 to 9999.');
            $hidden = $input['hidden'] ?? '0';
            if (!in_array($hidden, [false, true, 0, 1, '0', '1'], true)) throw new InvalidArgumentException('Invalid choice visibility.');
            $hidden = (int) (bool) $hidden;
            if ($hidden && !(int) $option['hidden']) {
                $visible = array_filter($option['choices'], function ($other) use ($cid) { return (int) $other['id'] !== $cid && !(int) $other['hidden']; });
                if (!$visible) throw new InvalidArgumentException('Keep at least one choice visible for an enabled option.');
            }
            $currencies = array_map('intval', array_column($state['currencies'], 'id'));
            $prices = [];
            foreach ($state['prices'] as $row) {
                if ((int) $row['relid'] !== $cid) continue;
                $currency = (int) $row['currency'];
                if (!in_array($currency, $currencies, true) || isset($prices[$currency])) throw new RuntimeException('Unknown currency or duplicate pricing rows. Review this choice in WHMCS.');
                $prices[$currency] = $row;
            }
            $submitted = $input['prices'] ?? [];
            if (!is_array($submitted) || count($submitted) !== count($prices)) throw new InvalidArgumentException('The pricing form was incomplete. Nothing was saved.');
            $updates = [];
            foreach ($submitted as $currency => $amounts) {
                if (!ctype_digit((string) $currency) || !isset($prices[$currency])) throw new InvalidArgumentException('Invalid currency or billing fields.');
                $row = $prices[$currency];
                $changes = [];
                foreach (self::clean_prices($amounts) as $column => $amount) {
                    if ($amount !== number_format((float) $row[$column], 2, '.', '')) $changes[$column] = $amount;
                }
                if ($changes) $updates[] = ['id' => $row['id'], 'changes' => $changes];
            }
            if ($label !== $choice['optionname'] || (int) $sort !== (int) $choice['sortorder'] || $hidden !== (int) $choice['hidden']) {
                $pdo->prepare('UPDATE tblproductconfigoptionssub SET optionname = ?, sortorder = ?, hidden = ? WHERE id = ? AND configid = ?')->execute([$label, (int) $sort, $hidden, $cid, $oid]);
            }
            foreach ($updates as $update) {
                $set = implode(', ', array_map(function ($column) { return '`' . $column . '` = ?'; }, array_keys($update['changes'])));
                $pdo->prepare('UPDATE tblpricing SET ' . $set . ' WHERE id = ?')->execute(array_merge(array_values($update['changes']), [$update['id']]));
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function render($groups, $plans, $categories, $pdo, $whmcs_url, $selection = null) {
        $query = $selection ?? $_GET;
        $category = isset($query['config_category']) && is_scalar($query['config_category']) ? absint($query['config_category']) : 0;
        $pid = isset($query['config_plan']) && is_scalar($query['config_plan']) ? absint($query['config_plan']) : 0;
        $filtered = array_values(array_filter($groups, function ($group) use ($category, $pid) {
            if (!$category && !$pid) return true;
            foreach ($group['products'] as $product) if ((!$category || (int) $product['category_id'] === $category) && (!$pid || (int) $product['id'] === $pid)) return true;
            return false;
        }));
        $gid = isset($query['native_group']) && is_scalar($query['native_group']) ? absint($query['native_group']) : 0;
        if (!$gid) $gid = (int) ($filtered[0]['id'] ?? 0);
        $state = null;
        $error = '';
        try {
            if ($gid) {
                if (!in_array($gid, array_map('intval', array_column($filtered, 'id')), true)) throw new InvalidArgumentException('The selected group does not match these filters. Choose a group from the list.');
                $state = self::group($pdo, $gid);
            }
        } catch (Throwable $exception) { $error = $exception instanceof PDOException ? 'Native groups could not be loaded. Check the WHMCS connection.' : $exception->getMessage(); }
        $native_url = $whmcs_url . 'admin/configproductoptions.php';
        ?>
        <section id="configuration-groups" class="rm-panel rm-resource-panel" data-admin-section="groups" data-admin-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-admin-nonce="<?php echo esc_attr(wp_create_nonce('rackmonk_admin_section')); ?>">
            <div class="rm-section-heading"><div><p class="rm-eyebrow">NATIVE WHMCS CUSTOMIZATION</p><h2>Configuration groups <span class="rm-count"><?php echo count($groups); ?></span></h2><p>Real option groups, linked plans and independent currency prices. Product categories are separate.</p></div><a class="rm-button rm-button-secondary" href="<?php echo esc_url($native_url); ?>" target="_blank" rel="noopener noreferrer">Manage groups in WHMCS</a></div>
            <div class="rm-native-load-status" data-admin-status role="status" aria-live="polite" hidden><span data-admin-message></span><button type="button" class="rm-button rm-button-secondary" data-admin-retry hidden>Retry</button></div>
            <div class="rm-resource-picker">
                <label for="rm-config-category">Category</label><select id="rm-config-category" name="config_category" data-admin-select><option value="0">All product categories</option><?php foreach ($categories as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($category, $item['id']); ?>><?php echo esc_html($item['name']); ?></option><?php endforeach; ?></select>
                <label for="rm-config-plan">Plan</label><select id="rm-config-plan" name="config_plan" data-admin-select><option value="0">All plans</option><?php foreach ($plans as $item): if ($category && (int) $item['gid'] !== $category) continue; ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($pid, $item['id']); ?>><?php echo esc_html($item['name']); ?></option><?php endforeach; ?></select>
                <label for="rm-config-group">Group</label><select id="rm-config-group" name="native_group" data-admin-select><?php foreach ($filtered as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($gid, $item['id']); ?>><?php echo esc_html($item['name']); ?></option><?php endforeach; ?><?php if (!$filtered): ?><option value="0">No matching groups</option><?php endif; ?></select>
            </div>
            <div class="rm-table-scroll rm-group-list" role="region" aria-label="Native configuration groups" tabindex="0">
                <table class="rm-groups-table"><thead><tr><th>Configuration group</th><th>Linked plans</th><th>Options / choices</th><th>Manage</th></tr></thead><tbody>
                <?php foreach ($filtered as $group): ?>
                    <tr data-native-group="<?php echo esc_attr($group['id']); ?>" <?php echo (int) $group['id'] === $gid ? 'class="is-selected"' : ''; ?>>
                        <td><strong><?php echo esc_html($group['name']); ?></strong><small>Group #<?php echo esc_html($group['id']); ?></small></td>
                        <td><?php foreach ($group['products'] as $product): ?><span class="rm-linked-plan"><?php echo esc_html($product['name']); ?></span><?php endforeach; ?><?php if (!$group['products']): ?><span class="rm-native-muted">Not linked to a plan</span><?php endif; ?><?php if (count($group['products']) > 1): ?><small class="rm-native-warning">Shared: edits affect all linked plans</small><?php endif; ?></td>
                        <td><?php echo esc_html($group['option_count'] . ' options / ' . $group['choice_count'] . ' choices'); ?></td>
                        <td><a class="rm-button rm-button-secondary" data-admin-group="<?php echo esc_attr($group['id']); ?>" href="<?php echo esc_url(add_query_arg(['page' => 'rackmonk-whmcs-plans', 'native_group' => $group['id']], admin_url('admin.php')) . '#configuration-groups'); ?>">Open group</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filtered): ?><tr><td colspan="4"><div class="rm-empty"><strong><?php echo $pdo ? 'No matching configuration groups' : 'WHMCS is disconnected'; ?></strong><p>Adjust the filters or create and link a group in native WHMCS.</p></div></td></tr><?php endif; ?>
                </tbody></table>
            </div>
            <?php if ($error): ?><div class="rm-alert" role="alert"><?php echo esc_html($error); ?></div><?php endif; ?>
            <?php if ($state) self::render_detail($state, $query['native_option'] ?? null, $query['native_choice'] ?? null, $whmcs_url); ?>
        </section>
        <?php
    }

    public static function render_detail($state, $selected_option, $selected_choice, $whmcs_url) {
                $gid = (int) $state['group']['id'];
                $native_url = $whmcs_url . 'admin/configproductoptions.php';
                $oid = $selected_option === null ? (int) (array_key_first($state['options']) ?? 0) : (is_scalar($selected_option) ? absint($selected_option) : 0);
                $option = $state['options'][$oid] ?? null;
                $choices = $option['choices'] ?? [];
                $cid = $selected_choice === null ? (int) (array_key_first($choices) ?? 0) : (is_scalar($selected_choice) ? absint($selected_choice) : 0);
                $choice = $choices[$cid] ?? null;
                $edit_url = $native_url . '?action=managegroup&id=' . $gid;
            ?>
            <div class="rm-native-detail" data-native-detail data-native-group="<?php echo esc_attr($gid); ?>" data-native-option="<?php echo esc_attr($oid); ?>" data-native-choice="<?php echo esc_attr($cid); ?>" data-native-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-native-nonce="<?php echo esc_attr(wp_create_nonce('rackmonk_native_selection')); ?>">
                <div class="rm-native-load-status" role="status" aria-live="polite" hidden><span data-native-load-message></span><button type="button" class="rm-button rm-button-secondary" data-native-retry hidden>Retry</button></div>
                <div class="rm-section-heading"><div><p class="rm-eyebrow">SELECTED GROUP #<?php echo esc_html($gid); ?></p><h3><?php echo esc_html($state['group']['name']); ?></h3><p><?php echo esc_html(implode(', ', array_column($state['products'], 'name')) ?: 'No linked plans'); ?></p></div><a class="rm-button rm-button-secondary" href="<?php echo esc_url($edit_url); ?>" target="_blank" rel="noopener noreferrer">Add options / manage links</a></div>
                <?php if (count($state['products']) > 1): ?><div class="rm-resource-explanation rm-native-warning">This is a shared native group. Saving a choice changes it for every linked plan listed above.</div><?php endif; ?>
                <div class="rm-resource-picker"><label for="rm-native-option">Option</label><select id="rm-native-option" name="native_option" data-native-select="option"><?php foreach ($state['options'] as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($oid, $item['id']); ?>><?php echo esc_html($item['optionname'] . ' (' . count($item['choices']) . ' choices)' . ($item['hidden'] ? ' - hidden' : '')); ?></option><?php endforeach; ?></select></div>
                <?php if ($option): ?>
                <div class="rm-resource-picker"><label for="rm-native-choice">Choice</label><select id="rm-native-choice" name="native_choice" data-native-select="choice"><?php foreach ($choices as $item): ?><option value="<?php echo esc_attr($item['id']); ?>" <?php selected($cid, $item['id']); ?>><?php echo esc_html($item['optionname'] . ($item['hidden'] ? ' - hidden' : '')); ?></option><?php endforeach; ?></select><span class="rm-subtle-label"><?php echo esc_html(self::TYPES[(int) $option['optiontype']] ?? 'Custom type'); ?></span></div>
                <?php endif; ?>
                <?php if ($choice && (int) $option['optiontype'] === 1):
                    $prices = [];
                    $price_error = false;
                    $currency_ids = array_map('intval', array_column($state['currencies'], 'id'));
                    foreach ($state['prices'] as $row) {
                        if ((int) $row['relid'] !== $cid) continue;
                        if (isset($prices[(int) $row['currency']]) || !in_array((int) $row['currency'], $currency_ids, true)) $price_error = true;
                        $prices[(int) $row['currency']] = $row;
                    }
                ?>
                <form method="post" class="rm-native-choice-editor">
                    <?php wp_nonce_field('rackmonk_native_choice_' . $gid . '_' . $oid . '_' . $cid); ?>
                    <input type="hidden" name="rackmonk_action" value="save_native_choice"><input type="hidden" name="native_group" value="<?php echo esc_attr($gid); ?>"><input type="hidden" name="native_option" value="<?php echo esc_attr($oid); ?>"><input type="hidden" name="native_choice" value="<?php echo esc_attr($cid); ?>"><input type="hidden" name="native_version" value="<?php echo esc_attr(self::version($state)); ?>">
                    <div class="rm-form-section rm-form-grid">
                        <div class="rm-field"><label for="rm-native-label">Choice label / native value</label><input id="rm-native-label" name="native_settings[label]" type="text" maxlength="255" required value="<?php echo esc_attr($choice['optionname']); ?>"><small class="rm-field-help">Existing native value|display-label syntax is retained.</small></div>
                        <div class="rm-field"><label for="rm-native-sort">Sort order</label><input id="rm-native-sort" name="native_settings[sortorder]" type="number" min="0" max="9999" required value="<?php echo esc_attr($choice['sortorder']); ?>"></div>
                        <label class="rm-native-visibility"><input name="native_settings[hidden]" type="checkbox" value="1" <?php checked(!empty($choice['hidden'])); ?>> Hide this choice from clients</label>
                    </div>
                    <div class="rm-resource-explanation"><strong>Native WHMCS prices, not website-cart prices.</strong> Enter the total upgrade amount for each billing term. Setup fees are separate. Values are never multiplied or currency-converted on save. Native WHMCS uses 36 months for triennial billing; <code>-1.00</code> is a disabled native billing value.</div>
                    <?php if ($price_error): ?><div class="rm-alert" role="alert">Duplicate or unknown-currency pricing rows found. Resolve them in WHMCS before saving.</div><?php endif; ?>
                    <?php foreach ($state['currencies'] as $currency): $row = $prices[(int) $currency['id']] ?? null; ?>
                        <div class="rm-native-currency"><h4><?php echo esc_html($currency['code'] . ' (' . $currency['prefix'] . ')'); ?></h4>
                        <?php if (!$row): ?><p class="rm-native-muted">No native price row for this currency. Add it in WHMCS; saving here will not create or infer prices.</p>
                        <?php else: ?>
                        <div class="rm-table-scroll" role="region" aria-label="<?php echo esc_attr($currency['code'] . ' billing prices'); ?>" tabindex="0"><table class="rm-native-prices"><thead><tr><th>Price type</th><?php foreach (self::CYCLES as $cycle): ?><th><?php echo esc_html($cycle['label']); ?></th><?php endforeach; ?></tr></thead><tbody>
                        <?php foreach (['setup' => 'Setup fee', 'recurring' => 'Upgrade total'] as $kind => $label): ?><tr><th><?php echo esc_html($label); ?></th><?php foreach (self::CYCLES as $column => $cycle): $field = $kind === 'setup' ? $cycle['setup'] : $column; ?><td><input type="number" min="-1" max="99999999.99" step="0.01" required name="native_settings[prices][<?php echo esc_attr($currency['id']); ?>][<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr(number_format((float) $row[$field], 2, '.', '')); ?>" aria-label="<?php echo esc_attr($currency['code'] . ' ' . $cycle['label'] . ' ' . $label); ?>"></td><?php endforeach; ?></tr><?php endforeach; ?>
                        </tbody></table></div>
                        <?php endif; ?></div>
                    <?php endforeach; ?>
                    <input type="hidden" name="native_complete" value="1">
                    <div class="rm-form-footer"><span>Only this choice is updated. Other choices, base prices, group links and invoices remain unchanged.</span><button class="rm-button rm-button-primary" type="submit" <?php disabled($price_error); ?>>Save native choice</button></div>
                </form>
                <?php elseif ($option && (int) $option['optiontype'] !== 1): ?><div class="rm-resource-explanation">This native option type is shown for reference. Edit its settings in WHMCS to preserve its specific billing behavior.</div>
                <?php else: ?><div class="rm-resource-explanation">Select a valid option and choice, or add them using the native WHMCS group editor.</div><?php endif; ?>
            </div>
        <?php
    }
}
