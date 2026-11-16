<div class="team__item">
    <?php if($settings['image']['url']):?>
        <div class="team__thumb">
            <img src="<?php echo esc_url($settings['image']['url']);?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
        </div>
    <?php endif;?>
    <div class="team__content">
        <h2 class="title">
            <?php echo xhosting_wp_kses($settings['title']);?>
        </h2>
        <?php if(!empty($settings['designation'])):?>
            <span><?php echo xhosting_wp_kses($settings['designation']);?></span>
        <?php endif;?>
    </div>
</div>