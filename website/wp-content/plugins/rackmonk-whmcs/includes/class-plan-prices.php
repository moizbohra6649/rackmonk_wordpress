<?php
/** Independent native base-plan prices. Existing INR values are never used as USD amounts. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Plan_Prices {
    public static function state($pdo, $pid) {
        if (!$pdo) throw new RuntimeException('WHMCS is disconnected. Plan pricing is unavailable.');
        $stmt = $pdo->prepare('SELECT id, name FROM tblproducts WHERE id = ?');
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new InvalidArgumentException('Choose an existing WHMCS plan.');
        $stmt = $pdo->prepare("SELECT * FROM tblpricing WHERE type = 'product' AND relid = ? ORDER BY id");
        $stmt->execute([$pid]);
        return ['product' => $product, 'prices' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'currencies' => $pdo->query('SELECT id, code, prefix, `default` FROM tblcurrencies ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function version($state) { return hash('sha256', serialize($state)); }

    public static function save_currency($pdo, $pid, $currency, $input, $version) {
        if (!$pdo) throw new RuntimeException('WHMCS is disconnected. Nothing was saved.');
        $amounts = Rackmonk_Configurable_Groups::clean_prices($input);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id FROM tblproducts WHERE id = ? FOR UPDATE');
            $stmt->execute([$pid]);
            if (!$stmt->fetchColumn()) throw new InvalidArgumentException('This WHMCS plan no longer exists.');
            $stmt = $pdo->prepare("SELECT id FROM tblpricing WHERE type = 'product' AND relid = ? ORDER BY id FOR UPDATE");
            $stmt->execute([$pid]);
            $stmt->fetchAll(PDO::FETCH_COLUMN);
            $state = self::state($pdo, $pid);
            if (!is_string($version) || !hash_equals(self::version($state), $version)) throw new RuntimeException('Plan prices changed since this page was loaded. Reload before saving.');
            if (!in_array($currency, array_map('intval', array_column($state['currencies'], 'id')), true)) throw new InvalidArgumentException('Choose an enabled WHMCS currency.');
            $rows = array_values(array_filter($state['prices'], function ($row) use ($currency) { return (int) $row['currency'] === $currency; }));
            if (count($rows) > 1) throw new RuntimeException('Duplicate prices found for this currency. Resolve them in WHMCS.');
            if ($rows) {
                $changes = [];
                foreach ($amounts as $column => $amount) if ($amount !== number_format((float) $rows[0][$column], 2, '.', '')) $changes[$column] = $amount;
                if ($changes) {
                    $set = implode(', ', array_map(function ($column) { return '`' . $column . '` = ?'; }, array_keys($changes)));
                    $pdo->prepare('UPDATE tblpricing SET ' . $set . ' WHERE id = ?')->execute(array_merge(array_values($changes), [$rows[0]['id']]));
                }
            } else {
                $columns = implode(', ', array_map(function ($column) { return '`' . $column . '`'; }, array_keys($amounts)));
                $placeholders = implode(', ', array_fill(0, count($amounts), '?'));
                $pdo->prepare("INSERT INTO tblpricing (type, currency, relid, " . $columns . ") VALUES ('product', ?, ?, " . $placeholders . ')')->execute(array_merge([$currency, $pid], array_values($amounts)));
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function render($plans, $pdo, $selected_pid = null) {
        $selection = $selected_pid ?? ($_GET['pricing_plan'] ?? ($plans[0]['id'] ?? 0));
        $pid = is_scalar($selection) ? absint($selection) : 0;
        $state = null;
        $error = '';
        try { if ($pid) $state = self::state($pdo, $pid); }
        catch (Throwable $exception) { $error = $exception instanceof PDOException ? 'Base prices could not be loaded. Check the WHMCS connection.' : $exception->getMessage(); }
        ?>
        <section id="base-plan-prices" class="rm-panel rm-resource-panel" data-admin-section="prices" data-admin-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-admin-nonce="<?php echo esc_attr(wp_create_nonce('rackmonk_admin_section')); ?>">
            <div class="rm-section-heading"><div><p class="rm-eyebrow">INDEPENDENT CURRENCY PRICING</p><h2>INR / USD base-plan prices</h2><p>Existing INR rates stay in INR. Set USD separately; saving one currency never converts or overwrites another.</p></div></div>
            <div class="rm-native-load-status" data-admin-status role="status" aria-live="polite" hidden><span data-admin-message></span><button type="button" class="rm-button rm-button-secondary" data-admin-retry hidden>Retry</button></div>
            <div class="rm-resource-picker"><label for="rm-pricing-plan">Choose a plan</label><select id="rm-pricing-plan" name="pricing_plan" data-admin-select><?php foreach ($plans as $plan): ?><option value="<?php echo esc_attr($plan['id']); ?>" <?php selected($pid, $plan['id']); ?>><?php echo esc_html($plan['name']); ?></option><?php endforeach; ?></select></div>
            <?php if ($error): ?><div class="rm-alert" role="alert"><?php echo esc_html($error); ?></div><?php endif; ?>
            <?php if (!$state): ?><div class="rm-resource-explanation">Connect WHMCS and select a real plan to edit its currency prices.</div><?php else: ?>
            <div class="rm-resource-explanation"><strong><?php echo esc_html($state['product']['name']); ?></strong> &middot; Values below are native term totals, not per-month averages. Triennial billing is used for the 48 Months price. Use <code>-1.00</code> to disable a billing value.</div>
            <?php foreach ($state['currencies'] as $currency):
                $rows = array_values(array_filter($state['prices'], function ($row) use ($currency) { return (int) $row['currency'] === (int) $currency['id']; }));
                $row = $rows[0] ?? null;
            ?>
            <form method="post" class="rm-base-price-editor" data-price-currency="<?php echo esc_attr($currency['code']); ?>">
                <?php wp_nonce_field('rackmonk_plan_currency_' . $pid . '_' . $currency['id']); ?>
                <input type="hidden" name="rackmonk_action" value="save_plan_currency"><input type="hidden" name="pricing_plan" value="<?php echo esc_attr($pid); ?>"><input type="hidden" name="pricing_currency" value="<?php echo esc_attr($currency['id']); ?>"><input type="hidden" name="pricing_version" value="<?php echo esc_attr(self::version($state)); ?>">
                <div class="rm-native-currency"><h3><?php echo esc_html($currency['code'] . ' (' . $currency['prefix'] . ')'); ?></h3><p class="rm-native-muted"><?php echo $row ? 'Existing amounts loaded from this WHMCS currency record.' : 'No price record yet. Enter this currency explicitly; no INR value has been copied. Other recurring terms start disabled.'; ?></p>
                <?php if (count($rows) > 1): ?><div class="rm-alert" role="alert">Duplicate currency prices found. Resolve them in WHMCS before saving.</div><?php endif; ?>
                <div class="rm-table-scroll" role="region" aria-label="<?php echo esc_attr($currency['code'] . ' base plan prices'); ?>" tabindex="0"><table class="rm-native-prices"><thead><tr><th>Price type</th><?php foreach (Rackmonk_Configurable_Groups::CYCLES as $cycle): ?><th><?php echo esc_html($cycle['label']); ?></th><?php endforeach; ?></tr></thead><tbody>
                <?php foreach (['setup' => 'Setup fee', 'recurring' => 'Plan total'] as $kind => $label): ?><tr><th><?php echo esc_html($label); ?></th><?php foreach (Rackmonk_Configurable_Groups::CYCLES as $column => $cycle): $field = $kind === 'setup' ? $cycle['setup'] : $column; $value = $row ? number_format((float) $row[$field], 2, '.', '') : ($kind === 'setup' ? '0.00' : ($column === 'monthly' ? '' : '-1.00')); ?><td><input type="number" min="-1" max="99999999.99" step="0.01" required name="plan_prices[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo $column === 'monthly' && !$row ? 'Enter price' : ''; ?>" aria-label="<?php echo esc_attr($currency['code'] . ' ' . $cycle['label'] . ' ' . $label); ?>"></td><?php endforeach; ?></tr><?php endforeach; ?>
                </tbody></table></div></div>
                <input type="hidden" name="price_complete" value="1"><div class="rm-form-footer"><span><?php echo esc_html($currency['code']); ?> only. No group, resource or other currency price is changed.</span><button class="rm-button rm-button-primary" type="submit" <?php disabled(count($rows) > 1); ?>>Save <?php echo esc_html($currency['code']); ?> prices</button></div>
            </form>
            <?php endforeach; endif; ?>
        </section>
        <?php
    }
}
