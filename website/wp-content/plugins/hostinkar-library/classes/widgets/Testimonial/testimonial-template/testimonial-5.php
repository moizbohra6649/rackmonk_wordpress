<div class="swiper-container fix testimonial-active-two">
    <div class="swiper-wrapper">
        <?php foreach($settings['testimonials'] as $item):?>
            <div class="swiper-slide">
                <div class="testimonial__item testimonial__item-three">
                    <div class="rating">
                        <img src="<?php echo esc_url($item['rating_logo']['url']);?>" alt="<?php if(!empty($item['rating_logo']['alt'])){ echo esc_attr($item['rating_logo']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                    </div>
                    <?php if(!empty($item['feedback'])):?>
                        <div class="testimonial__content">
                            <p><?php echo xhosting_wp_kses($item['feedback']);?></p>
                        </div>
                    <?php endif;?>
                    <div class="testimonial__author">
                        <div class="content">
                            <?php if(!empty($item['name'])):?>
                                <h2 class="title"><?php echo xhosting_wp_kses($item['name']);?></h2>
                            <?php endif;?>
                            <?php if(!empty($item['designation'])):?>
                                <span><?php echo xhosting_wp_kses($item['designation']);?></span>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </div>
</div>