<?php 
    if ( ! empty( $settings['link']['url'] ) ) {
        $this->add_link_attributes( 'link', $settings['link'] );
    }
?>
<div class="hosting__item-two">
    <div class="hosting__item-top">
        <div class="hosting__icon hosting__icon-two">
            <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
                <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
            <?php else:?>
                <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
            <?php endif;?>
        </div>
        <div class="hosting__content-top">
            <h4 class="title"><a <?php echo $this->get_render_attribute_string( 'link' ); ?>><?php echo xhosting_wp_kses($settings['title']);?></a></h4>
            <?php if(!empty($settings['sub_title'])):?>
                <span><?php echo xhosting_wp_kses($settings['sub_title']);?></span>
            <?php endif;?>
        </div>
    </div>
    <?php if(!empty($settings['excerpt'])):?>
        <div class="hosting__content-two">
            <p><?php echo xhosting_wp_kses($settings['excerpt']);?></p>
        </div>
    <?php endif;?>
     <?php if(!empty($settings['btn_label'])):?>
    <div class="hosting__btn-two">
        <a <?php echo $this->get_render_attribute_string( 'link' ); ?>>
            <svg class="left-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.16797 7.5L11.668 10L9.16797 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M2.5 10C2.5 10.9849 2.69399 11.9602 3.0709 12.8701C3.44781 13.7801 4.00026 14.6069 4.6967 15.3033C5.39314 15.9997 6.21993 16.5522 7.12987 16.9291C8.03982 17.306 9.01509 17.5 10 17.5C10.9849 17.5 11.9602 17.306 12.8701 16.9291C13.7801 16.5522 14.6069 15.9997 15.3033 15.3033C15.9997 14.6069 16.5522 13.7801 16.9291 12.8701C17.306 11.9602 17.5 10.9849 17.5 10C17.5 8.01088 16.7098 6.10322 15.3033 4.6967C13.8968 3.29018 11.9891 2.5 10 2.5C8.01088 2.5 6.10322 3.29018 4.6967 4.6967C3.29018 6.10322 2.5 8.01088 2.5 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="text"><?php echo xhosting_wp_kses($settings['btn_label']);?></span>
            <svg class="right-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.16797 7.5L11.668 10L9.16797 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M2.5 10C2.5 10.9849 2.69399 11.9602 3.0709 12.8701C3.44781 13.7801 4.00026 14.6069 4.6967 15.3033C5.39314 15.9997 6.21993 16.5522 7.12987 16.9291C8.03982 17.306 9.01509 17.5 10 17.5C10.9849 17.5 11.9602 17.306 12.8701 16.9291C13.7801 16.5522 14.6069 15.9997 15.3033 15.3033C15.9997 14.6069 16.5522 13.7801 16.9291 12.8701C17.306 11.9602 17.5 10.9849 17.5 10C17.5 8.01088 16.7098 6.10322 15.3033 4.6967C13.8968 3.29018 11.9891 2.5 10 2.5C8.01088 2.5 6.10322 3.29018 4.6967 4.6967C3.29018 6.10322 2.5 8.01088 2.5 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>
    </div>
    <?php endif;?>
    <?php if($settings['is_recommend'] === 'yes'):?>
        <span class="hosting__badge"><?php echo xhosting_wp_kses($settings['recommend_badge']);?></span>
    <?php endif;?>
    <?php if($settings['is_populer'] === 'yes'):?>
        <span class="hosting__badge hosting__badge-two"><?php echo xhosting_wp_kses($settings['populer_badge']);?></span>
    <?php endif;?>
</div>