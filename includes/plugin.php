<?php
/**
 * Carta Organisasi Masjid — main code (loaded by carta-organisasi.php after the duplicate-copy guard).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Capability needed to edit the chart. Default: Administrator + Editor. */
function pc_cap() { return apply_filters( 'pc_capability', 'edit_pages' ); }

require_once __DIR__ . '/class-github-updater.php';
if ( is_admin() || wp_doing_cron() ) {
	new PC_GitHub_Updater( PC_FILE, PC_REPO, PC_VER );
}

/* ------------------------------------------------------------------ *
 * Data
 * ------------------------------------------------------------------ */

function pc_default() {
	return array(
		'tajuk'       => 'Carta Organisasi ' . get_bloginfo( 'name' ),
		'nota'        => '',
		'warna_utama' => '#3f6b2a',
		'warna_aksen' => '#7cbf3f',
		'tiers'       => array(),
	);
}

/** Starter structure for a typical Malaysian masjid committee (names left blank). */
function pc_template() {
	$m = function ( $j ) { return array( 'jawatan' => $j, 'keterangan' => '', 'nama' => '', 'img_id' => 0, 'img_url' => '' ); };
	return array_merge( pc_default(), array(
		'tiers' => array(
			array( 'label' => 'Penasihat', 'sorot' => true, 'members' => array( $m( 'Penasihat Masjid' ) ) ),
			array( 'label' => 'Nazir', 'sorot' => true, 'members' => array( $m( 'Nazir' ) ) ),
			array( 'label' => 'Pegawai Utama', 'sorot' => false, 'members' => array( $m( 'Timbalan Nazir' ), $m( 'Setiausaha' ), $m( 'Bendahari' ) ) ),
			array( 'label' => 'Pegawai Masjid', 'sorot' => false, 'members' => array( $m( 'Imam 1' ), $m( 'Imam 2' ), $m( 'Bilal' ), $m( 'Siak' ) ) ),
			array( 'label' => 'Biro-biro', 'sorot' => false, 'members' => array(
				$m( 'Biro Pentadbiran' ), $m( 'Biro Kewangan' ), $m( 'Biro Dakwah' ), $m( 'Biro Pendidikan' ),
				$m( 'Biro Belia' ), $m( 'Biro Kebajikan & Khairat' ), $m( 'Biro Pembangunan' ), $m( 'Biro Muslimat' ),
			) ),
		),
	) );
}

function pc_get() {
	$d = get_option( PC_OPT );
	if ( ! is_array( $d ) ) $d = pc_default();
	return wp_parse_args( $d, pc_default() );
}

/** Clean untrusted input (array decoded from the editor's JSON). */
function pc_sanitize( $in ) {
	$out = pc_default();
	$out['tajuk'] = sanitize_text_field( $in['tajuk'] ?? '' );
	$out['nota']  = sanitize_textarea_field( $in['nota'] ?? '' );
	$out['warna_utama'] = sanitize_hex_color( $in['warna_utama'] ?? '' ) ?: $out['warna_utama'];
	$out['warna_aksen'] = sanitize_hex_color( $in['warna_aksen'] ?? '' ) ?: $out['warna_aksen'];
	$tiers = is_array( $in['tiers'] ?? null ) ? $in['tiers'] : array();
	foreach ( array_slice( $tiers, 0, 20 ) as $t ) {
		if ( ! is_array( $t ) ) continue;
		$tier = array(
			'label'    => sanitize_text_field( $t['label'] ?? '' ),
			'sorot'    => ! empty( $t['sorot'] ),
			'members'  => array(),
		);
		$ms = is_array( $t['members'] ?? null ) ? $t['members'] : array();
		foreach ( array_slice( $ms, 0, 40 ) as $m ) {
			if ( ! is_array( $m ) ) continue;
			$row = array(
				'jawatan'    => sanitize_text_field( $m['jawatan'] ?? '' ),
				'keterangan' => sanitize_text_field( $m['keterangan'] ?? '' ),
				'nama'       => sanitize_text_field( $m['nama'] ?? '' ),
				'img_id'     => absint( $m['img_id'] ?? 0 ),
				'img_url'    => esc_url_raw( $m['img_url'] ?? '' ),
			);
			if ( $row['img_id'] && ! wp_attachment_is_image( $row['img_id'] ) ) $row['img_id'] = 0;
			if ( $row['img_id'] ) $row['img_url'] = '';
			if ( $row['jawatan'] === '' && $row['nama'] === '' && ! $row['img_id'] && ! $row['img_url'] ) continue;
			$tier['members'][] = $row;
		}
		if ( $tier['members'] || $tier['label'] !== '' ) $out['tiers'][] = $tier;
	}
	return $out;
}

