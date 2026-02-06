<?php
/*
Option Quality
Plugin: Disable Thumbnails, Threshold and Image Options
Since: 0.2
Author: KGM Servizi
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class dtat_img_quality {
	private array|false $dtat_imgquality_options;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'kgmimgquality_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'kgmimgquality_page_init' ] );
	}

	public function kgmimgquality_add_plugin_page() {
		add_management_page(
			esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ),
			esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ),
			'manage_options',
			'kgmimgquality',
			[ $this, 'kgmimgquality_create_admin_page' ]
		);
	}

	public function kgmimgquality_create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'disable-thumbnails-and-threshold' ) );
		}
		
		// Use cached options to avoid database calls
		$this->dtat_imgquality_options = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION ); ?>

		<div class="wrap">
			<h2><?php echo esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Remember you need to regenerate thumbnails for delete old thumbnails image already generated.', 'disable-thumbnails-and-threshold' ); ?></strong></p>
			<p><?php echo esc_html__( 'Plugin recommended for regenerate thumbnails', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/reg-thumb' ); ?>" target="_blank"><?php echo esc_html__( 'Regenerate Thumbnails', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'WP-CLI media regeneration', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/WP-CLI-thumb-rgnrt' ); ?>" target="_blank"><?php echo esc_html__( 'Documentation', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmimgquality_option_group' );
					do_settings_sections( 'kgmimgquality-admin' );
					wp_nonce_field( 'qi_save_settings', 'kgmdttio_nonce' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function kgmimgquality_page_init() {
		register_setting(
			'kgmimgquality_option_group',
			DTAT_QUALITY_OPTION,
			[ $this, 'kgmimgquality_sanitize' ]
		);

		add_settings_section(
			'kgmimgquality_setting_section',
			esc_html__( 'Settings', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'kgmimgquality_section_info' ],
			'kgmimgquality-admin' 
		);

		add_settings_field(
			'jpeg_quality',
			esc_html__( 'JPEG Quality', 'disable-thumbnails-and-threshold' ) . ' <br> <small>' . esc_html__( 'Default WordPress: 82%', 'disable-thumbnails-and-threshold' ) . '</small>',
			[ $this, 'jpeg_quality_callback' ],
			'kgmimgquality-admin',
			'kgmimgquality_setting_section'
		);
	}

	public function kgmimgquality_sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION, [] );
		}
		
		$sanitary_values = [];
		$valid           = true;
		
		if ( ! isset( $_POST['kgmdttio_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kgmdttio_nonce'] ) ), 'qi_save_settings' ) ) {
			$valid = false;
			add_settings_error( 'kgmimgquality_option_notice', 'nonce_error', esc_html__( 'Nonce validation error.', 'disable-thumbnails-and-threshold' ) );
		} else {
			if ( isset( $input['jpeg_quality'] ) ) {
				$quality = sanitize_text_field($input['jpeg_quality']);
				// Validate that quality is a number between 1 and 100
				if ( is_numeric($quality) && ($quality_int = intval($quality)) >= 1 && $quality_int <= 100 ) {
					$sanitary_values['jpeg_quality'] = $quality_int;
				} else {
					$valid = false;
					add_settings_error( 'kgmimgquality_option_notice', 'invalid_quality', esc_html__( 'JPEG quality must be a number between 1 and 100.', 'disable-thumbnails-and-threshold' ) );
				}
			}
		}

		if ( ! $valid ) {
			$sanitary_values = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION );
		}

		return $sanitary_values;
	}

	public function kgmimgquality_section_info() {
		echo '<p>' . esc_html__( 'Configure JPEG compression quality (1-100). Higher values produce better quality but larger file sizes.', 'disable-thumbnails-and-threshold' ) . '</p>';
	}

	public function jpeg_quality_callback() {
		printf(
			'<input class="regular-text" type="number" step="1" min="1" max="100" name="%s[jpeg_quality]" id="jpeg_quality" value="%s"> <span class="description">%s</span>',
			esc_attr( DTAT_QUALITY_OPTION ),
			( is_array( $this->dtat_imgquality_options ) && isset( $this->dtat_imgquality_options['jpeg_quality'] ) ) ? esc_attr( $this->dtat_imgquality_options['jpeg_quality']) : '',
			esc_html__( '%', 'disable-thumbnails-and-threshold' )
		);
		
		// Debug: Show current WordPress JPEG quality and check for overrides
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook
		$current_wp_quality = apply_filters( 'jpeg_quality', 82 );
		$plugin_quality = null;
		if ( is_array( $this->dtat_imgquality_options ) && isset( $this->dtat_imgquality_options['jpeg_quality'] ) ) {
			$plugin_quality = intval( $this->dtat_imgquality_options['jpeg_quality'] );
		}
		
		// Only show debug if plugin value differs from current WordPress value
		if ( $plugin_quality && $plugin_quality !== $current_wp_quality ) {
			printf( '<br><small class="description" style="color: #d63638;">%s %s</small>', 
				esc_html( '⚠️' ),
				// translators: %1$d: Plugin quality percentage, %2$d: Current WordPress quality percentage
				esc_html( sprintf( __( 'Plugin quality (%1$d%%) is being overridden by external settings (current: %2$d%%)', 'disable-thumbnails-and-threshold' ), $plugin_quality, $current_wp_quality ) )
			);
		} else {
			printf( '<br><small class="description" style="color: #666;">%s</small>', 
				// translators: %d: Current WordPress JPEG quality percentage
				esc_html( sprintf( __( 'Current WordPress JPEG quality: %d%%', 'disable-thumbnails-and-threshold' ), $current_wp_quality ) ) 
			);
		}
	}

}
if ( is_admin() )
	$dtat_img_quality = new dtat_img_quality();
