<div class="contact__form-box">
    <?php if(!empty($settings['title'])):?>
        <h2 class="title"><?php echo xhosting_wp_kses($settings['title']);?></h2>
    <?php endif;?>
    <?php if(!empty($settings['shortcode'])){
        echo do_shortcode($settings['shortcode']);
    }?>
</div>