<div class="hosting__item-wrap">
    <div class="swiper-container hosting-active fix">
        <div class="swiper-wrapper">
            <?php 
                $number_words = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
                $counter = 0;  
                foreach($settings['services'] as $item):  
                    $word = isset($number_words[$counter]) ? $number_words[$counter] : 'default';
            ?>
            <div class="swiper-slide elementor-repeater-item-<?php echo esc_attr($item['_id']); ?>">
                <div class="hosting__item item-<?php echo esc_attr($word); ?>">
                    <div class="hosting__icon">
                        <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                            <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($item['icon_img']['alt'])){ echo esc_attr($item['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                        <?php else:?>
                            <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <?php endif;?>
                    </div>
                    <div class="hosting__content">
                        <?php if(!empty($item['title'])):?>
                            <h3 class="title"><a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php echo xhosting_wp_kses($item['title']);?></a></h3>
                        <?php endif;?>
                        <?php if(!empty($item['excerpt'])):?>
                        <p><?php echo xhosting_wp_kses($item['excerpt']);?></p>
                        <?php endif;?>
                        <?php if(!empty($item['btn_label'])):?>
                        <div class="hosting__btn">
                            <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>" class="tg-btn tg-btn-three">
                                <?php echo xhosting_wp_kses($item['btn_label']);?>
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12.5 13.3333L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12.5 6.66663L15.8333 9.99996" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                        <?php if($item['is_populer'] === 'yes'):?>
                            <span class="hosting__tag"><?php echo xhosting_wp_kses($item['populer_badge']);?></span>
                        <?php endif;?>
                        <?php endif;?>
                    </div>
                </div>
            </div>
            <?php $counter++; endforeach;?>
        </div>
    </div>
</div>