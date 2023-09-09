<?php

// BEGIN iThemes Security - Do not modify or remove this line
// iThemes Security Config Details: 2
define( 'DISALLOW_FILE_EDIT', true ); // Disable File Editor - Security > Settings > WordPress Tweaks > File Editor
// END iThemes Security - Do not modify or remove this line

define( 'ITSEC_ENCRYPTION_KEY', 'fHUyUkYqYUNRRWFAUzYhcjpgX2AhO0g4YHwhOz5XNVdmLEBoNlFjZTZSa3shTChOWy48RElaNjhjNCtadGFeUg==' );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'cse' );

/** Database username */
define( 'DB_USER', 'cse_admin' );

/** Database password */
define( 'DB_PASSWORD', 'abc@123' );

/** Database hostname */
define( 'DB_HOST', 'cse-mysql' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'aGPp5I,#/BVyzgvR>SnZWP89#_x7trSOatT%7j<PvoJ6VL|#BANFIJrw^XL0*WOz' );
define( 'SECURE_AUTH_KEY',  '{2D-~b1PC*cp2e$H/D?%`AuwL4^OkBa/0xW(^zS+| IOY,~f)Jd)#8W}] X,?zg$' );
define( 'LOGGED_IN_KEY',    'f&Vy|xfLFG7.]V.vjF)q#kwgql@pQ9Cs~?qL>&%H+$54n]5.$^tG{;_^kdN EPeh' );
define( 'NONCE_KEY',        'GDSr=LYg=6RK[P;l0O*tIe@p]24Ae/C&ehu(En71Nw$q~5NwzIPr4A2W7Fz3W`)6' );
define( 'AUTH_SALT',        'Z*0J5o!TXHE]pcW8w1e[IU|2)j<=l<X7%)i$aDKW<>p-`>$)9,IXDl-Z($q[2O^L' );
define( 'SECURE_AUTH_SALT', '&j?{3PM?6T=X/zq7j0oU29VbTcJl%Hw#*:rHg#0ntr3)+F7*~M#oOVXV}QD@c<Bs' );
define( 'LOGGED_IN_SALT',   'B/MhyimJ-8Ps)Mg[1Z|HS+lx@Mvu;5eCvm|t)x O@]2hoNGI,hJh?;.Z$uQ/733V' );
define( 'NONCE_SALT',       'E2m&zAqAd_C<Kn>2U<&y@A4Ib@]V<jfX>#?0_ady6?Evq(ONh[iuWPnc:O*UEX(*' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define('FS_METHOD', 'direct');
