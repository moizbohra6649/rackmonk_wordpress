<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="banner__inner-wrap">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="banner__content">
                <?php if(!empty($settings['title'])):?>
                    <h2 class="title">
                        <?php echo xhosting_wp_kses($settings['title']); ?>
                    </h2>
                <?php endif; ?>
                <?php if(!empty($settings['description'])):?>
                    <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
                <?php endif; ?>
                <?php $price_source = $settings['rackmonk_price_source'] ?? 'auto';
                $banner_text = static function ($key, $default) { return esc_html(class_exists('Rackmonk_Site_Content') ? \Rackmonk_Site_Content::text($key, $default) : $default); };
                if ($price_source === 'catalog' || ($price_source === 'auto' && is_front_page() && $this->get_id() === '1e35ce6')) :
                    $rate = class_exists('Rackmonk_Plans_Manager') && class_exists('Rackmonk_Website_Cart') ? \Rackmonk_Plans_Manager::get_instance()->get_lowest_inr_cart_rate() : null;
                    if ($rate) : ?>
                    <div class="banner__price">
                        <span><?php echo $banner_text('banner_start_label', 'Start with'); ?> <strong>&#8377;<?php echo esc_html(number_format(ceil($rate['cents'] / $rate['months']) / 100, 2)); ?></strong>/mo</span>
                        <?php if ($rate['monthly'] !== null && $rate['monthly'] * $rate['months'] > $rate['cents']) : ?>
                            <p><?php echo $banner_text('banner_monthly_label', 'Monthly billing'); ?> &#8377;<?php echo esc_html(number_format($rate['monthly'] / 100, 2)); ?>/mo</p>
                        <?php endif; ?>
                        <p>For <?php echo esc_html($rate['months']); ?> <?php echo $banner_text('banner_term_label', 'Months Term'); ?><?php if ($rate['setup'] > 0) : ?>; setup &#8377;<?php echo esc_html(number_format($rate['setup'] / 100, 2)); ?><?php endif; ?>. <?php echo $banner_text('banner_tax_note', 'Taxes extra.'); ?></p>
                    </div>
                    <?php else : ?>
                    <div class="banner__price"><span><?php echo $banner_text('banner_unavailable', '48-month pricing will be available once configured.'); ?></span></div>
                    <?php endif; ?>
                <?php elseif(!empty($settings['regular_price']) || !empty($settings['discount_price'])):?>
                    <div class="banner__price">
                        <span><?php echo xhosting_wp_kses($settings['regular_price']); ?></span>
                        <p><?php echo xhosting_wp_kses($settings['discount_price']); ?></p>
                    </div>
                <?php endif; ?>
                <?php if(!empty($settings['btn_label'])):?>
                <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>  class="tg-btn tg-black-btn">
                    <?php if(!empty($settings['btn_icon'])):?>
                        <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                    <?php endif;?>
                    <?php echo xhosting_wp_kses($settings['btn_label']); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 col-md-8">
            <div class="banner__img-wrap">
                <?php if(!empty($settings['image']['url'])):?>
                <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>" class="main-img">
                <?php endif;?>
                <?php if(!empty($settings['image2']['url'])):?>
                    <img src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>" data-aos="fade-down" data-aos-delay="200">
                <?php endif;?>
                <?php if(!empty($settings['image3']['url'])):?>
                    <img src="<?php echo esc_url($settings['image3']['url']); ?>" alt="<?php if(!empty($settings['image3']['alt'])){ echo esc_attr($settings['image3']['alt']);}?>" data-aos="fade-right" data-aos-delay="200">
                <?php endif;?>
                <?php if(!empty($settings['image4']['url'])):?>
                    <img src="<?php echo esc_url($settings['image4']['url']); ?>" alt="<?php if(!empty($settings['image4']['alt'])){ echo esc_attr($settings['image4']['alt']);}?>" data-aos="fade-up" data-aos-delay="200">
                <?php endif;?>
            </div>
        </div>
    </div>
</div>
