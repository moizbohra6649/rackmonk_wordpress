<?php

/**
 *Google Font Load
 */
if (!function_exists('xhosting_fonts_url')):

	function xhosting_fonts_url(){
		if ('off' === _x('on', 'Inter: on or off', 'hostinkar')) {
			return '';
		}
		// Only the weights the design uses, with font-display: swap so text shows immediately.
		return 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap';
	}
endif;


/**
 * Enqueue scripts and styles.
 */
function xhosting_scripts() {
	// CSS files
	$styles = [
		'xhosting-google-fonts' => [ xhosting_fonts_url(), [], null ],
		'bootstrap'             => [ XHOSTING_CSS_PATH . '/bootstrap.min.css' ],
		'e-animations'          => [ XHOSTING_CSS_PATH . '/animate.min.css' ],
		'fontawesome.css'       => [ XHOSTING_CSS_PATH . '/fontawesome-all.min.css' ],
		'magnific-popup'        => [ XHOSTING_CSS_PATH . '/magnific-popup.css' ],
		'nice-select'           => [ XHOSTING_CSS_PATH . '/nice-select.css' ],
		'slick'           		=> [ XHOSTING_CSS_PATH . '/slick.css' ],
		'odometer-the' 			=> [ XHOSTING_CSS_PATH . '/odometer.css' ],
		'swiper-xhosting'       => [ XHOSTING_CSS_PATH . '/swiper-bundle.min.css' ],
		'xhosting-icons'      	=> [ XHOSTING_CSS_PATH . '/default-icons.css' ],
		'xhosting-default'      => [ XHOSTING_CSS_PATH . '/default.css' ],
		'xhosting-aos'      	=> [ XHOSTING_CSS_PATH . '/aos.css' ],
		'xhosting-main'         => [ XHOSTING_CSS_PATH . '/main.css', [], filemtime( get_template_directory() . '/assets/css/main.css' ) ],
	];

	foreach ( $styles as $handle => $data ) {
		wp_enqueue_style( $handle, ...$data );
	}

	// RTL Styles
	$your_curnt_lang = apply_filters( 'wpml_current_language', null );
	if ( is_rtl() && $your_curnt_lang !== 'en' ) {
		wp_enqueue_style( 'xhosting-rtl', XHOSTING_CSS_PATH . '/rtl.css' );
	}

	// Theme default style
	wp_enqueue_style( 'xhosting-style', get_stylesheet_uri(), [], filemtime( get_stylesheet_directory() . '/style.css' ) );

	// Brand colours + typography (driven by Elementor Site Settings).
	wp_enqueue_style( 'rackmonk-brand', XHOSTING_CSS_PATH . '/rackmonk-brand.css', [ 'xhosting-main' ], filemtime( get_template_directory() . '/assets/css/rackmonk-brand.css' ) );

	// JS files
	$scripts = [
        'jquery-masonry'   => [ [], false, true ],
        'imagesloaded'     => [ [ 'jquery' ], false, true ],
        'bootstrap-bundle' => [ [ 'jquery' ], XHOSTING_JS_PATH . '/bootstrap.min.js', true ],
        'swiper-bundle'    => [ [ 'jquery' ], XHOSTING_JS_PATH . '/swiper-bundle.min.js', true ],
        'wow'              => [ [ 'jquery' ], XHOSTING_JS_PATH . '/wow.min.js', true ],
        'appear'           => [ [ 'jquery' ], XHOSTING_JS_PATH . '/jquery.appear.js', true ],
        'magnific-popup'   => [ [ 'jquery' ], XHOSTING_JS_PATH . '/jquery.magnific-popup.min.js', true ],
        'nice-select'      => [ [ 'jquery' ], XHOSTING_JS_PATH . '/nice-select.js', true ],
        'slick'            => [ [ 'jquery' ], XHOSTING_JS_PATH . '/slick.min.js', true ],
        'counterup'        => [ [ 'jquery' ], XHOSTING_JS_PATH . '/jquery.counterup.min.js', true ],
        'marquee'          => [ [ 'jquery' ], XHOSTING_JS_PATH . '/jquery.marquee.min.js', true ],
        'odometer'         => [ [ 'jquery' ], XHOSTING_JS_PATH . '/jquery.odometer.min.js', true ],
        'aos'              => [ [ 'jquery' ], XHOSTING_JS_PATH . '/aos.js', true ],
        'xhosting-main'    => [ [ 'jquery' ], XHOSTING_JS_PATH . '/main.js', true ],
    ];

    foreach ( $scripts as $handle => $args ) {
        list( $deps, $src, $in_footer ) = $args;

        if ( $src ) {
            wp_enqueue_script( $handle, $src, $deps, null, $in_footer );
        } else {
            wp_enqueue_script( $handle ); // assumes it's a core script like 'jquery-masonry'
        }
    }

	// RTL JS
	if ( is_rtl() && $your_curnt_lang !== 'en' ) {
		wp_enqueue_script( 'xhosting-rtl', XHOSTING_JS_PATH . '/rtl.js', [ 'jquery' ], '1.0', true );
	}

	// WordPress native comment-reply
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'xhosting_scripts' );
