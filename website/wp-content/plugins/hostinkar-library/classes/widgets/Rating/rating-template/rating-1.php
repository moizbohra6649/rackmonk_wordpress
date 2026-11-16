<div class="banner__review">
    <div class="rating">
        <?php
        $rating = isset($settings['rating']) ? floatval($settings['rating']) : 0;
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            echo '<i class="fas fa-star"></i>';
        }

        // Half star
        if ($halfStar) {
            echo '<i class="fas fa-star-half-alt"></i>';
        }

        // Empty stars
        for ($i = 0; $i < $emptyStars; $i++) {
            echo '<i class="far fa-star"></i>';
        }
        ?>
    </div>
    <p>
        <?php if(!empty($settings['rate'])){
            echo xhosting_wp_kses($settings['rate']);
        }?>
        <span><?php echo esc_html($rating); ?></span> 
        <?php if(!empty($settings['outoftext'])){
            echo xhosting_wp_kses($settings['outoftext']);
        }?>
        <?php if(!empty($settings['total_rating'])){?>
            <span><?php echo xhosting_wp_kses($settings['total_rating']);?></span> 
       <?php }?>
         <?php if(!empty($settings['review_text'])){
            echo xhosting_wp_kses($settings['review_text']);
        }?>
    </p>
</div>
