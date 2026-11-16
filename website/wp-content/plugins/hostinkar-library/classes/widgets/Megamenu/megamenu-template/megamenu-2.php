<div class="d-flex flex-wrap-mobile">
    <div class="tg-mega-menu-left-side">
        <?php if(!empty($settings['title'])):?>
            <h5 class="mega-menu-title mega-menu-title-two"><?php echo xhosting_wp_kses($settings['title']);?></h5>
        <?php endif;?>
        <div class="row gutter-y-24">
            <?php foreach($settings['megamenus'] as $item):?>
                <div class="col-lg-6">
                    <div class="mega-menu-item-two">
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
                </div>
            <?php endforeach;?>
        </div>
    </div>
    <div class="tg-mega-menu-right-side">
        <img src="<?php echo esc_url($settings['banner_img']['url']);?>" alt="<?php if(!empty($settings['banner_img']['alt'])){ echo esc_attr($settings['banner_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php if(!empty($settings['discount'])):?>
            <h2 class="title"><?php echo xhosting_wp_kses($settings['discount']);?></h2>
        <?php endif;?>
    </div>
</div>