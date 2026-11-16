<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<section class="hosting__top-area hosting__top-bg-two">
    <div class="container">
        <div class="row align-items-end justify-content-center">
            <div class="col-lg-6">
                <div class="hosting__top-content hosting__top-content-five">
                    <?php if(!empty($settings['title'])):?>
                        <h2 class="title">
                            <?php echo xhosting_wp_kses($settings['title']); ?>
                        </h2>
                    <?php endif; ?>
                     <?php if(!empty($settings['description'])):?>
                        <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($settings['btn_label'])):?>
                        <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>  class="tg-btn tg-black-btn-two"><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 col-md-9">
                <div class="hosting__top-images hosting__top-images-five">
                    <?php if(!empty($settings['image']['url'])):?>
                        <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>" data-aos="fade-up" data-aos-delay="200">
                    <?php endif;?>
                </div>
            </div>
        </div>
    </div>
</section>