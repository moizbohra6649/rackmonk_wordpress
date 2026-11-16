<div class="success__item-two">
    <div class="success__content-two">
        <?php if(!empty($settings['title'])):?>
            <h2 class="title">
                <?php echo xhosting_wp_kses($settings['title']); ?>
            </h2>
        <?php endif; ?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
        <?php endif; ?>
    </div>
    <?php if(!empty($settings['image']['url']) || !empty($settings['image2']['url'])):?>
        <div class="success__img-two">
            <?php if(!empty($settings['image']['url'])):?>
                <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>" data-aos="fade-up" data-aos-delay="200">
            <?php endif; ?>
            <?php if(!empty($settings['image2']['url'])):?>
                <img src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>" data-aos="fade-up" data-aos-delay="200" data-parallax='{"y" : 50 }'>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>