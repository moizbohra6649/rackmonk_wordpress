<div class="testimonial__item">
    <?php
    $rating = floatval($settings['rating']);
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    ?>

    <div class="rating">
        <?php for ($i = 0; $i < $fullStars; $i++) : ?>
            <img src="<?php echo esc_url($settings['rating_img']['url']);?>" alt="<?php if(!empty($settings['rating_img']['alt'])){ echo esc_attr($settings['rating_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php endfor; ?>

        <?php if ($halfStar) : ?>
            <img src="<?php echo esc_url($settings['rating_img']['url']);?>" alt="<?php if(!empty($settings['rating_img']['alt'])){ echo esc_attr($settings['rating_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php endif; ?>

        <?php for ($i = 0; $i < $emptyStars; $i++) : ?>
             <img src="<?php echo esc_url($settings['rating_img']['url']);?>" alt="<?php if(!empty($settings['rating_img']['alt'])){ echo esc_attr($settings['rating_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        <?php endfor; ?>
    </div>

    <div class="testimonial__content">
        <?php if(!empty($settings['feedback'])):?>
            <p><?php echo xhosting_wp_kses($settings['feedback']);?></p>
        <?php endif;?>
    </div>
    <div class="testimonial__author">
        <div class="thumb">
            <img src="<?php echo esc_url($settings['authore']['url']);?>" alt="<?php if(!empty($settings['authore']['alt'])){ echo esc_attr($settings['authore']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        </div>
        <div class="content">
            <?php if(!empty($settings['name'])):?>
                <h2 class="title"><?php echo xhosting_wp_kses($settings['name']);?></h2>
            <?php endif;?>
             <?php if(!empty($settings['designation'])):?>
                <span><?php echo xhosting_wp_kses($settings['designation']);?></span>
            <?php endif;?>
        </div>
    </div>
</div>