function pc_save( $data ) {
	$old = get_option( PC_OPT );
	if ( is_array( $old ) ) {
		$h = get_option( PC_HIST, array() );
		if ( ! is_array( $h ) ) $h = array();
		array_unshift( $h, array(
			'masa' => current_time( 'mysql' ),
			'oleh' => wp_get_current_user()->display_name ?: 'sistem',
			'data' => $old,
		) );
		update_option( PC_HIST, array_slice( $h, 0, 15 ), false );
	}
	update_option( PC_OPT, $data, false );
}

function pc_img_src( $m, $size = 'medium' ) {
	if ( ! empty( $m['img_id'] ) ) {
		$u = wp_get_attachment_image_url( $m['img_id'], $size );
		if ( $u ) return $u;
	}
	return $m['img_url'] ?? '';
}

function pc_admin_url( $args = array() ) { return add_query_arg( $args, admin_url( 'admin.php?page=' . PC_SLUG ) ); }

/** Seed the starter template on first activation (never overwrites existing data). */
register_activation_hook( PC_FILE, function () {
	if ( ! is_array( get_option( PC_OPT ) ) ) {
		add_option( PC_OPT, pc_sanitize( pc_template() ), '', false );
	}
} );

/* ------------------------------------------------------------------ *
 * Front end — shortcode [carta_organisasi]
 * ------------------------------------------------------------------ */

function pc_register_style() {
	if ( ! wp_style_is( 'carta-organisasi', 'registered' ) ) {
		wp_register_style( 'carta-organisasi', PC_URL . 'assets/carta.css', array(), PC_VER );
	}
}
add_action( 'wp_enqueue_scripts', 'pc_register_style' );

add_shortcode( 'carta_organisasi', 'pc_render' );

/** Inline CSS variables for the chosen colours. */
function pc_style_attr( $d ) {
	return sprintf( '--pc-green:%s;--pc-leaf:%s;', esc_attr( $d['warna_utama'] ), esc_attr( $d['warna_aksen'] ) );
}

