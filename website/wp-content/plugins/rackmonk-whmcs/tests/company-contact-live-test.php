<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function company_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS: $message\n";
}
try {
    $official = get_option('rackmonk_company_contact_source');
    company_check(($official['source'] ?? '') === 'https://www.rackmonk.com/contact/', 'Contact data records the official source');
    foreach (['/', '/contact-us/', '/about-us/', '/pricing/'] as $path) {
        $response = wp_remote_get(home_url($path), ['timeout' => 30]);
        company_check(!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200, 'Page available: ' . $path);
        $html = wp_remote_retrieve_body($response);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $text = $dom->textContent;
        foreach (['203, Regency Palace', 'Opposite Marriott Hotel', '452010', $official['phone'], $official['email']] as $value) company_check(strpos($text, $value) !== false, $path . ' displays ' . $value);
        company_check(strpos($html, $official['tel']) !== false && strpos($html, 'mailto:' . $official['email']) !== false, $path . ' has correct call/email links');
        company_check(strpos($text, '1454 Street') === false && strpos($text, 'sayhello@rackmonk.com') === false && strpos($text, 'Marquette, Michigan') === false, $path . ' has no old/demo contact details');
        foreach ($official['socials'] as $network => $url) company_check(strpos($html, $url) !== false, $path . ' uses official ' . $network . ' profile');
    }
    foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'numberposts' => -1]) as $form) {
        $mail = get_post_meta($form->ID, '_mail', true);
        company_check(($mail['recipient'] ?? '') === $official['email'], 'Form ' . $form->ID . ' delivery recipient configured');
        company_check((bool) get_post_meta($form->ID, '_rackmonk_contact_mail_backup_v1', true), 'Form ' . $form->ID . ' previous settings backed up');
    }
    company_check(strpos(Rackmonk_Site_Content::text('about_identity_first'), 'Indore') !== false, 'About Us uses editable verified location');
    company_check(has_custom_logo() && (int) get_theme_mod('custom_logo') === (int) get_option('rackmonk_header_logo_id'), 'Header logo uses WordPress Site Identity');
    $locations = get_nav_menu_locations();
    $menu = wp_get_nav_menu_object('Main Menu');
    company_check($menu && (int) ($locations['menu-1'] ?? 0) === (int) $menu->term_id, 'Primary menu is the admin-managed Main Menu');
    echo "$checks checks passed. No email sent; delivery/SMTP not verified.\nVERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n");
    exit(1);
}
