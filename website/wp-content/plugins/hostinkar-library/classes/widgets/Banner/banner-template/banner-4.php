<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="features__item-two">
    <div class="features__content-two">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title">
                <?php echo xhosting_wp_kses($settings['title']); ?>
            </h2>
        <?php endif; ?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
        <?php endif; ?>
         <?php if(!empty($settings['btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-link-btn">
                <span class="link-effect">
                    <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                    <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                </span>
                <?php if(!empty($settings['btn_icon'])):?>
                    <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                <?php endif;?>                
            </a>
        <?php endif; ?>
    </div>
    <div class="features__thumb-two">
        <?php if(!empty($settings['image']['url'])):?>
            <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>" data-aos="fade-left" data-aos-delay="200">
        <?php endif;?>
    </div>
    <?php if(!empty($settings['image2']['url'])):?>
        <div class="features__shape">
            <img src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>">
        </div>
    <?php endif;?>
</div>