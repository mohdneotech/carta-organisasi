<?php
/**
 * Plugin Name:       Carta Organisasi Masjid
 * Plugin URI:        https://github.com/mohdneotech/carta-organisasi
 * Description:       Carta organisasi masjid / surau yang mudah dikemas kini oleh AJK — susun peringkat, jawatan, nama dan gambar dari wp-admin (seret & lepas, pratonton langsung, sejarah versi). Papar dengan shortcode [carta_organisasi].
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Mohd Nordin Hussain
 * Author URI:        https://mohdnordin.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       carta-organisasi
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Another copy (e.g. the original site-specific build) is already loaded — stand down instead of fataling.
if ( defined( 'PC_VER' ) ) {
	register_activation_hook( __FILE__, function () {
		wp_die( 'Sila nyahaktifkan plugin lama (Perepat Carta Organisasi) dahulu, kemudian aktifkan plugin ini. Semua data akan dikekalkan.', 'Plugin lama masih aktif', [ 'back_link' => true ] );
	} );
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>Carta Organisasi Masjid:</strong> satu lagi plugin carta organisasi (fungsi <code>pc_*</code>) sedang aktif. Nyahaktifkan plugin lama dahulu — data carta akan dikekalkan.</p></div>';
	} );
	return;
}

define( 'PC_VER', '1.1.0' );
define( 'PC_FILE', __FILE__ );
define( 'PC_OPT', 'pc_carta' );
define( 'PC_HIST', 'pc_carta_history' );
define( 'PC_URL', plugin_dir_url( __FILE__ ) );
define( 'PC_SLUG', 'carta-organisasi' ); // admin page slug
define( 'PC_REPO', 'mohdneotech/carta-organisasi' );

require_once __DIR__ . '/includes/plugin.php';
