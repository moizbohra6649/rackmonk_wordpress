<div class="slider__marquee clearfix marquee-wrap">
    <div class="marquee_mode marquee__group">
        <?php foreach($settings['movings'] as $item):?>
            <h6 class="marquee__item">
                <?php echo xhosting_wp_kses($item['title']); ?>
            </h6>
        <?php endforeach;?>
    </div>
</div>