<?php

use WHMCS\Database\Capsule;

include_once __DIR__ . '/functions.php';
add_hook('ClientAreaPage', 1, function ($vars) {

    global $CONFIG;
    if (isset($CONFIG['Template']) && $CONFIG['Template'] == "aqua-sky-one") {
        include_once __DIR__ . '/functions.php';
        return aquaskythemeactivator_doCheckLicenseTheme($vars);
    }
});

add_hook('ClientAreaPage', 1, function ($vars) {
    global $CONFIG;
    if (!isset($CONFIG['Template']) || $CONFIG['Template'] !== "aqua-sky-one") {
        return;
    }
    aquaskythemeactivator_doCheckLicenseTheme($vars);

    // Fetch all relevant settings
    $settings = Capsule::table('mod_portal_content')
        ->whereIn('setting', [
            'heading',
            'description',
            'button_text',
            'button_link',
            'position',
            'banner_image'

        ])
        ->pluck('value', 'setting');
    global $smarty;
    $smarty->assign('homepage_banner_heading', $settings['heading'] ?? '');
    $smarty->assign('homepage_banner_description', $settings['description'] ?? '');
    $smarty->assign('homepage_banner_button_text', $settings['button_text'] ?? '');
    $smarty->assign('homepage_banner_position', $settings['position'] ?? 'center');
    $smarty->assign('homepage_banner_button_link', $settings['button_link'] ?? '');
    $smarty->assign('homepage_banner_image', $settings['banner_image'] ?? '');
});

add_hook('ClientAreaPage', 1, function ($vars) {
    //check for active language
    global $CONFIG;
    $language = $CONFIG['Language'];
    if (isset($_SESSION['Language']) && !empty($_SESSION['Language'])) {
        $language = $_SESSION['Language'];
    }
    include_once(ROOTDIR . '/modules/addons/aquaskythemeactivator/lang/' . $language . '_aqua-sky-one.php');

    $vars['lang'] = $_LANG;
    return $vars;
});