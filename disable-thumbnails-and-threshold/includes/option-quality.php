<?php
/**
 * Option Quality
 *
 * Handles the Image Quality admin settings page.
 *
 * @package Disable_Thumbnails_Threshold
 * @since   0.2
 * @author  KGM Servizi
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DTAT_Img_Quality {
	private array|false $dtat_imgquality_options;

	/**
	 * Constructor. Register admin hooks.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'page_init' ] );
	}

	/**
	 * Register the plugin admin menu page under Tools.
	 *
	 * @since  0.2
	 * @return void
	 */
	public function add_plugin_page() {
		add_management_page(
			esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ),
			esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ),
			'manage_options',
			'kgmimgquality',
			[ $this, 'create_admin_page' ]
		);
	}

	/**
	 * Render the Image Quality admin settings page.
	 *
	 * @since  0.2
	 * @return void
	 */
	public function create_admin_page() {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'disable-thumbnails-and-threshold' ) );
		}
		
		// Use cached options to avoid database calls
		$this->dtat_imgquality_options = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION ); ?>

		<div class="wrap">
			<h2><?php echo esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Remember you need to regenerate thumbnails for delete old thumbnails image already generated.', 'disable-thumbnails-and-threshold' ); ?></strong></p>
			<p><?php echo esc_html__( 'Plugin recommended for regenerate thumbnails', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/reg-thumb' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Regenerate Thumbnails', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'WP-CLI media regeneration', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/WP-CLI-thumb-rgnrt' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Documentation', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmimgquality_option_group' );
					do_settings_sections( 'kgmimgquality-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	/**
	 * Register settings, sections, and fields.
	 *
	 * @since  0.2
	 * @return void
	 */
	public function page_init() {
		register_setting(
			'kgmimgquality_option_group',
			DTAT_QUALITY_OPTION,
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'kgmimgquality_setting_section',
			esc_html__( 'Settings', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'section_info' ],
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

	/**
	 * Sanitize and validate form input before saving.
	 *
	 * @since  0.2
	 * @param  array $input Raw form input values.
	 * @return array Sanitized values.
	 */
	public function sanitize( array $input ): array {
		// Check user capabilities for security
		if ( ! current_user_can( 'manage_options' ) ) {
			return $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION, [] );
		}
		
		// Nonce is already verified by WordPress options.php handler via settings_fields().
		$sanitary_values = [];

		if ( isset( $input['jpeg_quality'] ) ) {
			$quality = sanitize_text_field( $input['jpeg_quality'] );
			$quality_int = intval( $quality );
			if ( is_numeric( $quality ) && $quality_int >= 1 && $quality_int <= 100 ) {
				$sanitary_values['jpeg_quality'] = $quality_int;
			} else {
				add_settings_error( 'kgmimgquality_option_notice', 'invalid_quality', esc_html__( 'JPEG quality must be a number between 1 and 100.', 'disable-thumbnails-and-threshold' ) );
				$existing = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION, [] );
				if ( is_array( $existing ) && isset( $existing['jpeg_quality'] ) ) {
					return [ 'jpeg_quality' => absint( $existing['jpeg_quality'] ) ];
				}
				return [];
			}
		}

		return $sanitary_values;
	}

	/**
	 * Output the settings section description.
	 *
	 * @since  0.2
	 * @return void
	 */
	public function section_info() {
		echo '<p>' . esc_html__( 'Configure JPEG compression quality (1-100). Higher values produce better quality but larger file sizes.', 'disable-thumbnails-and-threshold' ) . '</p>';
	}

	/**
	 * Render the JPEG quality number input field.
	 *
	 * @since  0.2
	 * @return void
	 */
	public function jpeg_quality_callback() {
		printf(
			'<input class="regular-text" type="number" step="1" min="1" max="100" name="%s[jpeg_quality]" id="jpeg_quality" value="%s"> <span class="description">%s</span>',
			esc_attr( DTAT_QUALITY_OPTION ),
			( is_array( $this->dtat_imgquality_options ) && isset( $this->dtat_imgquality_options['jpeg_quality'] ) ) ? esc_attr( $this->dtat_imgquality_options['jpeg_quality'] ) : '',
			esc_html__( '%', 'disable-thumbnails-and-threshold' )
		);
		
		// Compare plugin value against WordPress default.
		// We avoid calling apply_filters() here because the jpeg_quality filter expects
		// a $context argument that is unavailable outside of image processing.
		$wp_default_quality = 82;
		$plugin_quality     = null;
		if ( is_array( $this->dtat_imgquality_options ) && isset( $this->dtat_imgquality_options['jpeg_quality'] ) ) {
			$plugin_quality = intval( $this->dtat_imgquality_options['jpeg_quality'] );
		}

		if ( $plugin_quality && $plugin_quality !== $wp_default_quality ) {
			printf( '<br><small class="description dtat-notice-warning">%s %s</small>',
				esc_html( '⚠️' ),
				// translators: %1$d: Plugin quality percentage, %2$d: WordPress default quality percentage
				esc_html( sprintf( __( 'Plugin quality (%1$d%%) differs from WordPress default (%2$d%%)', 'disable-thumbnails-and-threshold' ), $plugin_quality, $wp_default_quality ) )
			);
		} else {
			printf( '<br><small class="description dtat-notice-info">%s</small>',
				// translators: %d: WordPress default JPEG quality percentage
				esc_html( sprintf( __( 'WordPress default JPEG quality: %d%%', 'disable-thumbnails-and-threshold' ), $wp_default_quality ) )
			);
		}
	}

}
if ( is_admin() ) {
	$DTAT_Img_Quality = new DTAT_Img_Quality();
}
