<?php 
    if ( ! empty( $settings['link']['url'] ) ) {
        $this->add_link_attributes( 'link', $settings['link'] );
    }
?> 
<div class="hosting__item-four hosting__item-five">
    <div class="hosting__icon-five">
         <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
            <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php else:?>
            <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php endif;?>
    </div>
    <div class="hosting__content-four">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title"><?php echo xhosting_wp_kses($settings['title']);?></h2>
        <?php endif;?>
        <?php if(!empty($settings['excerpt'])):?>
            <p><?php echo xhosting_wp_kses($settings['excerpt']);?></p>
        <?php endif;?>
        <a <?php echo $this->get_render_attribute_string( 'link' ); ?>><?php echo xhosting_wp_kses($settings['btn_label']);?>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12.5 13.3333L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12.5 6.66675L15.8333 10.0001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>
    </div>
</div>