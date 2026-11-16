<?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<div class="support__inner-wrap-four support__bg jarallax" data-background="<?php echo esc_url($settings['bg_img']['url']);?>">
    <div class="support__content-four">
        <div class="support__icon">
            <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
                <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
            <?php else:?>
                <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
            <?php endif;?>
        </div>
        <?php if(!empty($settings['title'])):?>
            <h3 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h3>
        <?php endif;?>
        <?php if(!empty($settings['infos'])):?>
            <div class="support__list-wrap">
                <?php foreach($settings['infos'] as $item):?>
                    <div class="support__list-item support__list-item-four">
                        <div class="icon">
                            <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                                <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                            <?php else:?>
                                <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <?php endif;?>
                        </div>
                        <div class="content">
                            <?php if(!empty($item['title'])):?>
                                <h5 class="title"><?php echo xhosting_wp_kses($item['title']); ?></h5>
                            <?php endif;?>
                            <?php if(!empty($item['excerpt'])):?>
                                <p><?php echo xhosting_wp_kses($item['excerpt']); ?></p>
                            <?php endif;?>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        <?php endif;?>
        <?php if(!empty($settings['btn_label'])):?>
            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-btn-five"><?php echo xhosting_wp_kses($settings['btn_label']); ?>
                <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.16797 10.5H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12.5 13.8333L15.8333 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12.5 7.1665L15.8333 10.4998" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        <?php endif;?>
        <?php if(!empty($settings['shape']['url'])):?>
            <div class="shape">
                <img src="<?php echo esc_url($settings['shape']['url']);?>" alt="<?php if(!empty($settings['shape']['alt'])){ echo esc_attr($settings['shape']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
            </div>
        <?php endif;?>
    </div>
</div>