function pc_render( $atts = array() ) {
	$d = pc_get();
	pc_register_style(); // block themes render shortcodes before wp_enqueue_scripts
	wp_enqueue_style( 'carta-organisasi' );
	if ( ! $d['tiers'] ) {
		return current_user_can( pc_cap() )
			? '<p class="pc-kosong"><em>Carta organisasi belum diisi. <a href="' . esc_url( pc_admin_url() ) . '">Kemas kini di sini</a>.</em></p>'
			: '';
	}
	ob_start();
	echo '<div class="pc-carta" style="' . pc_style_attr( $d ) . '">';
	if ( $d['tajuk'] !== '' ) echo '<p class="pc-tajuk">' . esc_html( $d['tajuk'] ) . '</p>';
	$first = true;
	foreach ( $d['tiers'] as $t ) {
		if ( ! $t['members'] ) continue;
		$has_photo = false;
		foreach ( $t['members'] as $m ) if ( pc_img_src( $m ) ) { $has_photo = true; break; }
		$cls = 'pc-tier' . ( $t['sorot'] ? ' pc-sorot' : '' ) . ( $has_photo ? ' pc-photos' : '' );
		if ( ! $first ) echo '<div class="pc-line" aria-hidden="true"></div>';
		$first = false;
		echo '<div class="' . esc_attr( $cls ) . '"' . ( $t['label'] !== '' ? ' role="group" aria-label="' . esc_attr( $t['label'] ) . '"' : '' ) . '>';
		foreach ( $t['members'] as $m ) {
			$src = pc_img_src( $m );
			echo '<div class="pc-node">';
			if ( $has_photo ) {
				if ( $src ) {
					echo '<img class="pc-foto" src="' . esc_url( $src ) . '" alt="' . esc_attr( $m['nama'] ?: $m['jawatan'] ) . '" loading="lazy" decoding="async">';
				} else {
					echo '<span class="pc-foto pc-foto-kosong" aria-hidden="true"><svg viewBox="0 0 24 24" width="40" height="40"><path fill="currentColor" d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg></span>';
				}
			}
			echo '<div class="pc-jawatan">' . esc_html( $m['jawatan'] ) . '</div>';
			if ( $m['keterangan'] !== '' ) echo '<div class="pc-ket">' . esc_html( $m['keterangan'] ) . '</div>';
			echo '<div class="pc-nama">' . ( $m['nama'] !== '' ? esc_html( $m['nama'] ) : '<em class="pc-placeholder">[Nama]</em>' ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
	}
	if ( $d['nota'] !== '' ) echo '<p class="pc-nota">' . nl2br( esc_html( $d['nota'] ) ) . '</p>';
	if ( current_user_can( pc_cap() ) ) {
		echo '<p class="pc-edit"><a href="' . esc_url( pc_admin_url() ) . '">✎ Kemas kini carta</a></p>';
	}
	echo '</div>';
	return ob_get_clean();
}

/* ------------------------------------------------------------------ *
 * Admin
 * ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_menu_page( 'Carta Organisasi', 'Carta Organisasi', pc_cap(), PC_SLUG, 'pc_admin_page', 'dashicons-networking', 21 );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'toplevel_page_' . PC_SLUG ) return;
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style( 'carta-organisasi-admin', PC_URL . 'assets/admin.css', array(), PC_VER );
	wp_enqueue_style( 'carta-organisasi', PC_URL . 'assets/carta.css', array(), PC_VER );
	wp_enqueue_script( 'carta-organisasi-admin', PC_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), PC_VER, true );
} );

add_action( 'admin_post_pc_simpan', function () {
	if ( ! current_user_can( pc_cap() ) ) wp_die( 'Tiada kebenaran.' );
	check_admin_referer( 'pc_simpan' );
	$data = json_decode( wp_unslash( $_POST['pc_json'] ?? '' ), true );
	if ( ! is_array( $data ) ) {
		wp_safe_redirect( pc_admin_url( array( 'pc' => 'ralat' ) ) );
		exit;
	}
	pc_save( pc_sanitize( $data ) );
	wp_safe_redirect( pc_admin_url( array( 'pc' => 'disimpan' ) ) );
	exit;
} );

add_action( 'admin_post_pc_pulih', function () {
	if ( ! current_user_can( pc_cap() ) ) wp_die( 'Tiada kebenaran.' );
	check_admin_referer( 'pc_pulih' );
	$i = absint( $_POST['idx'] ?? 999 );
	$h = get_option( PC_HIST, array() );
	if ( isset( $h[ $i ]['data'] ) ) {
		pc_save( pc_sanitize( $h[ $i ]['data'] ) );
		wp_safe_redirect( pc_admin_url( array( 'pc' => 'dipulih' ) ) );
	} else {
		wp_safe_redirect( pc_admin_url( array( 'pc' => 'ralat' ) ) );
	}
	exit;
} );

/** Load the starter template (current chart goes into history first). */
add_action( 'admin_post_pc_templat', function () {
	if ( ! current_user_can( pc_cap() ) ) wp_die( 'Tiada kebenaran.' );
	check_admin_referer( 'pc_templat' );
	$cur = pc_get();
	$tpl = pc_template();
	$tpl['tajuk'] = $cur['tajuk'] ?: $tpl['tajuk'];
	$tpl['warna_utama'] = $cur['warna_utama'];
	$tpl['warna_aksen'] = $cur['warna_aksen'];
	pc_save( pc_sanitize( $tpl ) );
	wp_safe_redirect( pc_admin_url( array( 'pc' => 'templat' ) ) );
	exit;
} );

/** Create a published page containing the shortcode. */
add_action( 'admin_post_pc_cipta_halaman', function () {
	if ( ! current_user_can( 'publish_pages' ) ) wp_die( 'Tiada kebenaran.' );
	check_admin_referer( 'pc_cipta_halaman' );
	if ( ! pc_page_id() ) {
		wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Carta Organisasi',
			'post_content' => '[carta_organisasi]',
		) );
	}
	wp_safe_redirect( pc_admin_url( array( 'pc' => 'halaman' ) ) );
	exit;
} );

