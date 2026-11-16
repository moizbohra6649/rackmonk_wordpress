
<?php if(!empty($settings['title'])):?>
    <div class="brand__title">
    <?php echo xhosting_wp_kses($settings['title']); ?>
    </div>
<?php endif; ?>
<div class="brand__item-wrap">
    <div class="swiper-container brand-active fix">
        <div class="swiper-wrapper">
            <?php foreach($settings['brands'] as $item):?>
                <div class="swiper-slide">
                    <div class="brand__item">
                        <img src="<?php echo esc_url($item['brand_img']['url']);?>" alt="<?php if(!empty($item['brand_img']['alt'])){ echo esc_attr($item['brand_img']['alt']);}?>">
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    </div>
</div>