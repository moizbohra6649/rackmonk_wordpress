<div class="breadcrumb__menu">
    <ul class="list-wrap">
        <?php foreach($settings['lists'] as $item):?>
            <li>
                <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php echo xhosting_wp_kses($item['title']);?> <?php if(!empty($item['badge'])):?> <span><?php echo xhosting_wp_kses($item['badge']);?></span><?php endif;?></a>
            </li>
        <?php endforeach;?>
    </ul>
</div>