<div class="features__item">
    <div class="features__thumb">
       <?php if(!empty($settings['image']['url'])):?>
            <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>">
        <?php endif;?>
    </div>
    <div class="features__content">
        <?php if(!empty($settings['title'])):?>
            <h3 class="title">
                <?php echo xhosting_wp_kses($settings['title']); ?>
            </h3>
        <?php endif; ?>
        <?php if(!empty($settings['description'])):?>
            <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
        <?php endif; ?>
    </div>
</div>