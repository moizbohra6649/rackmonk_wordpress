<div class="rm-feature-badges">
<?php if(!empty($settings['title'])):?>
    <div class="brand__title">
    <?php echo xhosting_wp_kses($settings['title']); ?>
    </div>
<?php endif; ?>
<ul class="brand__item-wrap rm-badge-grid">
    <?php foreach(($settings['brands'] ?? []) as $item):
        $image = $item['brand_img'] ?? [];
        if (empty($image['url'])) continue;
        $url = $image['url'];
        $id = (int) ($image['id'] ?? 0);
        $file = $id ? get_attached_file($id) : false;
        if ($file && is_file($file)) $url = add_query_arg('ver', filemtime($file), $url);
        $alt = $image['alt'] ?? '';
        if ($alt === '' && $id) $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
    ?>
    <li class="brand__item">
        <img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" decoding="async">
    </li>
    <?php endforeach; ?>
</ul>
</div>
