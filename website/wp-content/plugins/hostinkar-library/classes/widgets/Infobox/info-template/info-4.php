<div class="support__list-item">
    <div class="icon">
        <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
            <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php else:?>
            <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php endif;?>
    </div>
    <div class="content">
        <?php if(!empty($settings['title'])):?>
            <h5 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h5>
        <?php endif;?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
        <?php endif;?>
    </div>
</div>