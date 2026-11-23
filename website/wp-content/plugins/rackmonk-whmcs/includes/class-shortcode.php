<?php
/**
 * Rackmonk Pricing Shortcode
 * Dynamic WHMCS pricing tables with cycle duration selector and category tabs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_Pricing_Shortcode {

    public function __construct() {
        add_shortcode('rackmonk_plans', [$this, 'render_shortcode']);
        add_shortcode('rackmonk_pricing', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'gid'          => '',
            'category'     => '',
            'default_term' => '48',
            'show_tabs'    => 'yes',
            'show_cycles'  => 'yes',
        ], $atts, 'rackmonk_plans');

        $manager = Rackmonk_Plans_Manager::get_instance();
        $categories = $manager->get_categories();

        $gid = !empty($atts['gid']) ? intval($atts['gid']) : null;
        if (!$gid && !empty($atts['category'])) {
            $catSlug = strtolower(trim($atts['category']));
            foreach ($categories as $c) {
                if (stripos(strtolower($c['name']), $catSlug) !== false || stripos(strtolower($c['slug'] ?? ''), $catSlug) !== false) {
                    $gid = intval($c['id']);
                    break;
                }
            }
        }

        $plans = $manager->get_plans($gid);
        $copy = static function ($key, $default) {
            return esc_html(class_exists('Rackmonk_Site_Content') ? Rackmonk_Site_Content::text($key, $default) : $default);
        };

        ob_start();
        ?>
        <div class="rackmonk-pricing-container" data-default-term="<?php echo esc_attr($atts['default_term']); ?>" data-unavailable-label="<?php echo esc_attr(class_exists('Rackmonk_Site_Content') ? Rackmonk_Site_Content::text('pricing_unavailable', 'Unavailable') : 'Unavailable'); ?>">
            
            <?php if ($atts['show_tabs'] === 'yes' && empty($gid)): ?>
            <!-- Category Tabs -->
            <div class="rackmonk-category-tabs">
                <button type="button" class="rackmonk-cat-btn active" data-gid="all"><?php echo $copy('pricing_all_plans', 'All Plans'); ?></button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="rackmonk-cat-btn" data-gid="<?php echo esc_attr($cat['id']); ?>">
                        <?php echo esc_html($cat['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_cycles'] === 'yes'): ?>
            <!-- Billing Cycle Duration Selector (48 Months Default) -->
            <div class="rackmonk-cycle-tabs-wrap">
                <div class="rackmonk-cycle-tabs">
                    <button type="button" class="rackmonk-cycle-btn <?php echo $atts['default_term'] === '48' ? 'active' : ''; ?>" data-cycle="48">
                        <span class="cycle-title"><?php echo $copy('pricing_48_title', '48 Months'); ?></span>
                        <span class="cycle-badge"><?php echo $copy('pricing_48_badge', ''); ?></span>
                    </button>
                    <button type="button" class="rackmonk-cycle-btn <?php echo $atts['default_term'] === '24' ? 'active' : ''; ?>" data-cycle="24">
                        <span class="cycle-title"><?php echo $copy('pricing_24_title', '24 Months'); ?></span>
                        <span class="cycle-badge"><?php echo $copy('pricing_24_badge', ''); ?></span>
                    </button>
                    <button type="button" class="rackmonk-cycle-btn <?php echo $atts['default_term'] === '12' ? 'active' : ''; ?>" data-cycle="12">
                        <span class="cycle-title"><?php echo $copy('pricing_12_title', '12 Months'); ?></span>
                        <span class="cycle-badge"><?php echo $copy('pricing_12_badge', ''); ?></span>
                    </button>
                    <button type="button" class="rackmonk-cycle-btn <?php echo $atts['default_term'] === '1' ? 'active' : ''; ?>" data-cycle="1">
                        <span class="cycle-title"><?php echo $copy('pricing_1_title', '1 Month'); ?></span>
                    </button>
                </div>
                <div class="rackmonk-cycle-hint">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-right:4px; color:#16a34a;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    <strong><?php echo $copy('pricing_hint_title', 'Compare billing terms:'); ?></strong> <?php echo $copy('pricing_hint', 'Review the selected term, total price and included features before ordering.'); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pricing Cards Grid -->
            <div class="rackmonk-pricing-grid">
                <?php if (empty($plans)): ?>
                    <p style="text-align:center; grid-column: 1/-1; padding: 40px; color: #64748b;"><?php echo $copy('pricing_empty', 'No active hosting plans found in this category.'); ?></p>
                <?php else: foreach ($plans as $plan): 
                    $activeTerm = in_array($atts['default_term'], ['48', '24', '12', '1']) ? $atts['default_term'] : '48';
                    $currCycle = $plan['cycles'][$activeTerm] ?? $plan['cycles']['48'];
                ?>
                <div class="rackmonk-plan-card <?php echo $plan['is_popular'] ? 'is-popular' : ''; ?>" data-gid="<?php echo esc_attr($plan['gid']); ?>" data-plan-id="<?php echo esc_attr($plan['id']); ?>" data-cycles='<?php echo esc_attr(json_encode($plan['cycles'])); ?>'>
                    <?php if ($plan['is_popular']): ?>
                        <div class="rackmonk-popular-badge"><?php echo $copy('pricing_popular', 'MOST POPULAR'); ?></div>
                    <?php endif; ?>

                    <div class="rackmonk-plan-header">
                        <span class="rackmonk-plan-group"><?php echo esc_html($plan['group_name']); ?></span>
                        <h3 class="rackmonk-plan-name"><?php echo esc_html($plan['name']); ?></h3>
                        <p class="rackmonk-plan-tagline"><?php echo esc_html($plan['tagline']); ?></p>
                    </div>

                    <div class="rackmonk-plan-price-wrap">
                        <div class="rackmonk-price-regular">
                            <span class="currency"><?php echo esc_html($plan['currency']); ?></span><del class="reg-price-val"><?php echo esc_html($manager->money_number($currCycle['regular_price'], $plan)); ?></del>
                            <span class="discount-badge discount-val" style="<?php echo empty($currCycle['discount_text']) ? 'display:none;' : ''; ?>"><?php echo esc_html($currCycle['discount_text']); ?></span>
                        </div>
                        <div class="rackmonk-price-main">
                            <span class="currency"><?php echo esc_html($plan['currency']); ?></span>
                            <span class="price-val"><?php echo isset($currCycle['available']) && !$currCycle['available'] ? $copy('pricing_unavailable', 'Unavailable') : esc_html($manager->money_number($currCycle['price_per_month'], $plan)); ?></span>
                            <span class="period">/mo</span>
                        </div>
                        <div class="rackmonk-billing-note note-val">
                            <?php echo esc_html($currCycle['billing_note']); ?>
                        </div>
                    </div>

                    <div class="rackmonk-plan-cta">
                        <a <?php if (!empty($currCycle['order_url'])): ?>href="<?php echo esc_url($currCycle['order_url']); ?>"<?php else: ?>aria-disabled="true"<?php endif; ?> class="rackmonk-order-btn order-link-btn">
                            <span><?php echo $copy('pricing_choose', 'Choose Plan'); ?></span>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.16699 10H15.8337" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10.833 5L15.833 10L10.833 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </div>

                    <div class="rackmonk-plan-features">
                        <h4 class="features-title"><?php echo $copy('pricing_features', 'Top Features Included:'); ?></h4>
                        <ul class="features-list">
                            <?php foreach ($plan['features'] as $feat): ?>
                                <li>
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="10" cy="10" r="10" fill="#E8F5E9"/>
                                        <path d="M6 10L8.5 12.5L14 7" stroke="#2E7D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span><?php echo esc_html($feat); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>
                <?php endforeach; endif; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}

new Rackmonk_Pricing_Shortcode();
