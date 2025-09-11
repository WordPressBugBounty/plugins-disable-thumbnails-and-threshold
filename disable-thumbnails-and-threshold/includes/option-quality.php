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

class kgmimgquality {
	private array|false $kgmimgquality_options;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'kgmimgquality_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'kgmimgquality_page_init' ] );
	}

	public function kgmimgquality_add_plugin_page() {
		add_management_page(
			'Image Quality',
			'Image Quality',
			'manage_options',
			'kgmimgquality',
			[ $this, 'kgmimgquality_create_admin_page' ]
		);
	}

	public function kgmimgquality_create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		// Use cached options to avoid database calls
		$this->kgmimgquality_options = $GLOBALS['kgmimgquality_options'] ?? get_option( KGM_QUALITY_OPTION ); ?>

		<div class="wrap">
			<h2>Image Quality</h2>
			<p><strong>Remember you need to regenerate thumbnails for delete old thumbnails image already generated.</strong></p>
			<p>Plugin recommended for regenerate thumbnails -> <a href="https://uskgm.it/reg-thumb" target="_blank">Regenerate Thumbnails</a></p>
			<p>WP-CLI media regeneration -> <a href="https://uskgm.it/WP-CLI-thumb-rgnrt" target="_blank">Documentation</a></p>
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
			KGM_QUALITY_OPTION,
			[ $this, 'kgmimgquality_sanitize' ]
		);

		add_settings_section(
			'kgmimgquality_setting_section',
			'Settings', 
			[ $this, 'kgmimgquality_section_info' ],
			'kgmimgquality-admin' 
		);

		add_settings_field(
			'jpeg_quality',
			'JPEG Quality <br> <small>Default WordPress: 82%</small>',
			[ $this, 'jpeg_quality_callback' ],
			'kgmimgquality-admin',
			'kgmimgquality_setting_section'
		);
	}

	public function kgmimgquality_sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['kgmimgquality_options'] ?? get_option( KGM_QUALITY_OPTION, [] );
		}
		
		$sanitary_values = [];
		$valid           = true;
		
		if ( isset( $_POST['kgmdttio_nonce'] ) && wp_verify_nonce( $_POST['kgmdttio_nonce'], 'qi_save_settings' ) ) {
			if ( isset( $input['jpeg_quality'] ) ) {
				$quality = sanitize_text_field($input['jpeg_quality']);
				// Validate that quality is a number between 1 and 100
				if ( is_numeric($quality) && ($quality_int = intval($quality)) >= 1 && $quality_int <= 100 ) {
					$sanitary_values['jpeg_quality'] = $quality_int;
				} else {
					$valid = false;
					add_settings_error( 'kgmimgquality_option_notice', 'invalid_quality', 'JPEG quality must be a number between 1 and 100.' );
				}
			}
		} else {
			$valid = false;
			add_settings_error( 'kgmimgquality_option_notice', 'nonce_error', 'Nonce validation error.' );
		}

		if ( ! $valid ) {
			$sanitary_values = $GLOBALS['kgmimgquality_options'] ?? get_option( KGM_QUALITY_OPTION );
		}

		return $sanitary_values;
	}

	public function kgmimgquality_section_info() {
		echo '<p>Configure JPEG compression quality (1-100). Higher values produce better quality but larger file sizes.</p>';
	}

	public function jpeg_quality_callback() {
		printf(
			'<input class="regular-text" type="number" step="1" min="1" max="100" name="%s[jpeg_quality]" id="jpeg_quality" value="%s"> <span class="description">%%</span>',
			KGM_QUALITY_OPTION,
			( is_array( $this->kgmimgquality_options ) && isset( $this->kgmimgquality_options['jpeg_quality'] ) ) ? esc_attr( $this->kgmimgquality_options['jpeg_quality']) : ''
		);
		
		// Debug: Show current WordPress JPEG quality and check for overrides
		$current_wp_quality = apply_filters( 'jpeg_quality', 82 );
		$plugin_quality = null;
		if ( is_array( $this->kgmimgquality_options ) && isset( $this->kgmimgquality_options['jpeg_quality'] ) ) {
			$plugin_quality = intval( $this->kgmimgquality_options['jpeg_quality'] );
		}
		
		// Only show debug if plugin value differs from current WordPress value
		if ( $plugin_quality && $plugin_quality !== $current_wp_quality ) {
			printf( '<br><small class="description" style="color: #d63638;">%s Plugin quality (%d%%) is being overridden by external settings (current: %d%%)</small>', 
				esc_html( '⚠️' ), 
				esc_html( $plugin_quality ), 
				esc_html( $current_wp_quality ) 
			);
		} else {
			printf( '<br><small class="description" style="color: #666;">Current WordPress JPEG quality: %d%%</small>', esc_html( $current_wp_quality ) );
		}
	}

}
if ( is_admin() )
	$kgmimgquality = new kgmimgquality();
