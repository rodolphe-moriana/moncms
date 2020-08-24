<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'wordpress' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'b@csty-gY2,db3bl3]_]Pz``Kk3BYZDxI@0m=Q<1KpEhc)W^~H<!);)tU#j:[pDO' );
define( 'SECURE_AUTH_KEY',  'cQ!QA=&v|7B^XL8oMnl:0N62Aih9gVWP>kTmv.}TQn`mf|!:>4#x!&A469&?}NH9' );
define( 'LOGGED_IN_KEY',    'X7sSuq*[}J^Rl xS0uR_<x)YnheN^D0@hTC:YLTAXO/DiS}?] =a:foV4q#_5P`?' );
define( 'NONCE_KEY',        '4-uEdR;LlF1;{4quLw29v$Nwuys]e!z;S5{v}P{VmsI=sDrzU-{Ji)A_<uE*r!S`' );
define( 'AUTH_SALT',        'Qn}<(bu,;v00ZLm1KyHmv6TmK@p|^~rsBu>b5UPj8o-T)F*%MfW^w!,WRYsTTC<_' );
define( 'SECURE_AUTH_SALT', 'v:Bk2]uNYqjN/}}+C9|j}:FZ#MnyJA:m;D3Ua8^X<6fjv%F$_)ieqdY9rrHb-y`;' );
define( 'LOGGED_IN_SALT',   '%<MBamxwIXqAwh+[[Ch.,A%LmKEnET^48sK/IM4mOwPgx.{2CiR=wmNNOP*;~8@q' );
define( 'NONCE_SALT',       '.[n9Xq`IX~jg<An}C3u9Q1@UaGjyF E>_=3<M>JK qCzx3{F%iXj53)NI7-XXE,9' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
