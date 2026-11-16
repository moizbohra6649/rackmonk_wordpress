    <?php if(!empty($settings['title'])):?>
        <h5 class="mega-menu-title"><?php echo xhosting_wp_kses($settings['title']);?></h5>
    <?php endif;?>
    <div class="row row-cols-1 row-cols-lg-4 row-cols-xl-4">
        <?php foreach($settings['megamenus'] as $item):?>
            <div class="col">
                <div class="mega-menu-item active">
                    <div class="mega-menu-thumb">
                        <a target="<?php echo esc_attr( $item['website_link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['website_link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['website_link']['url']);?>"><img src="<?php echo esc_url($item['image']['url']);?>" alt="<?php if(!empty($item['image']['alt'])){ echo esc_attr($item['image']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>"></a>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </div>