/** Published page that shows the chart. */
function pc_page_id() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type='page' AND post_status='publish' AND post_content LIKE %s LIMIT 1",
		'%' . $wpdb->esc_like( '[carta_organisasi' ) . '%'
	) );
}

function pc_admin_page() {
	$d = pc_get();
	// Attach thumbnail URLs for the editor UI.
	foreach ( $d['tiers'] as &$t ) foreach ( $t['members'] as &$m ) $m['thumb'] = pc_img_src( $m, 'thumbnail' );
	unset( $t, $m );
	$hist = get_option( PC_HIST, array() );
	$msg  = sanitize_key( $_GET['pc'] ?? '' );
	$pid  = pc_page_id();
	$post = function ( $action, $label, $class = 'button', $confirm = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pc-inline"' . ( $confirm ? ' onsubmit="return confirm(\'' . esc_js( $confirm ) . '\');"' : '' ) . '>';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
		wp_nonce_field( $action );
		echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	};
	?>
	<div class="wrap pc-admin">
		<h1 class="wp-heading-inline">Carta Organisasi</h1>
		<?php if ( $pid ) : ?><a class="page-title-action" href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank">Lihat halaman ↗</a><?php endif; ?>
		<hr class="wp-header-end">
		<?php if ( $msg === 'disimpan' ) : ?><div class="notice notice-success is-dismissible"><p>Carta organisasi disimpan.</p></div><?php endif; ?>
		<?php if ( $msg === 'dipulih' ) : ?><div class="notice notice-success is-dismissible"><p>Versi lama telah dipulihkan.</p></div><?php endif; ?>
		<?php if ( $msg === 'templat' ) : ?><div class="notice notice-success is-dismissible"><p>Templat asas dimuatkan. Carta sebelum ini disimpan dalam Sejarah perubahan.</p></div><?php endif; ?>
		<?php if ( $msg === 'halaman' ) : ?><div class="notice notice-success is-dismissible"><p>Halaman "Carta Organisasi" telah dicipta. Tambahkannya ke menu di Penampilan → Menu.</p></div><?php endif; ?>
		<?php if ( $msg === 'ralat' ) : ?><div class="notice notice-error"><p>Ralat — tiada perubahan disimpan. Cuba lagi.</p></div><?php endif; ?>

		<?php if ( ! $pid ) : ?>
			<div class="notice notice-info pc-setup"><p>Belum ada halaman yang memaparkan carta. Tambah shortcode <code>[carta_organisasi]</code> pada mana-mana halaman, atau</p>
			<p><?php $post( 'pc_cipta_halaman', 'Cipta halaman "Carta Organisasi"', 'button button-secondary' ); ?></p></div>
		<?php endif; ?>

		<div class="pc-help">
			<strong>Cara guna:</strong> Setiap <b>Peringkat</b> ialah satu baris dalam carta (atas → bawah). Seret <span class="dashicons dashicons-move"></span> untuk susun semula peringkat atau jawatan (boleh juga seret jawatan ke peringkat lain).
			Biarkan <i>Nama</i> kosong untuk papar <em class="pc-placeholder">[Nama]</em>. Klik <b>Simpan</b> selepas selesai.
		</div>

		<form id="pc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pc_simpan">
			<?php wp_nonce_field( 'pc_simpan' ); ?>
			<input type="hidden" name="pc_json" id="pc-json">

			<table class="form-table pc-meta" role="presentation">
				<tr><th><label for="pc-tajuk">Tajuk / sesi</label></th><td><input type="text" id="pc-tajuk" class="large-text" value="<?php echo esc_attr( $d['tajuk'] ); ?>" placeholder="cth. Jawatankuasa Masjid … Sesi 2026–2028"></td></tr>
				<tr><th><label for="pc-nota">Nota bawah carta <span class="pc-opt">(pilihan)</span></label></th><td><textarea id="pc-nota" class="large-text" rows="2"><?php echo esc_textarea( $d['nota'] ); ?></textarea></td></tr>
				<tr><th>Warna</th><td class="pc-warna">
					<label><input type="color" id="pc-warna-utama" value="<?php echo esc_attr( $d['warna_utama'] ); ?>"> Utama <span class="pc-opt">(garis atas kad)</span></label>
					<label><input type="color" id="pc-warna-aksen" value="<?php echo esc_attr( $d['warna_aksen'] ); ?>"> Aksen <span class="pc-opt">(garisan, bingkai gambar, peringkat diserlahkan)</span></label>
				</td></tr>
			</table>

			<div id="pc-tiers"></div>

			<p class="pc-actions">
				<button type="button" class="button" id="pc-add-tier"><span class="dashicons dashicons-plus-alt2"></span> Tambah peringkat</button>
				<button type="submit" class="button button-primary button-hero" id="pc-save">Simpan carta</button>
				<span class="pc-dirty" hidden>● Ada perubahan belum disimpan</span>
			</p>
		</form>

		<h2>Pratonton</h2>
		<p class="description">Pratonton dikemas kini semasa anda menaip (belum disimpan). Rupa sebenar mungkin sedikit berbeza mengikut tema.</p>
		<div class="pc-preview-wrap"><div id="pc-preview"></div></div>

		<h2>Templat</h2>
		<p class="description">Muatkan struktur asas masjid (Penasihat → Nazir → Pegawai Utama → Imam/Bilal/Siak → Biro) dengan nama kosong. Carta semasa akan disimpan dalam sejarah.</p>
		<?php $post( 'pc_templat', 'Muatkan templat asas', 'button', 'Gantikan carta semasa dengan templat asas? Carta semasa boleh dipulihkan dari Sejarah perubahan.' ); ?>

		<?php if ( $hist ) : ?>
		<h2>Sejarah perubahan</h2>
		<p class="description">15 versi terakhir disimpan secara automatik. Pulihkan jika tersilap padam.</p>
		<table class="widefat striped pc-hist">
			<thead><tr><th>Masa</th><th>Oleh</th><th>Kandungan</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $hist as $i => $h ) :
				$cnt = 0; foreach ( (array) ( $h['data']['tiers'] ?? array() ) as $t ) $cnt += count( (array) ( $t['members'] ?? array() ) ); ?>
				<tr>
					<td><?php echo esc_html( mysql2date( 'j M Y, g:i a', $h['masa'] ) ); ?></td>
					<td><?php echo esc_html( $h['oleh'] ); ?></td>
					<td><?php echo esc_html( count( (array) ( $h['data']['tiers'] ?? array() ) ) . ' peringkat, ' . $cnt . ' jawatan' ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Pulihkan versi ini? Carta semasa akan disimpan dalam sejarah.');">
							<input type="hidden" name="action" value="pc_pulih"><input type="hidden" name="idx" value="<?php echo (int) $i; ?>">
							<?php wp_nonce_field( 'pc_pulih' ); ?>
							<button class="button button-small">Pulihkan</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<script>window.PC_DATA = <?php echo wp_json_encode( $d ); ?>;</script>
	<?php
}

/** Notice on the page editor so nobody edits the chart HTML by hand. */
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || $s->base !== 'post' || $s->post_type !== 'page' ) return;
	$p = get_post( absint( $_GET['post'] ?? 0 ) );
	if ( ! $p || strpos( $p->post_content, '[carta_organisasi' ) === false ) return;
	echo '<div class="notice notice-info"><p><strong>Carta organisasi</strong> pada halaman ini diurus di menu <a href="' . esc_url( pc_admin_url() ) . '">Carta Organisasi</a>. Jangan padam kod <code>[carta_organisasi]</code>.</p></div>';
} );

add_filter( 'plugin_action_links_' . plugin_basename( PC_FILE ), function ( $l ) {
	array_unshift( $l, '<a href="' . esc_url( pc_admin_url() ) . '">Urus carta</a>' );
	return $l;
} );
