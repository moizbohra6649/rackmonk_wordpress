<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="footer__cart-wrap">
    <div class="row">
        <div class="col-md-8">
            <div class="footer__cart-content">
                <?php if(!empty($settings['title'])):?>
                    <span class="title"><?php echo xhosting_wp_kses($settings['title']); ?></span>
                <?php endif;?>
                <?php if(!empty($settings['payment']['url'])):?>
                    <img src="<?php echo esc_url($settings['payment']['url']);?>" alt="<?php if(!empty($settings['payment']['alt'])){ echo esc_attr($settings['payment']['alt']);}?>">
                <?php endif;?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="footer__cart-content-right">
                <?php if(!empty($settings['btn_label'])):?>
                    <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>>
                        <?php if(!empty($settings['btn_icon'])):?>
                            <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <?php endif;?>
                        <?php echo xhosting_wp_kses($settings['btn_label']); ?>
                    </a>
                <?php endif;?>
                <?php if(!empty($settings['languages'])):?>
                <div class="language__select">
                    <select name="orderby">
                        <?php foreach($settings['languages'] as $item):?>
                            <option value="<?php echo xhosting_wp_kses($item['title']); ?>"><?php echo xhosting_wp_kses($item['title']); ?></option>
                        <?php endforeach;?>
                    </select>
                </div>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>