<div class="features__item-fourteen">
    <div class="features__icon-four">
        <?php if ($settings['type'] === 'image' && ($settings['icon_img']['url'])) :?>
            <img src="<?php echo esc_url($settings['icon_img']['url']);?>" alt="<?php if(!empty($settings['icon_img']['alt'])){ echo esc_attr($settings['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php else:?>
            <?php \Elementor\Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php endif;?>
    </div>
    <div class="features__content-ten">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h2>
        <?php endif;?>
        <?php if(!empty($settings['infos'])):?>
            <ul class="list-wrap features__list-two">
                <?php foreach($settings['infos'] as $item):?>
                    <li><?php echo xhosting_wp_kses($item['title']); ?></li>
                <?php endforeach;?>
            </ul>
        <?php endif;?>
    </div>
    <?php if(!empty($settings['subtitle'])):?>
        <h1 class="text-overlay"><?php echo xhosting_wp_kses($settings['subtitle']); ?></h1>
    <?php endif;?>
    <?php if(!empty($settings['shape']['url'])):?>
        <div class="shape">
            <img src="<?php echo esc_url($settings['shape']['url']);?>" alt="<?php if(!empty($settings['shape']['alt'])){ echo esc_attr($settings['shape']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        </div>
    <?php endif;?>
</div>