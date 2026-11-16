<div class="footer__bottom-menu">
    <ul class="list-wrap">
        <?php foreach($settings['links'] as $item):?>
            <li>
                <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"><?php echo xhosting_wp_kses($item['title']);?></a>
            </li>
        <?php endforeach;?>
    </ul>
</div>