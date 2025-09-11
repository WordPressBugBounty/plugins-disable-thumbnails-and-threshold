<?php
/*
Option Threshold & EXIF
Plugin: Disable Thumbnails, Threshold and Image Options
Since: 0.1
Author: KGM Servizi
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class kgmdisablethreshold {
	private array|false $kgmdisablethreshold_options;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'kgmdisablethreshold_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'kgmdisablethreshold_page_init' ] );
	}

	public function kgmdisablethreshold_add_plugin_page() {
		add_management_page(
			'Image Threshold&EXIF',
			'Image Threshold&EXIF',
			'manage_options',
			'kgmdisablethreshold',
			[ $this, 'kgmdisablethreshold_create_admin_page' ]
		);
	}

	public function kgmdisablethreshold_create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		// Use cached options to avoid database calls
		$this->kgmdisablethreshold_options = $GLOBALS['kgmdisablethreshold_options'] ?? get_option( KGM_THRESHOLD_OPTION ); ?>

		<div class="wrap">
			<h2>Disable Threshold&EXIF</h2>
			<p><strong>Remember you need to regenerate thumbnails for delete old thumbnails image already generated.</strong></p>
			<p>Plugin recommended for regenerate thumbnails -> <a href="https://uskgm.it/reg-thumb" target="_blank">Regenerate Thumbnails</a></p>
			<p>WP-CLI media regeneration -> <a href="https://uskgm.it/WP-CLI-thumb-rgnrt" target="_blank">Documentation</a></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmdisablethreshold_option_group' );
					do_settings_sections( 'kgmdisablethreshold-admin' );
					wp_nonce_field( 'ts_save_settings', 'kgmdttio_nonce' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function kgmdisablethreshold_page_init() {
		register_setting(
			'kgmdisablethreshold_option_group',
			KGM_THRESHOLD_OPTION,
			[ $this, 'kgmdisablethreshold_sanitize' ]
		);

		add_settings_section(
			'kgmdisablethreshold_setting_section',
			'Settings', 
			[ $this, 'kgmdisablethreshold_section_info' ],
			'kgmdisablethreshold-admin' 
		);

		add_settings_field(
			'new_threshold',
			'New Size Threshold <br> <small>Default WordPress: 2560px</small>',
			[ $this, 'new_threshold_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
		add_settings_field(
			'disable_threshold',
			'Disable Threshold',
			[ $this, 'disable_threshold_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
		add_settings_field(
			'disable_image_rotation_exif',
			'Disable Image Rotation by EXIF',
			[ $this, 'disable_image_rotation_exif_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
	}

	public function kgmdisablethreshold_sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['kgmdisablethreshold_options'] ?? get_option( KGM_THRESHOLD_OPTION, [] );
		}
		
		$sanitary_values = [];
		$valid           = true;

		if ( isset( $_POST['kgmdttio_nonce'] ) && wp_verify_nonce( $_POST['kgmdttio_nonce'], 'ts_save_settings' ) ) {
			if ( isset( $input['new_threshold'] ) ) {
				$threshold = sanitize_text_field($input['new_threshold']);
				// Validate that threshold is a positive number
				if ( is_numeric($threshold) && ($threshold_int = intval($threshold)) > 0 ) {
					$sanitary_values['new_threshold'] = $threshold_int;
				} else {
					$valid = false;
					add_settings_error( 'kgmdisablethreshold_option_notice', 'invalid_threshold', 'Threshold must be a positive number.' );
				}
			}
			if ( isset( $input['disable_threshold'] ) ) {
				$disable_threshold = sanitize_text_field($input['disable_threshold']);
				// Validate checkbox value - only accept expected values
				if ( $disable_threshold === 'disable_threshold' ) {
					$sanitary_values['disable_threshold'] = $disable_threshold;
				}
			}
			if ( isset( $input['disable_image_rotation_exif'] ) ) {
				$disable_exif = sanitize_text_field($input['disable_image_rotation_exif']);
				// Validate checkbox value - only accept expected values
				if ( $disable_exif === 'disable_image_rotation_exif' ) {
					$sanitary_values['disable_image_rotation_exif'] = $disable_exif;
				}
			}
		} else {
			$valid = false;
			add_settings_error( 'kgmdisablethreshold_option_notice', 'nonce_error', 'Nonce validation error.' );
		}

		if ( ! $valid ) {
			$sanitary_values = $GLOBALS['kgmdisablethreshold_options'] ?? get_option( KGM_THRESHOLD_OPTION );
		}
		
		return $sanitary_values;
	}

	public function kgmdisablethreshold_section_info() {
		echo '<p>Configure image size threshold and EXIF rotation settings. The threshold determines when WordPress creates additional image sizes for large images.</p>';
	}

	public function new_threshold_callback() {
		printf(
			'<input class="regular-text" type="number" step="1" min="0" name="%s[new_threshold]" id="new_threshold" value="%s"> <span class="description">px</span>',
			esc_attr( KGM_THRESHOLD_OPTION ),
			( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['new_threshold'] ) ) ? esc_attr( $this->kgmdisablethreshold_options['new_threshold']) : ''
		);
		
		// Check if plugin disables threshold
		$plugin_disables_threshold = false;
		if ( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['disable_threshold'] ) ) {
			$plugin_disables_threshold = ( $this->kgmdisablethreshold_options['disable_threshold'] === 'disable_threshold' );
		}
		
		// Show yellow warning message if plugin disables threshold
		if ( $plugin_disables_threshold ) {
			printf( '<br><small class="description" style="color: #dba617;">%s Disabled by plugin</small>', 
				esc_html( '⚠️' ) 
			);
		} else {
			// Debug: Show current WordPress big image threshold and check for overrides
			$current_wp_threshold = apply_filters( 'big_image_size_threshold', 2560 );
			$plugin_threshold = null;
			if ( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['new_threshold'] ) ) {
				$plugin_threshold = intval( $this->kgmdisablethreshold_options['new_threshold'] );
			}
			
			// Only show debug if plugin value differs from current WordPress value
			if ( $plugin_threshold && $plugin_threshold !== $current_wp_threshold ) {
				printf( '<br><small class="description" style="color: #d63638;">%s Plugin threshold (%dpx) is being overridden by external settings (current: %dpx)</small>', 
					esc_html( '⚠️' ), 
					esc_html( $plugin_threshold ), 
					esc_html( $current_wp_threshold ) 
				);
			} else {
				printf( '<br><small class="description" style="color: #666;">Current WordPress big image threshold: %dpx</small>', esc_html( $current_wp_threshold ) );
			}
		}
	}

	public function disable_threshold_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[disable_threshold]" id="disable_threshold" value="disable_threshold" %s><span class="slider"></span></label>',
			esc_attr( KGM_THRESHOLD_OPTION ),
			( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['disable_threshold'] ) && $this->kgmdisablethreshold_options['disable_threshold'] === 'disable_threshold' ) ? 'checked' : ''
		);
		
		// Debug: Check if disable_threshold setting is being overridden
		$plugin_intends_to_disable = false;
		if ( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['disable_threshold'] ) ) {
			$plugin_intends_to_disable = ( $this->kgmdisablethreshold_options['disable_threshold'] === 'disable_threshold' );
		}
		
		// Get the actual current state of the big_image_size_threshold filter
		$current_wp_threshold_value = apply_filters( 'big_image_size_threshold', 2560 ); // Default is 2560, false if disabled
		
		// Only show debug if plugin setting differs from current WordPress state
		if ( $plugin_intends_to_disable && $current_wp_threshold_value !== false ) {
			// Plugin wants to disable, but it's not disabled (it's a number)
			printf( '<br><small class="description" style="color: #d63638;">%s Plugin intends to disable threshold, but it is currently active (current: %s). May be overridden by external settings.</small>', 
				esc_html( '⚠️' ), 
				esc_html( $current_wp_threshold_value === false ? 'disabled' : $current_wp_threshold_value . 'px' ) 
			);
		} elseif ( ! $plugin_intends_to_disable && $current_wp_threshold_value === false ) {
			// Plugin does NOT want to disable, but it IS disabled
			printf( '<br><small class="description" style="color: #d63638;">%s Threshold is currently disabled by external settings, overriding plugin\'s intention.</small>', 
				esc_html( '⚠️' ) 
			);
		}
	}

	public function disable_image_rotation_exif_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[disable_image_rotation_exif]" id="disable_image_rotation_exif" value="disable_image_rotation_exif" %s><span class="slider"></span></label>',
			esc_attr( KGM_THRESHOLD_OPTION ),
			( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['disable_image_rotation_exif'] ) && $this->kgmdisablethreshold_options['disable_image_rotation_exif'] === 'disable_image_rotation_exif' ) ? 'checked' : ''
		);
		
		// Debug: Check if EXIF rotation is being overridden by external filters
		$plugin_exif_disabled = false;
		if ( is_array( $this->kgmdisablethreshold_options ) && isset( $this->kgmdisablethreshold_options['disable_image_rotation_exif'] ) ) {
			$plugin_exif_disabled = ( $this->kgmdisablethreshold_options['disable_image_rotation_exif'] === 'disable_image_rotation_exif' );
		}
		
		// Get the actual current state of the wp_image_maybe_exif_rotate filter
		// The filter passes $rotate (default 1), $file, $info. We only care about $rotate.
		$current_wp_exif_rotate_state = apply_filters( 'wp_image_maybe_exif_rotate', 1, null, null ); // Default is 1 (true), 0 if disabled
		
		// Only show debug if plugin setting differs from current WordPress state
		if ( $plugin_exif_disabled && $current_wp_exif_rotate_state !== 0 ) {
			// Plugin wants to disable (0), but it's not disabled (it's 1 or something else)
			printf( '<br><small class="description" style="color: #d63638;">%s Plugin intends to disable EXIF rotation, but it is currently active. May be overridden by external settings.</small>', 
				esc_html( '⚠️' ) 
			);
		} elseif ( ! $plugin_exif_disabled && $current_wp_exif_rotate_state === 0 ) {
			// Plugin does NOT want to disable, but it IS disabled
			printf( '<br><small class="description" style="color: #d63638;">%s EXIF rotation is currently disabled by external settings, overriding plugin\'s intention.</small>', 
				esc_html( '⚠️' ) 
			);
		}
	}

}
if ( is_admin() )
	$kgmdisablethreshold = new kgmdisablethreshold();
