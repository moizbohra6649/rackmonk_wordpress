<div class="hosting__item-wrap-three">
    <?php 
        $number_words = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'];
        $counter = 0;  
        foreach($settings['services'] as $item):  
            $word = isset($number_words[$counter]) ? $number_words[$counter] : 'default';
    ?>
    <div class="hosting__item-four">
        <div class="hosting__icon-four">
            <?php if ($item['type'] === 'image' && ($item['icon_img']['url'])) :?>
                <img src="<?php echo esc_url($item['icon_img']['url']);?>" alt="<?php if(!empty($item['icon_img']['alt'])){ echo esc_attr($item['icon_img']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
            <?php else:?>
                <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
            <?php endif;?>
        </div>
        <div class="hosting__content-four">
            <h2 class="title"><?php echo xhosting_wp_kses($item['title']);?></h2>
            <?php if(!empty($item['excerpt'])):?>
            <p><?php echo xhosting_wp_kses($item['excerpt']);?></p>
            <?php endif;?>
            <?php if(!empty($item['btn_label'])):?>
                <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php echo xhosting_wp_kses($item['btn_label']);?>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12.5 13.3333L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12.5 6.66675L15.8333 10.0001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            <?php endif;?>
        </div>
    </div>
    <?php endforeach;?>
</div>
<div class="banner__review hosting__review">
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