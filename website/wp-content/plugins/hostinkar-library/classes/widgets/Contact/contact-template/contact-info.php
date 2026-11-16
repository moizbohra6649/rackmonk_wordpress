<div class="contact__info-box">
    <?php if(!empty($settings['title'])):?>
        <h3 class="title"><?php echo xhosting_wp_kses($settings['title']);?></h3>
    <?php endif;?>
    <?php if(!empty($settings['description'])):?>
        <p><?php echo xhosting_wp_kses($settings['description']);?></p>
    <?php endif;?>
    <?php if(!empty($settings['contacts'])):?>
        <ul class="list-wrap">
            <?php foreach($settings['contacts'] as $item):?>
                <li>
                    <div class="icon">
                        <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                            <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($item['icon_img']['alt'])){ echo esc_attr($item['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                        <?php else:?>
                            <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <?php endif;?>
                    </div>
                    <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php echo xhosting_wp_kses($item['title']);?></a>
                </li>
            <?php endforeach;?>
        </ul>
    <?php endif;?>
    <div class="header-dropdown-social contact__social">
        <?php if(!empty($settings['s_title'])):?>
            <span class="title"><?php echo xhosting_wp_kses($settings['s_title']);?></span>
        <?php endif;?>
        <?php if(!empty($settings['socials'])):?>
        <ul class="list-wrap">
            <?php foreach($settings['socials'] as $item):?>
                <li>
                    <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?></a>
                </li>
            <?php endforeach;?>

        </ul>
        <?php endif;?>
    </div>
</div>