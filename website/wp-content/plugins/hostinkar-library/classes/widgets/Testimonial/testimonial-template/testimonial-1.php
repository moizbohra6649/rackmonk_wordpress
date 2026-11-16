<div class="testimonial__video-wrap" <?php if(!empty($settings['authore']['url'])):?> data-background="<?php echo esc_url($settings['authore']['url']);?>" <?php endif;?>>
    <div class="play-btn">
        <a aria-label="video" href="<?php echo esc_url($settings['video_link']['url']);?>" class="popup-video">
            <svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.97067e-08 0.999794V16.9998C-5.0094e-05 17.1777 0.0473748 17.3524 0.137382 17.5059C0.227388 17.6594 0.356717 17.7861 0.512024 17.8729C0.667332 17.9598 0.842993 18.0036 1.02088 17.9999C1.19878 17.9961 1.37245 17.945 1.524 17.8518L14.524 9.85179C14.6696 9.76233 14.7898 9.63704 14.8733 9.48788C14.9567 9.33873 15.0005 9.17069 15.0005 8.99979C15.0005 8.8289 14.9567 8.66086 14.8733 8.51171C14.7898 8.36255 14.6696 8.23726 14.524 8.14779L1.524 0.147794C1.37245 0.0545582 1.19878 0.00344584 1.02088 -0.000270125C0.842993 -0.00398609 0.667332 0.0398289 0.512024 0.126654C0.356717 0.21348 0.227388 0.340171 0.137382 0.493657C0.0473748 0.647142 -5.0094e-05 0.821864 3.97067e-08 0.999794Z" fill="currentColor" />
            </svg>
        </a>
    </div>
    <div class="testimonial__quote">
        <?php \Elementor\Icons_Manager::render_icon( $settings['quote'], [ 'aria-hidden' => 'true' ] ); ?>
        <?php if(!empty($settings['feedback'])):?>
            <p><?php echo xhosting_wp_kses($settings['feedback']);?></p>
        <?php endif;?>
        <div class="testimonial__quote-author">
             <?php if(!empty($settings['name'])):?>
                <h6 class="title"><?php echo xhosting_wp_kses($settings['name']);?></h6>
            <?php endif;?>
             <?php if(!empty($settings['designation'])):?>
                <span><?php echo xhosting_wp_kses($settings['designation']);?></span>
            <?php endif;?>
        </div>
    </div>
</div>