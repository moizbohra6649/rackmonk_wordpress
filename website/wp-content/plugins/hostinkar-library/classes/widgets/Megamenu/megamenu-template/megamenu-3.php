<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="d-flex">
    <div class="tg-mega-menu-left-side-two">
        <?php if(!empty($settings['title'])):?>
            <h5 class="mega-menu-title mega-menu-title-two"><?php echo xhosting_wp_kses($settings['title']);?></h5>
        <?php endif;?>
        <div class="mega-menu-item-wrap">
            <?php foreach($settings['megamenus'] as $item):?>
                <div class="mega-menu-item-two mega-menu-item-three">
                    <a target="<?php echo esc_attr( $item['website_link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['website_link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['website_link']['url']);?>">
                        <div class="icon">
                            <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                                <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($item['icon_img']['alt'])){ echo esc_attr($item['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                            <?php else:?>
                                <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <?php endif;?>
                        </div>
                        <div class="content">
                            <?php if(!empty($item['title'])):?>
                                <strong class="title"><?php echo xhosting_wp_kses($item['title']);?></strong>
                            <?php endif;?>
                            <?php if(!empty($item['desc'])):?>
                                <span><?php echo xhosting_wp_kses($item['desc']);?></span>
                            <?php endif;?>
                        </div>
                    </a>
                </div>
            <?php endforeach;?>
        </div>
    </div>
    <?php if($settings['info_box_enable'] === 'yes'):?>
        <div class="tg-mega-menu-right-side-two">
            <?php if(!empty($settings['box_title'])):?>
                <h5 class="mega-menu-title mega-menu-title-two"><?php echo xhosting_wp_kses($settings['box_title']);?></h5>
            <?php endif;?>
            <div class="reliable__box">
                <?php if(!empty($settings['info_title'])):?>
                    <h2 class="title"><?php echo xhosting_wp_kses($settings['info_title']);?></h2>
                <?php endif;?>
                <?php if(!empty($settings['info_desc'])):?>
                    <p><?php echo xhosting_wp_kses($settings['info_desc']);?></p>
                <?php endif;?>
                <?php if(!empty($settings['btn_label'])):?>
                    <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-border-btn-four"><?php echo xhosting_wp_kses($settings['btn_label']); ?>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.16797 7.5L11.668 10L9.16797 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2.5 10C2.5 10.9849 2.69399 11.9602 3.0709 12.8701C3.44781 13.7801 4.00026 14.6069 4.6967 15.3033C5.39314 15.9997 6.21993 16.5522 7.12987 16.9291C8.03982 17.306 9.01509 17.5 10 17.5C10.9849 17.5 11.9602 17.306 12.8701 16.9291C13.7801 16.5522 14.6069 15.9997 15.3033 15.3033C15.9997 14.6069 16.5522 13.7801 16.9291 12.8701C17.306 11.9602 17.5 10.9849 17.5 10C17.5 8.01088 16.7098 6.10322 15.3033 4.6967C13.8968 3.29018 11.9891 2.5 10 2.5C8.01088 2.5 6.10322 3.29018 4.6967 4.6967C3.29018 6.10322 2.5 8.01088 2.5 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif;?>
                <?php if(!empty($settings['info_shape']['url'])):?>
                    <img class="shape" src="<?php echo esc_url($settings['info_shape']['url']);?>" alt="<?php if(!empty($settings['info_shape']['alt'])){ echo esc_attr($settings['info_shape']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                <?php endif;?>
            </div>
        </div>
    <?php endif;?>
    </div>