<div class="testimonial__bottom-content">
    <?php if(!empty($settings['review_logo']['url'])):?>
        <img src="<?php echo esc_url($settings['review_logo']['url']); ?>" alt="<?php if(!empty($settings['review_logo']['alt'])){ echo esc_attr($settings['review_logo']['alt']);}?>">
    <?php endif;?>
    <?php if(!empty($settings['review_text'])):?>
        <p>
            <?php echo xhosting_wp_kses($settings['review_text']); ?>
        </p>
    <?php endif;?>
</div>