<article class="rm-migration-card">
    <div class="rm-migration-card-top">
        <span class="rm-migration-number"><?php echo esc_html($settings['rackmonk_step_number'] ?? '01'); ?></span>
        <?php $complete = ($settings['rackmonk_step_number'] ?? '') === '03'; ?>
        <svg class="rm-migration-direction<?php echo $complete ? ' is-complete' : ''; ?>" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="<?php echo $complete ? 'm5 12 4 4L19 6' : 'M5 12h14m-6-6 6 6-6 6'; ?>"/></svg>
    </div>
    <?php if (!empty($settings['title'])): ?>
        <h3 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h3>
    <?php endif; ?>
    <?php if (!empty($settings['description'])): ?>
        <p><?php echo xhosting_wp_kses($settings['description']); ?></p>
    <?php endif; ?>
</article>
