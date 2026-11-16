<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="footer__hosting-wrap">
    <?php if($settings['title_shape'] === 'yes'):?>
        <div class="icon">
            <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
        </div>
    <?php endif;?>
    <div class="content">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h2>
        <?php endif;?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
        <?php endif;?>
        <?php if(!empty($settings['btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-btn-five"><?php echo xhosting_wp_kses($settings['btn_label']); ?>
                <svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.66797 10H16.3346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M13 13.3333L16.3333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M13 6.66699L16.3333 10.0003" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        <?php endif;?>
    </div>
</div>