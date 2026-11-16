<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
    if ( ! empty( $settings['t_btn_link']['url'] ) ) {
        $this->add_link_attributes( 't_btn_link', $settings['t_btn_link'] );
    }
?>
<div class="domain__price-item">
    <?php if(!empty($settings['extension'])):?>
        <h3 class="name"><?php echo xhosting_wp_kses($settings['extension']);?></h3>
    <?php endif;?>
    <?php if(!empty($settings['title'])):?>
        <span class="intro-price"><?php echo xhosting_wp_kses($settings['title']);?></span>
    <?php endif;?>

    <?php if(!empty($settings['price'])):?>
        <h2 class="price"><?php echo xhosting_wp_kses($settings['price']);?> 
        <?php if(!empty($settings['discount'])):?>
            <span><?php echo xhosting_wp_kses($settings['discount']);?></span>
        <?php endif;?>
        </h2>
    <?php endif;?>
    <?php if(!empty($settings['btn_label']) || !empty($settings['t_btn_label'])):?>
    <div class="domain__price-btn">
        <?php if(!empty($settings['btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>  class="tg-btn tg-btn-two"><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
        <?php endif;?>
        <?php if(!empty($settings['t_btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 't_btn_link' ); ?>  class="transfer"><?php echo xhosting_wp_kses($settings['t_btn_label']); ?></a>
        <?php endif;?>

    </div>
    <?php endif;?>
</div>