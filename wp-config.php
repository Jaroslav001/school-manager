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
define('DB_NAME', 'newanj');

/** Database username */
define('DB_USER', 'PjQ94t5A');

/** Database password */
define('DB_PASSWORD', ',MX%U:go}gzm(.~oU4>-');

/** Database hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

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
define('AUTH_KEY',         'zUlx@`[g7c4M5+?T6XJtq-X)4xPf__)yPSP$F,#ItD:bL;vIu5(nUhk+8e_!#;3M');
define('SECURE_AUTH_KEY',  ',1QZAaHcI7t5U3nd+z6Wek,+aE-VBex;A2TQgkgh6OwiUP`*MwOc}skx>z^yo#6C');
define('LOGGED_IN_KEY',    'v#S|5fT)Y*NknDuo]r9,Y~OJ8-Jll@Hpe<(H_aE;6oiK&0LZ$@+;|UA[KsWY$]6@');
define('NONCE_KEY',        '1S[bWk*!xy,eQ|^Z~NheX>M,c2?YB%8:0d ?6H@9!Ba=qe:r]qI`t3lw{QYN^zzd');
define('AUTH_SALT',        'QzAtn0ltaCm:8b,M-2^q{`K&99zn:?H@4_6S~u/*]-zwE|8Y*[JB^Cdq:;NxMW9C');
define('SECURE_AUTH_SALT', '&Rzrc_85SJj`d{h.SG/VPW<O|bOC0rmk$Pq#_w[0BshxQuC/wG8fO%v4dKIrX#&K');
define('LOGGED_IN_SALT',   '{;@ClO3WgDl:?8VxkLooQ.!?0]t_d(a.[B?E?/1InQA8wPY*_9B,,RnQ-Tl~)bh(');
define('NONCE_SALT',       '%r|vWaE*>kTt`_KS[e:Hu9qPepF=Y{7]7T.NqK8R;.j~QWp-x(I`TKSIO_tVG<4f');

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
define('WP_DEBUG', true);

/* Add any custom values between this line and the "stop editing" line. */

define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (! defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
