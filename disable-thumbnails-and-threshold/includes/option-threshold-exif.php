<?php
/**
 * Option Threshold & EXIF
 *
 * Handles the Image Threshold and EXIF rotation admin settings page.
 *
 * @package Disable_Thumbnails_Threshold
 * @since   0.1
 * @author  KGM Servizi
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DTAT_Disable_Threshold {
	private array|false $dtat_disablethreshold_options;

	/**
	 * Constructor. Register admin hooks.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'page_init' ] );
	}

	/**
	 * Register the plugin admin menu page under Tools.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function add_plugin_page() {
		add_management_page(
			esc_html__( 'Image Threshold&EXIF', 'disable-thumbnails-and-threshold' ),
			esc_html__( 'Image Threshold&EXIF', 'disable-thumbnails-and-threshold' ),
			'manage_options',
			'kgmdisablethreshold',
			[ $this, 'create_admin_page' ]
		);
	}

	/**
	 * Render the Threshold & EXIF admin settings page.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'disable-thumbnails-and-threshold' ) );
		}
		
		// Use cached options to avoid database calls
		$this->dtat_disablethreshold_options = $GLOBALS['dtat_disablethreshold_options'] ?? get_option( DTAT_THRESHOLD_OPTION ); ?>

		<div class="wrap">
			<h2><?php echo esc_html__( 'Disable Threshold&EXIF', 'disable-thumbnails-and-threshold' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Remember you need to regenerate thumbnails for delete old thumbnails image already generated.', 'disable-thumbnails-and-threshold' ); ?></strong></p>
			<p><?php echo esc_html__( 'Plugin recommended for regenerate thumbnails', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/reg-thumb' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Regenerate Thumbnails', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'WP-CLI media regeneration', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/WP-CLI-thumb-rgnrt' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Documentation', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmdisablethreshold_option_group' );
					do_settings_sections( 'kgmdisablethreshold-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	/**
	 * Register settings, sections, and fields.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function page_init() {
		register_setting(
			'kgmdisablethreshold_option_group',
			DTAT_THRESHOLD_OPTION,
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'kgmdisablethreshold_setting_section',
			esc_html__( 'Settings', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'section_info' ],
			'kgmdisablethreshold-admin' 
		);

		add_settings_field(
			'new_threshold',
			esc_html__( 'New Size Threshold', 'disable-thumbnails-and-threshold' ) . ' <br> <small>' . esc_html__( 'Default WordPress: 2560px', 'disable-thumbnails-and-threshold' ) . '</small>',
			[ $this, 'new_threshold_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
		add_settings_field(
			'disable_threshold',
			esc_html__( 'Disable Threshold', 'disable-thumbnails-and-threshold' ),
			[ $this, 'disable_threshold_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
		add_settings_field(
			'disable_image_rotation_exif',
			esc_html__( 'Disable Image Rotation by EXIF', 'disable-thumbnails-and-threshold' ),
			[ $this, 'disable_image_rotation_exif_callback' ],
			'kgmdisablethreshold-admin',
			'kgmdisablethreshold_setting_section'
		);
	}

	/**
	 * Sanitize and validate form input before saving.
	 *
	 * @since  0.1
	 * @param  array $input Raw form input values.
	 * @return array Sanitized values.
	 */
	public function sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['dtat_disablethreshold_options'] ?? get_option( DTAT_THRESHOLD_OPTION, [] );
		}
		
		// Nonce is already verified by WordPress options.php handler via settings_fields().
		$sanitary_values = [];

		if ( isset( $input['new_threshold'] ) ) {
			$threshold     = sanitize_text_field( $input['new_threshold'] );
			$threshold_int = intval( $threshold );
			if ( is_numeric( $threshold ) && $threshold_int > 0 ) {
				$sanitary_values['new_threshold'] = $threshold_int;
			} else {
				add_settings_error( 'kgmdisablethreshold_option_notice', 'invalid_threshold', esc_html__( 'Threshold must be a positive number.', 'disable-thumbnails-and-threshold' ) );
			}
		}
		if ( isset( $input['disable_threshold'] ) ) {
			$disable_threshold = sanitize_text_field( $input['disable_threshold'] );
			if ( 'disable_threshold' === $disable_threshold ) {
				$sanitary_values['disable_threshold'] = $disable_threshold;
			}
		}
		if ( isset( $input['disable_image_rotation_exif'] ) ) {
			$disable_exif = sanitize_text_field( $input['disable_image_rotation_exif'] );
			if ( 'disable_image_rotation_exif' === $disable_exif ) {
				$sanitary_values['disable_image_rotation_exif'] = $disable_exif;
			}
		}

		return $sanitary_values;
	}

	/**
	 * Output the settings section description.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function section_info() {
		echo '<p>' . esc_html__( 'Configure image size threshold and EXIF rotation settings. The threshold determines when WordPress creates additional image sizes for large images.', 'disable-thumbnails-and-threshold' ) . '</p>';
	}

	/**
	 * Render the threshold number input field.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function new_threshold_callback() {
		printf(
			'<input class="regular-text" type="number" step="1" min="1" name="%s[new_threshold]" id="new_threshold" value="%s" aria-describedby="dtat-threshold-desc"> <span class="description" id="dtat-threshold-desc">%s</span>',
			esc_attr( DTAT_THRESHOLD_OPTION ),
			( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['new_threshold'] ) ) ? esc_attr( $this->dtat_disablethreshold_options['new_threshold'] ) : '',
			esc_html__( 'px', 'disable-thumbnails-and-threshold' )
		);
		
		// Check if plugin disables threshold
		$plugin_disables_threshold = false;
		if ( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['disable_threshold'] ) ) {
			$plugin_disables_threshold = ( $this->dtat_disablethreshold_options['disable_threshold'] === 'disable_threshold' );
		}
		
		// Show yellow warning message if plugin disables threshold
		if ( $plugin_disables_threshold ) {
			printf( '<br><small class="description dtat-notice-caution">%s %s</small>', 
				esc_html( '⚠️' ),
				esc_html__( 'Disabled by plugin', 'disable-thumbnails-and-threshold' )
			);
		} else {
			// Compare plugin value against WordPress default.
			// We avoid calling apply_filters() here because big_image_size_threshold expects
			// $imagesize, $file, and $attachment_id arguments unavailable outside image processing.
			$wp_default_threshold = 2560;
			$plugin_threshold     = null;
			if ( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['new_threshold'] ) ) {
				$plugin_threshold = intval( $this->dtat_disablethreshold_options['new_threshold'] );
			}

			if ( $plugin_threshold && $plugin_threshold !== $wp_default_threshold ) {
				printf( '<br><small class="description dtat-notice-warning">%s %s</small>',
					esc_html( '⚠️' ),
					// translators: %1$d: Plugin threshold value in pixels, %2$d: WordPress default threshold value in pixels
					esc_html( sprintf( __( 'Plugin threshold (%1$dpx) differs from WordPress default (%2$dpx)', 'disable-thumbnails-and-threshold' ), $plugin_threshold, $wp_default_threshold ) )
				);
			} else {
				// translators: %d: WordPress default big image threshold value in pixels
				printf( '<br><small class="description dtat-notice-info">%s</small>', esc_html( sprintf( __( 'WordPress default big image threshold: %dpx', 'disable-thumbnails-and-threshold' ), $wp_default_threshold ) ) );
			}
		}
	}

	/**
	 * Render the disable threshold toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function disable_threshold_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[disable_threshold]" id="disable_threshold" value="disable_threshold" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THRESHOLD_OPTION ),
			( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['disable_threshold'] ) && $this->dtat_disablethreshold_options['disable_threshold'] === 'disable_threshold' ) ? 'checked' : '',
			esc_html__( 'Disable Threshold', 'disable-thumbnails-and-threshold' )
		);
		
		// Show informational status based on plugin's stored setting.
		// We avoid calling apply_filters('big_image_size_threshold') here because it expects
		// $imagesize, $file, and $attachment_id arguments unavailable outside image processing.
		$plugin_intends_to_disable = false;
		if ( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['disable_threshold'] ) ) {
			$plugin_intends_to_disable = ( $this->dtat_disablethreshold_options['disable_threshold'] === 'disable_threshold' );
		}

		if ( $plugin_intends_to_disable ) {
			printf( '<br><small class="description dtat-notice-info">%s</small>',
				esc_html__( 'Threshold is disabled by this plugin.', 'disable-thumbnails-and-threshold' )
			);
		}
	}

	/**
	 * Render the disable EXIF rotation toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function disable_image_rotation_exif_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[disable_image_rotation_exif]" id="disable_image_rotation_exif" value="disable_image_rotation_exif" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THRESHOLD_OPTION ),
			( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['disable_image_rotation_exif'] ) && $this->dtat_disablethreshold_options['disable_image_rotation_exif'] === 'disable_image_rotation_exif' ) ? 'checked' : '',
			esc_html__( 'Disable Image Rotation by EXIF', 'disable-thumbnails-and-threshold' )
		);
		
		// Show informational status based on plugin's stored setting.
		// We avoid calling apply_filters('wp_image_maybe_exif_rotate') here because it expects
		// a valid $file path argument unavailable outside image processing.
		$plugin_exif_disabled = false;
		if ( is_array( $this->dtat_disablethreshold_options ) && isset( $this->dtat_disablethreshold_options['disable_image_rotation_exif'] ) ) {
			$plugin_exif_disabled = ( $this->dtat_disablethreshold_options['disable_image_rotation_exif'] === 'disable_image_rotation_exif' );
		}

		if ( $plugin_exif_disabled ) {
			printf( '<br><small class="description dtat-notice-info">%s</small>',
				esc_html__( 'EXIF rotation is disabled by this plugin.', 'disable-thumbnails-and-threshold' )
			);
		}
	}

}
if ( is_admin() ) {
	$DTAT_Disable_Threshold = new DTAT_Disable_Threshold();
}
