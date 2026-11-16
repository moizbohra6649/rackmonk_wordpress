<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="domain__transfer-inner">
    <div class="row align-items-center justify-content-center">
        <div class="col-lg-6 col-md-8 order-0 order-lg-2">
            <div class="domain__transfer-img" data-aos="fade-left" data-aos-delay="200">
                <?php if(!empty($settings['image']['url'])):?>
                   <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>" >
                <?php endif;?>
                <?php if(!empty($settings['image2']['url'])):?>
                   <img src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>" >
                <?php endif;?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="domain__transfer-content">
                <?php if(!empty($settings['title'])):?>
                    <div class="section__title mb-15">
                        <h2 class="title">
                            <?php echo xhosting_wp_kses($settings['title']); ?>
                        </h2>
                    </div>
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
                        <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.75 5.75L5.25 13.25" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M6 5.75H12.75V12.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>