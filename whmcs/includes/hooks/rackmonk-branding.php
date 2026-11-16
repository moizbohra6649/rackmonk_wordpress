<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

add_hook('ClientAreaHeadOutput', 1, function () {
    $icon = htmlspecialchars(rtrim(\WHMCS\Utility\Environment\WebHelper::getBaseUrl(), '/') . '/assets/img/rackmonk-icon-dark.png', ENT_QUOTES, 'UTF-8');

    return '<link rel="icon" type="image/png" href="' . $icon . '">'
        . '<link rel="apple-touch-icon" href="' . $icon . '">'
        . '<style>
            #fullpage-overlay, .fullpageloader { background-color: rgba(65, 65, 65, .72) !important; }
            #fullpage-overlay .inner-wrapper img { content: url("' . $icon . '"); width: 110px; height: auto; }
            .fullpageloader p { font-size: 0; }
            .fullpageloader p::before { content: ""; display: inline-block; width: 110px; height: 110px; background: url("' . $icon . '") center / contain no-repeat; }
            .fullpageloader p i { display: none; }
            #fullpage-overlay .inner-wrapper img, .fullpageloader p::before { animation: rm-loader-pulse 1.5s ease-in-out infinite; }
            @keyframes rm-loader-pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.08); opacity: .75; } }
            @media (prefers-reduced-motion: reduce) { #fullpage-overlay .inner-wrapper img, .fullpageloader p::before { animation: none; } }
        </style>';
});
