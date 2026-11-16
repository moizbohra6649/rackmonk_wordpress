<div class="services__inner-wrap">
    <div class="services__tab-wrap">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <?php
                foreach( $settings['tabs'] as $id => $item ) :

                $active = ($id == 0) ? 'active' : '';
                $aria_selected = ($id == 0) ? 'true' : 'false';

            ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo esc_attr($active); ?>" id="one-tab<?php echo esc_attr($id); ?>" data-bs-toggle="tab" data-bs-target="#one-tab-pane<?php echo esc_attr($id); ?>" type="button" role="tab" aria-controls="one-tab-pane<?php echo esc_attr($id); ?>" aria-selected="true"><?php echo xhosting_wp_kses($item['title']);?></button>
                </li>
            <?php endforeach;?>
        </ul>
    </div>
    <div class="services__item-wrap">
        <div class="tab-content" id="myTabContent">
            <?php
                foreach( $settings['tabs'] as $id => $item ) :
                $active = ($id == 0) ? 'show active' : '';
            ?>
            <div class="tab-pane <?php echo esc_attr($active); ?>" id="one-tab-pane<?php echo esc_attr($id); ?>" role="tabpanel" aria-labelledby="one-tab<?php echo esc_attr($id); ?>" tabindex="0">
                <div class="services__item">
                    <div class="services__content">
                        <h2 class="title"><?php echo xhosting_wp_kses($item['description'])?></h2>
                        <?php if(!empty($item['btn_label'])):?>
                        <a target="<?php echo esc_attr( $item['btn_link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['btn_link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['btn_link']['url']);?>" class="tg-btn tg-btn-four"><?php echo xhosting_wp_kses($item['btn_label'])?>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12.5 13.3333L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12.5 6.66675L15.8333 10.0001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <?php endif;?>
                        <?php if(!empty($item['shape']['url'])):?>
                            <div class="services__shape">
                                <img src="<?php echo esc_url($item['shape']['url']);?>" alt="<?php if(!empty($item['shape']['alt'])){ echo esc_attr($item['shape']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                            </div>
                        <?php endif;?>
                    </div>
                    <?php if(!empty($item['content_img']['url'])):?>
                        <div class="services__thumb">
                            <img src="<?php echo esc_url($item['content_img']['url']);?>" alt="<?php if(!empty($item['content_img']['alt'])){ echo esc_attr($item['content_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                        </div>
                    <?php endif;?>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </div>
</div>