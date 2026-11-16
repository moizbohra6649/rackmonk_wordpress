<?php
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<section class="banner__area-three fix">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-6 col-md-10 order-0 order-lg-2">
                <div class="banner__img-wrap-three">
                    <?php if(!empty($settings['image']['url'])):?>
                        <img src="<?php echo esc_url($settings['image']['url']); ?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}?>">
                    <?php endif;?>
                    <?php if(!empty($settings['image2']['url'])):?>
                        <img class="hidden-img" src="<?php echo esc_url($settings['image2']['url']); ?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}?>">
                    <?php endif;?>
                    <div class="shape">
                        <?php if(!empty($settings['image3']['url'])):?>
                            <img src="<?php echo esc_url($settings['image3']['url']); ?>" alt="<?php if(!empty($settings['image3']['alt'])){ echo esc_attr($settings['image3']['alt']);}?>" data-aos="fade-down" data-aos-delay="200">
                        <?php endif;?>
                        <?php if(!empty($settings['image4']['url'])):?>
                            <img src="<?php echo esc_url($settings['image4']['url']); ?>" alt="<?php if(!empty($settings['image4']['alt'])){ echo esc_attr($settings['image4']['alt']);}?>" data-aos="fade-left" data-aos-delay="200">
                        <?php endif;?>
                        <?php if(!empty($settings['image5']['url'])):?>
                            <img src="<?php echo esc_url($settings['image5']['url']); ?>" alt="<?php if(!empty($settings['image5']['alt'])){ echo esc_attr($settings['image5']['alt']);}?>" data-aos="fade-down" data-aos-delay="400">
                        <?php endif;?>
                        <?php if(!empty($settings['image6']['url'])):?>
                            <img src="<?php echo esc_url($settings['image6']['url']); ?>" alt="<?php if(!empty($settings['image6']['alt'])){ echo esc_attr($settings['image6']['alt']);}?>" data-aos="fade-up" data-aos-delay="600">
                        <?php endif;?>
                        <?php if(!empty($settings['image7']['url'])):?>
                            <img src="<?php echo esc_url($settings['image7']['url']); ?>" alt="<?php if(!empty($settings['image7']['alt'])){ echo esc_attr($settings['image7']['alt']);}?>" data-aos="fade-right" data-aos-delay="400">
                        <?php endif;?>
                        <?php if(!empty($settings['image8']['url'])):?>
                            <img src="<?php echo esc_url($settings['image8']['url']); ?>" alt="<?php if(!empty($settings['image8']['alt'])){ echo esc_attr($settings['image8']['alt']);}?>" data-aos="fade-down" data-aos-delay="500">
                        <?php endif;?>
                        <?php if(!empty($settings['image9']['url'])):?>
                            <img src="<?php echo esc_url($settings['image9']['url']); ?>" alt="<?php if(!empty($settings['image9']['alt'])){ echo esc_attr($settings['image9']['alt']);}?>" data-aos="fade-down" data-aos-delay="200">
                        <?php endif;?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="banner__content-three">
                    <?php if(!empty($settings['subtitle'])):?>
                    <span class="sub-title">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 10L10 6" stroke="#17BD54" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M6.33333 6.66667C6.51743 6.66667 6.66667 6.51743 6.66667 6.33333C6.66667 6.14924 6.51743 6 6.33333 6C6.14924 6 6 6.14924 6 6.33333C6 6.51743 6.14924 6.66667 6.33333 6.66667Z" fill="#17BD54" stroke="#17BD54" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9.66536 10C9.84946 10 9.9987 9.85077 9.9987 9.66668C9.9987 9.48258 9.84946 9.33334 9.66536 9.33334C9.48127 9.33334 9.33203 9.48258 9.33203 9.66668C9.33203 9.85077 9.48127 10 9.66536 10Z" fill="#17BD54" stroke="#17BD54" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M3.33169 4.8C3.33169 4.41102 3.48621 4.03797 3.76126 3.76291C4.03632 3.48786 4.40937 3.33334 4.79835 3.33334H5.46502C5.85229 3.33311 6.22375 3.17974 6.49835 2.90667L6.96502 2.44C7.10132 2.30294 7.26337 2.19416 7.44185 2.11994C7.62033 2.04572 7.81172 2.00751 8.00502 2.00751C8.19832 2.00751 8.38971 2.04572 8.56819 2.11994C8.74667 2.19416 8.90872 2.30294 9.04502 2.44L9.51169 2.90667C9.78629 3.17974 10.1578 3.33311 10.545 3.33334H11.2117C11.6007 3.33334 11.9737 3.48786 12.2488 3.76291C12.5238 4.03797 12.6784 4.41102 12.6784 4.8V5.46667C12.6786 5.85393 12.832 6.2254 13.105 6.5L13.5717 6.96667C13.7088 7.10297 13.8175 7.26502 13.8917 7.4435C13.966 7.62198 14.0042 7.81337 14.0042 8.00667C14.0042 8.19997 13.966 8.39136 13.8917 8.56984C13.8175 8.74832 13.7088 8.91037 13.5717 9.04667L13.105 9.51334C12.832 9.78794 12.6786 10.1594 12.6784 10.5467V11.2133C12.6784 11.6023 12.5238 11.9754 12.2488 12.2504C11.9737 12.5255 11.6007 12.68 11.2117 12.68H10.545C10.1578 12.6802 9.78629 12.8336 9.51169 13.1067L9.04502 13.5733C8.90872 13.7104 8.74667 13.8192 8.56819 13.8934C8.38971 13.9676 8.19832 14.0058 8.00502 14.0058C7.81172 14.0058 7.62033 13.9676 7.44185 13.8934C7.26337 13.8192 7.10132 13.7104 6.96502 13.5733L6.49835 13.1067C6.22375 12.8336 5.85229 12.6802 5.46502 12.68H4.79835C4.40937 12.68 4.03632 12.5255 3.76126 12.2504C3.48621 11.9754 3.33169 11.6023 3.33169 11.2133V10.5467C3.33147 10.1594 3.17809 9.78794 2.90502 9.51334L2.43835 9.04667C2.30129 8.91037 2.19252 8.74832 2.11829 8.56984C2.04407 8.39136 2.00586 8.19997 2.00586 8.00667C2.00586 7.81337 2.04407 7.62198 2.11829 7.4435C2.19252 7.26502 2.30129 7.10297 2.43835 6.96667L2.90502 6.5C3.17809 6.2254 3.33147 5.85393 3.33169 5.46667V4.8Z" stroke="#17BD54" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <?php echo xhosting_wp_kses($settings['subtitle']); ?>
                    </span>
                    <?php endif;?>
                     <?php if(!empty($settings['title'])):?>
                        <h2 class="title">
                            <?php echo xhosting_wp_kses($settings['title']); ?>
                        </h2>
                    <?php endif; ?>
                    <?php if(!empty($settings['description'])):?>
                        <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($settings['btn_label'])):?>
                    <div class="tg-btn-group">
                        <a class="tg-btn-circle" <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>>
                            <?php if(!empty($settings['btn_icon'])):?>
                                <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <?php endif;?>  
                        </a>
                        <a class="tg-btn tg-border-btn-three" <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>><?php echo xhosting_wp_kses($settings['btn_label']); ?></a>
                        <a class="tg-btn-circle" <?php echo $this->get_render_attribute_string( 'btn_link' ); ?>>
                            <?php if(!empty($settings['btn_icon'])):?>
                                <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <?php endif;?>  
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="banner__shape-wrap">
        <?php if(!empty($settings['image10']['url'])):?>
            <img src="<?php echo esc_url($settings['image10']['url']); ?>" alt="<?php if(!empty($settings['image10']['alt'])){ echo esc_attr($settings['image10']['alt']);}?>">
        <?php endif;?>
        <?php if(!empty($settings['image11']['url'])):?>
            <img src="<?php echo esc_url($settings['image11']['url']); ?>" alt="<?php if(!empty($settings['image11']['alt'])){ echo esc_attr($settings['image11']['alt']);}?>">
        <?php endif;?>
        <?php if(!empty($settings['image12']['url'])):?>
            <img src="<?php echo esc_url($settings['image12']['url']); ?>" alt="<?php if(!empty($settings['image12']['alt'])){ echo esc_attr($settings['image12']['alt']);}?>">
        <?php endif;?>
        <?php if(!empty($settings['image13']['url'])):?>
            <img src="<?php echo esc_url($settings['image13']['url']); ?>" alt="<?php if(!empty($settings['image13']['alt'])){ echo esc_attr($settings['image13']['alt']);}?>">
        <?php endif;?>
    </div>
</section>