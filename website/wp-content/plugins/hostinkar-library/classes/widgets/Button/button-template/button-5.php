<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="faq__bottom-content">
    <p>
        <?php if(!empty($settings['btn_info'])){
            echo xhosting_wp_kses($settings['btn_info']);
        }?>
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
    </p>
</div>