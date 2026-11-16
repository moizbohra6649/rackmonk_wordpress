<div class="contact__item">
    <div class="contact__item-icon">
        <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
            <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php else:?>
            <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php endif;?>
    </div>
    <div class="contact__item-content">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title"><?php echo xhosting_wp_kses($settings['title']);?></h2>
        <?php endif;?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']);?></p>
        <?php endif;?>
        <?php if(!empty($settings['infos'])):?>
        <ul class="list-wrap">
            <?php foreach($settings['infos'] as $item):?>
                <li>
                    <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>">
                        <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <?php echo xhosting_wp_kses($item['title']);?>
                    </a>
                </li>
            <?php endforeach;?>
        </ul>
        <?php endif;?>
    </div>
</div>