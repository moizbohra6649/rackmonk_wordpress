<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="view-more-btn text-center mt-20">
    <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-border-btn-three"><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
</div>