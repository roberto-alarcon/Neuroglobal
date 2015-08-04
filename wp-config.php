<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'BD_dolor_espalda');

/** MySQL database username */
define('DB_USER', 'dbrootespalda');

/** MySQL database password */
define('DB_PASSWORD', 'd0l0rEsp4ld4');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'h3sqS?`-wVZ#W0|Infx!NWJ;V:622X-Tm@^,2grt+t[[WnnHHb; ED`(/H@/w6Yx');
define('SECURE_AUTH_KEY',  '+TRxgr*n+|u<n.5wt~2sEwB^%:XkNaNt6S!58#lVq4T(Au4J-:!}rmS$x0{~}hE?');
define('LOGGED_IN_KEY',    '+;wR7c[+x<64gV,Z=CWL(8[P`KS`U,^0!)QJdAYmMt>+G`/>E+_YN*K=r%_eyS2}');
define('NONCE_KEY',        'voh529f,B-m:aDVQu6Yr}6z6b;Hm|U_}_ebdhH>7B5OLr.?L9!hx.ZmE?!1$Q60.');
define('AUTH_SALT',        'S`|TXBxJq9Q|+=vTcnKb`&Ee,|FJRCWIs7El[~=bZBU-+%r:)hR=on *NFJhUvkb');
define('SECURE_AUTH_SALT', 'Zypok$k7ru;x&c[4Y%K?:<vnCSOzQbwuM~*m#VF2nZ .X]TiQ*5_b{_Ge-gdz@^l');
define('LOGGED_IN_SALT',   'PPbP5~NZMarCSg:R$C*rM^vSjyq_Ds+-p)t6eKBZY%hL;ngd.5~C)m+?PX<Thr[ ');
define('NONCE_SALT',       '-:r`k|2ZHW1 Wp44vc$|)W%6/sU[D5Nwz^bA3N4dflzb,S-[Z7<_}5hOayQyE*0H');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
