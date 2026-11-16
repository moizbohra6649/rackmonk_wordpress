<?php
$footer_copyright = cs_get_option('footer_copyright', __('© 2026 Rackmonk - High Performance Hosting. All rights reserved.', 'hostinkar'));
?>
<footer class="default-footer cta__bg">
    <div class="container">
        <div class="footer__copyright-text text-center">
            <?php echo wp_kses_post($footer_copyright); ?>
        </div>
    </div>
</footer>
