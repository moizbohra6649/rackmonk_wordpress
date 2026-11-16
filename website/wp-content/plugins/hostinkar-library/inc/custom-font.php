<?php 
/**
 * Add Multiple Font Groups to Elementor
 */
add_filter( 'elementor/fonts/groups', 'prefix_elementor_custom_fonts_group', 10, 1 );
function prefix_elementor_custom_fonts_group( $font_groups ) {
	$font_groups['Test So:hne Extraleicht'] = __( 'Test So:hne Extraleicht' );
	$font_groups['Host Grotesk'] = __( 'Host Grotesk' );
	$font_groups['Misspiece'] = __( 'Misspiece' );
	$font_groups['Ethereal Demo'] = __( 'Ethereal Demo' );
	$font_groups['Field Demo'] = __( 'Field Demo' );
	$font_groups['Test So:hne'] = __( 'Test So:hne' );
	return $font_groups;
}

/**
 * Add Multiple Group Fonts to Elementor
 */
add_filter( 'elementor/fonts/additional_fonts', 'prefix_elementor_custom_fonts', 10, 1 );
function prefix_elementor_custom_fonts( $additional_fonts ) {
	// Font name => Font group
	$additional_fonts['Test So:hne Extraleicht'] = 'Test So:hne Extraleicht';
	$additional_fonts['Host Grotesk'] = 'Host Grotesk';
	$additional_fonts['Misspiece'] = 'Misspiece';
	$additional_fonts['Ethereal Demo'] = 'Ethereal Demo';
	$additional_fonts['Field Demo'] = 'Field Demo';
	$additional_fonts['Test So:hne'] = 'Test So:hne';
	return $additional_fonts;
}
