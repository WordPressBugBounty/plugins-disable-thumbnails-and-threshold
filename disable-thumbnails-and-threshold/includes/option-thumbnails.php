<?php
/*
Option Thumbnail
Plugin: Disable Thumbnails, Threshold and Image Options
Since: 0.1
Author: KGM Servizi
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class dtat_disable_thumbnails {
	private array|false $dtat_disablethumbnails_options;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'kgmdisablethumbnails_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'kgmdisablethumbnails_page_init' ] );
	}

	public function kgmdisablethumbnails_add_plugin_page() {
		add_management_page(
			esc_html__( 'Image Sizes', 'disable-thumbnails-and-threshold' ), 
			esc_html__( 'Image Sizes', 'disable-thumbnails-and-threshold' ), 
			'manage_options', 
			'kgmdisablethumbnails', 
			[ $this, 'kgmdisablethumbnails_create_admin_page' ]
		);
	}

	public function kgmdisablethumbnails_create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'disable-thumbnails-and-threshold' ) );
		}
		
		// Use cached options to avoid database calls
		$this->dtat_disablethumbnails_options = $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION ); ?>

		<div class="wrap">
			<h2><?php echo esc_html__( 'Image Thumbnails', 'disable-thumbnails-and-threshold' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Remember you need to regenerate thumbnails for delete old thumbnails image already generated.', 'disable-thumbnails-and-threshold' ); ?></strong></p>
			<p><?php echo esc_html__( 'Plugin recommended for regenerate thumbnails', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/reg-thumb' ); ?>" target="_blank"><?php echo esc_html__( 'Regenerate Thumbnails', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'WP-CLI media regeneration', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/WP-CLI-thumb-rgnrt' ); ?>" target="_blank"><?php echo esc_html__( 'Documentation', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'Flagged image sizes will be disabled.', 'disable-thumbnails-and-threshold' ); ?></p>
			<?php settings_errors(); ?>
			
			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmdisablethumbnails_option_group' );
					do_settings_sections( 'kgmdisablethumbnails-admin' );
					wp_nonce_field( 'dt_save_settings', 'kgmdttio_nonce' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function kgmdisablethumbnails_page_init() {
		register_setting(
			'kgmdisablethumbnails_option_group',
			DTAT_THUMBNAILS_OPTION, 
			[ $this, 'kgmdisablethumbnails_sanitize' ] 
		);

		add_settings_section(
			'kgmdisablethumbnails_setting_section', 
			esc_html__( 'Settings', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'kgmdisablethumbnails_section_info' ],
			'kgmdisablethumbnails-admin' 
		);

		add_settings_field(
			'thumbnail',
			esc_html__( 'Thumbnail', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'thumbnail_callback' ],
			'kgmdisablethumbnails-admin', 
			'kgmdisablethumbnails_setting_section' 
		);
		add_settings_field(
			'medium',
			esc_html__( 'Medium', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'medium_callback' ],
			'kgmdisablethumbnails-admin', 
			'kgmdisablethumbnails_setting_section' 
		);
		add_settings_field(
			'medium_large',
			esc_html__( 'Medium Large', 'disable-thumbnails-and-threshold' ),
			[ $this, 'medium_large_callback' ],
			'kgmdisablethumbnails-admin',
			'kgmdisablethumbnails_setting_section' 
		);
		add_settings_field(
			'large', 
			esc_html__( 'Large', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'large_callback' ],
			'kgmdisablethumbnails-admin', 
			'kgmdisablethumbnails_setting_section' 
		);

		$image_sizes = wp_get_additional_image_sizes();
		foreach ( $image_sizes as $key => $image_size ) {
			if ( $image_size['crop'] == 1 ) {
				$crop = esc_html__( 'cropped', 'disable-thumbnails-and-threshold' );
			} else {
				$crop = "";
			}
	        add_settings_field(
				$key, 
				$key . '<br><small>(' . esc_attr( $image_size['width'] ) . 'x' . esc_attr( $image_size['height'] ) . ')</small><br><small>' . esc_attr( $crop ) . '</small>', 
				[ $this, 'ext_callback' ],
				'kgmdisablethumbnails-admin', 
				'kgmdisablethumbnails_setting_section', 
				$name = $key
			);
		}
	}

	public function kgmdisablethumbnails_sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION, [] );
		}
		
		$sanitary_values = [];
		$valid           = true;
		
		if ( ! isset( $_POST['kgmdttio_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kgmdttio_nonce'] ) ), 'dt_save_settings' ) ) {
			$valid = false;
			add_settings_error( 'kgmdisablethumbnails_option_notice', 'nonce_error', esc_html__( 'Nonce validation error.', 'disable-thumbnails-and-threshold' ) );
		} else {
			if ( isset( $input['thumbnail'] ) ) {
				$sanitary_values['thumbnail'] = sanitize_text_field($input['thumbnail']);
			}
			if ( isset( $input['medium'] ) ) {
				$sanitary_values['medium'] = sanitize_text_field($input['medium']);
			}
			if ( isset( $input['medium_large'] ) ) {
				$sanitary_values['medium_large'] = sanitize_text_field($input['medium_large']);
			}
			if ( isset( $input['large'] ) ) {
				$sanitary_values['large'] = sanitize_text_field($input['large']);
			}

			$image_sizes = wp_get_additional_image_sizes();
			foreach ( $image_sizes as $key => $image_size ) {
				if ( isset( $input[$key] ) ) {
					$sanitary_values[$key] = sanitize_text_field($input[$key]);
				}
			}
		}

		if ( ! $valid ) {
			$sanitary_values = $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION );
		}
		
		return $sanitary_values;
	}

	public function kgmdisablethumbnails_section_info() {
		
	}

	public function thumbnail_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[thumbnail]" id="thumbnail" value="thumbnail" %s><span class="slider"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['thumbnail'] ) && $this->dtat_disablethumbnails_options['thumbnail'] === 'thumbnail' ) ? 'checked' : ''
		);
	}

	public function medium_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[medium]" id="medium" value="medium" %s><span class="slider"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['medium'] ) && $this->dtat_disablethumbnails_options['medium'] === 'medium' ) ? 'checked' : ''
		);
	}

	public function medium_large_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[medium_large]" id="medium_large" value="medium_large" %s><span class="slider"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['medium_large'] ) && $this->dtat_disablethumbnails_options['medium_large'] === 'medium_large' ) ? 'checked' : ''
		);
	}

	public function large_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[large]" id="large" value="large" %s><span class="slider"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['large'] ) && $this->dtat_disablethumbnails_options['large'] === 'large' ) ? 'checked' : ''
		);
	}

	public function ext_callback( string $name ): void {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[%s]" id="%s" value="%s" %s><span class="slider"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( $name ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options[$name] ) && $this->dtat_disablethumbnails_options[$name] === $name ) ? 'checked' : ''
		);
	}

}
if ( is_admin() )
	$dtat_disable_thumbnails = new dtat_disable_thumbnails();
