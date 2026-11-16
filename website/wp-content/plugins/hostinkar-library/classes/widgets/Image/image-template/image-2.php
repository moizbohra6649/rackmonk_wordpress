<div class="mission__img">
    <?php if(!empty($settings['image']['url'])):?>
        <img src="<?php echo esc_url($settings['image']['url']);?>" alt="<?php if(!empty($settings['image']['alt'])){ echo esc_attr($settings['image']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
    <?php endif;?>
    <?php if(!empty($settings['image2']['url'])):?>
        <img src="<?php echo esc_url($settings['image2']['url']);?>" alt="<?php if(!empty($settings['image2']['alt'])){ echo esc_attr($settings['image2']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>" class="shape" data-parallax='{"x" : 50 }'>
    <?php endif;?>
</div>