<div class="counter__wrap">
<?php foreach($settings['counters'] as $item):?>
    <div class="counter__item">
        <h2 class="count"><span class="counter-number"><?php echo xhosting_wp_kses($item['count']);?></span><?php if($item['prefix']): ?><?php echo esc_attr($item['prefix']);?><?php endif; ?></h2>
        <p><?php echo xhosting_wp_kses($item['title']);?></p>
    </div>
    <?php endforeach;?>
</div>