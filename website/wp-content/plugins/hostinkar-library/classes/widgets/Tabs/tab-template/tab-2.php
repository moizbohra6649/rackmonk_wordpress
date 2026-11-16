<div class="tab-content" id="myTabContent">
    <?php
        foreach( $settings['tabs'] as $id => $item ) :
        $active = ($id == 0) ? 'show active' : '';
    ?>
    <div class="tab-pane fade <?php echo esc_attr($active); ?> elementor-repeater-item-<?php echo esc_attr($item['_id']); ?>" id="itemOne-tab-pane<?php echo esc_attr($id); ?>" role="tabpanel" aria-labelledby="itemOne-tab<?php echo esc_attr($id); ?>" tabindex="0">
        <div class="success__item-four">
            <?php if(!empty($item['shape']['url'])):?>
            <div class="shape">
                <img src="<?php echo esc_url($item['shape']['url']);?>" alt="<?php if(!empty($item['shape']['alt'])){ echo esc_attr($item['shape']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
            </div>
            <?php endif;?>
            <?php if(!empty($item['content_img']['url'])):?>
                <div class="success__img-four">
                    <img src="<?php echo esc_url($item['content_img']['url']);?>" alt="<?php if(!empty($item['content_img']['alt'])){ echo esc_attr($item['content_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                </div>
            <?php endif;?>
            <div class="success__content-four">
                <?php if(!empty($item['title'])):?>
                <h2 class="title"><?php echo xhosting_wp_kses($item['title'])?></h2>
                <?php endif;?>
                <?php if(!empty($item['description'])):?>
                    <p><?php echo xhosting_wp_kses($item['description'])?></p>
                <?php endif;?>
                <?php if(!empty($item['lists'])):?>
                <ul class="list-wrap features__list success__list-two">
                    <?php foreach($item['lists'] as $list):?>
                    <li>
                        <?php \Elementor\Icons_Manager::render_icon( $list['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <p><?php echo xhosting_wp_kses($list['title'])?></p>
                    </li>
                    <?php endforeach;?>
                </ul>
                <?php endif;?>
                <?php if(!empty($item['btn_label'])):?>
                    <a target="<?php echo esc_attr( $item['btn_link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['btn_link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['btn_link']['url']);?>" class="tg-btn tg-btn-five"><?php echo xhosting_wp_kses($item['btn_label'])?>
                        <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.16797 10.5H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12.5 13.8333L15.8333 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12.5 7.16699L15.8333 10.5003" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif;?>
            </div>
        </div>
    </div>
    <?php endforeach;?>
</div>
<div class="success__tab-wrap">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <?php
            foreach( $settings['tabs'] as $id => $item ) :

            $active = ($id == 0) ? 'active' : '';
            $aria_selected = ($id == 0) ? 'true' : 'false';

        ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo esc_attr($active); ?>" id="itemOne-tab<?php echo esc_attr($id); ?>" data-bs-toggle="tab" data-bs-target="#itemOne-tab-pane<?php echo esc_attr($id); ?>" type="button" role="tab" aria-controls="itemOne-tab-pane<?php echo esc_attr($id); ?>" aria-selected="true">
            <strong class="title"><?php echo xhosting_wp_kses($item['tb_title']);?></strong>
            <?php if(!empty($item['tb_desc'])):?>
                <span><?php echo xhosting_wp_kses($item['tb_desc']);?></span>
            <?php endif;?>
            </button>
        </li>
        <?php endforeach;?>
    </ul>
</div>