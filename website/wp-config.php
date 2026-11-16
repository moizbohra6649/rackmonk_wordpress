<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'rackmonk_wordpress');

/** Database username */
define('DB_USER', 'root');

/** Database password */
define('DB_PASSWORD', '');

/** Database hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

define('WP_MEMORY_LIMIT', '512M');

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'z3:(] |]SAV@D7, JBlAVg.KxD++U>,Nb]L?SEM4=i))mB#& *:|nnk[v#J*_@q6');
define('SECURE_AUTH_KEY', 'IW]M0((P.-=G`A0)zEE2hFz.:/x^YKl0t(CO,8e+ B{wF[(FBVx_ryD#r~I5U5ds');
define('LOGGED_IN_KEY', 'kR9:;XmDR9J6Y{>`lV]sv~6;;3RVenQ.o%Vs(I#.aI`hiunE1knCsp9DT,q1lH (');
define('NONCE_KEY', '4s_&T@5J-P>)B,xfo5cwE`b=Cs`5snx_9%lB1[n0C8qq=:7-hfH~OFsnc-Pqlhx?');
define('AUTH_SALT', '19ORk22< _]`y}eHRXj|[=R@!cA58g$TgwFM$k|}g$&W9eiaq 65WPC)S/L[ :+L');
define('SECURE_AUTH_SALT', '(WJ[.NNrE/3:=u*!fvs#^DGU7v?r^TnxJE6FMGEqh`acJ lHtSR%mJ/0tFI3}k?6');
define('LOGGED_IN_SALT', 'QwV}CZ}15uzE1wtU.k*Bg&Ym,>KxUy2cyknrq9,FT/?h],k?m!wrr_[9wd@}a:d<');
define('NONCE_SALT', '|FD9B!$6 {>u#7$(eGK?#A%)IO&]^-?foYy88+;i3u}jc6<ih#K0gv{EDmeiDf,l');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', false);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
