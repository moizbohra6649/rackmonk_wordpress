<div class="counter__wrap-two">
    <?php foreach($settings['counters'] as $item):?>
        <div class="counter__item-two">
            <div class="icon">
                <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                    <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($item['icon_img']['alt'])){ echo esc_attr($item['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                <?php else:?>
                    <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                <?php endif;?>
            </div>
            <div class="content">
                <h2 class="count"><span class="counter-number"><?php echo xhosting_wp_kses($item['count']);?></span><?php if($item['prefix']): ?><?php echo esc_attr($item['prefix']);?><?php endif; ?></h2>
                <p><?php echo xhosting_wp_kses($item['title']);?></p>
            </div>
        </div>
    <?php endforeach;?>
</div>