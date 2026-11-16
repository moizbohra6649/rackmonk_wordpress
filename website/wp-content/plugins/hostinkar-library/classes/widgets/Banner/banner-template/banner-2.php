<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="banner__poster-wrap">
    <?php if(!empty($settings['image']['url'])):?>
    <div class="banner__poster-thumb">
        <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>">
    </div>
   <?php endif;?>
    <div class="banner__poster-content">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title">
                <?php echo xhosting_wp_kses($settings['title']); ?>
            </h2>
        <?php endif; ?>
        <?php if(!empty($settings['btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-white-btn "><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
        <?php endif;?>
    </div>
</div>