 <?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="btnalign">
    <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-btn-two"><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
</div>