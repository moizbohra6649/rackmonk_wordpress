<div class="testimonial__item-four">
    <?php if(!empty($settings['authore']['url'])):?>
        <div class="testimonial__img">
            <img src="<?php echo esc_url($settings['authore']['url']);?>" alt="<?php if(!empty($settings['authore']['alt'])){ echo esc_attr($settings['authore']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        </div>
    <?php endif;?>
    <div class="testimonial__content-three">
        <?php \Elementor\Icons_Manager::render_icon( $settings['quote'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php if(!empty($settings['feedback'])):?>
            <p><?php echo xhosting_wp_kses($settings['feedback']);?></p>
        <?php endif;?>
        <div class="testimonial__author">
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
</div>