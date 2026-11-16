<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<section class="banner__area-two">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-7 col-lg-6 order-0 order-lg-2">
                <div class="banner__img-wrap-two">
                    <?php if(!empty($settings['image']['url'])):?>
                        <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>">
                    <?php endif;?>
                    <?php if(!empty($settings['image2']['url'])):?>
                        <img src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>" data-aos="fade-up" data-aos-delay="200">
                    <?php endif;?>
                    <?php if(!empty($settings['image3']['url'])):?>
                        <img src="<?php echo esc_url($settings['image3']['url']); ?>" alt="<?php if(!empty($settings['image3']['alt'])){ echo esc_attr($settings['image3']['alt']);}?>" data-aos="fade-left" data-aos-delay="200">
                    <?php endif;?>
                </div>
            </div>
            <div class="col-xl-5 col-lg-6">
                <div class="banner__content-two">
                    <?php if(!empty($settings['title'])):?>
                        <h2 class="title">
                            <?php echo xhosting_wp_kses($settings['title']); ?>
                        </h2>
                    <?php endif; ?>
                    <?php if(!empty($settings['description'])):?>
                        <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($settings['lists'])):?>
                        <ul class="list-wrap">
                            <?php foreach($settings['lists'] as $item):?>
                                <li>
                                    <svg width="17" height="13" viewBox="0 0 17 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 6.5L6 11.5L16 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php echo xhosting_wp_kses($item['title']); ?>
                                </li>
                            <?php endforeach;?>
                        </ul>
                    <?php endif; ?>
                    <div class="banner__btn">
                        <?php if(!empty($settings['btn_label'])):?>
                        <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn tg-btn-two">
                            <?php if(!empty($settings['btn_icon'])):?>
                                <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <?php endif;?>
                            <?php echo xhosting_wp_kses($settings['btn_label']); ?>
                        </a>
                        <?php endif;?>
                        <?php if(!empty($settings['regular_price'])):?>
                            <span class="price"><?php echo xhosting_wp_kses($settings['regular_price']); ?></span>
                        <?php endif;?>
                    </div>
                    <div class="banner__review banner__review-two">
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
                </div>
            </div>
        </div>
    </div>
</section>