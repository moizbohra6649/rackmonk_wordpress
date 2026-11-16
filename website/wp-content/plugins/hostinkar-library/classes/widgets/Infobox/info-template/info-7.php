 <div class="marquee__area-two">
    <div class="swiper-container tg-marquee-active">
        <div class="swiper-wrapper slide-transition">
            <?php foreach($settings['infos'] as $item):?>
                <div class="swiper-slide elementor-repeater-item-<?php echo esc_attr($item['_id']); ?>">
                    <div class="marquee__item-two">
                        <span><?php echo xhosting_wp_kses($item['title']); ?></span>
                    </div>
                </div>
            <?php endforeach;?>

        </div>
    </div>
</div>