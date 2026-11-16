<div class="counter__item-three">
    <h2 class="count"><span class="counter-number"><?php echo xhosting_wp_kses($settings['count']);?></span><?php if($settings['prefix']): ?> <?php echo esc_attr($settings['prefix']);?><?php endif; ?></h2>
    <p><?php echo xhosting_wp_kses($settings['title']);?></p>
</div>