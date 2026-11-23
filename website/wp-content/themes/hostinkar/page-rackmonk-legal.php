<?php
/**
 * Template Name: Legal Page
 *
 * Terms, refund and privacy pages. The text comes from the normal WordPress editor; this template adds
 * numbered sections with an "On this page" list (built from the H2 headings), the last-updated date,
 * links to the other legal pages and a contact box (email/phone from Rackmonk Options → Footer Options).
 */
defined('ABSPATH') || exit;
get_header();

while (have_posts()) :
    the_post();
    $content = apply_filters('the_content', get_the_content());

    // Give every H2 an anchor and collect them for the "On this page" list.
    $toc = [];
    $content = preg_replace_callback('/<h2\b([^>]*)>(.*?)<\/h2>/is', static function ($m) use (&$toc) {
        $label = trim(wp_strip_all_tags($m[2]));
        if ($label === '') return $m[0];
        $id = preg_match('/\bid=["\']([^"\']+)["\']/', $m[1], $existing) ? $existing[1] : 'section-' . (sanitize_title($label) ?: count($toc) + 1);
        $base = $id;
        for ($n = 2; in_array($id, array_column($toc, 0), true); $n++) $id = $base . '-' . $n;
        $toc[] = [$id, $label];
        $attrs = preg_replace('/\sid=["\'][^"\']*["\']/', '', $m[1]);
        return '<h2' . $attrs . ' id="' . esc_attr($id) . '">' . $m[2] . '</h2>';
    }, $content);

    // Other pages that use this template.
    $others = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 10, 'orderby' => 'menu_order title', 'order' => 'ASC', 'post__not_in' => [get_the_ID()], 'meta_key' => '_wp_page_template', 'meta_value' => 'page-rackmonk-legal.php']);

    $options = get_option('xhosting_theme_options');
    $options = is_array($options) ? $options : [];
    $first = static function ($value) { $parts = preg_split('/[,\n]+/', (string) $value); return trim((string) ($parts[0] ?? '')); };
    $email = $first($options['footer_email'] ?? '') ?: get_option('admin_email');
    $phone = $first($options['footer_phone'] ?? '');
    $contact_page = get_page_by_path('contact-us');
    ?>
    <main class="rm-legal">
        <div class="rm-legal-wrap">
            <aside class="rm-legal-aside" aria-label="<?php esc_attr_e('Page navigation', 'hostinkar'); ?>">
                <?php if (count($toc) > 1) : ?>
                    <details class="rm-legal-toc" open>
                        <summary><?php esc_html_e('On this page', 'hostinkar'); ?></summary>
                        <ol>
                            <?php foreach ($toc as [$id, $label]) : ?>
                                <li><a href="#<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></a></li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                <?php endif; ?>
                <?php if ($others) : ?>
                    <nav class="rm-legal-related" aria-label="<?php esc_attr_e('Other policies', 'hostinkar'); ?>">
                        <span><?php esc_html_e('Other policies', 'hostinkar'); ?></span>
                        <?php foreach ($others as $other) : ?>
                            <a href="<?php echo esc_url(get_permalink($other)); ?>"><?php echo esc_html(get_the_title($other)); ?> <span aria-hidden="true">&rarr;</span></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </aside>

            <article <?php post_class('rm-legal-doc'); ?>>
                <p class="rm-legal-meta">
                    <span class="rm-legal-chip"><?php esc_html_e('Legal', 'hostinkar'); ?></span>
                    <span><?php printf(esc_html__('Last updated: %s', 'hostinkar'), esc_html(get_the_modified_date('j F Y'))); ?></span>
                </p>
                <div class="rm-legal-body">
                    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output. ?>
                </div>
                <footer class="rm-legal-contact">
                    <div>
                        <h2 class="rm-legal-contact-title"><?php esc_html_e('Questions about this policy?', 'hostinkar'); ?></h2>
                        <p><?php esc_html_e('Our team is happy to explain anything on this page before you buy or at any time after.', 'hostinkar'); ?></p>
                    </div>
                    <div class="rm-legal-contact-links">
                        <?php if ($email) : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?>
                        <?php if ($phone) : ?><a href="tel:<?php echo esc_attr((strpos($phone, '+') === 0 ? '+' : '') . preg_replace('/\D/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a><?php endif; ?>
                        <?php if ($contact_page) : ?><a class="rm-legal-contact-button" href="<?php echo esc_url(get_permalink($contact_page)); ?>"><?php esc_html_e('Contact us', 'hostinkar'); ?></a><?php endif; ?>
                    </div>
                </footer>
            </article>
        </div>
    </main>
    <script>
    (function () {
        var toc = document.querySelector('.rm-legal-toc');
        if (toc && window.matchMedia && window.matchMedia('(max-width: 991px)').matches) toc.removeAttribute('open');
    })();
    </script>
    <?php
endwhile;

get_footer();
