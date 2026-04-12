<?php
/**
 * Option Thumbnail
 *
 * Handles the Image Sizes admin settings page.
 *
 * @package Disable_Thumbnails_Threshold
 * @since   0.1
 * @author  KGM Servizi
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DTAT_Disable_Thumbnails {
	private array|false $dtat_disablethumbnails_options;

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
			esc_html__( 'Image Sizes', 'disable-thumbnails-and-threshold' ), 
			esc_html__( 'Image Sizes', 'disable-thumbnails-and-threshold' ), 
			'manage_options', 
			'kgmdisablethumbnails', 
			[ $this, 'create_admin_page' ]
		);
	}

	/**
	 * Render the Image Sizes admin settings page.
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
		$this->dtat_disablethumbnails_options = $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION ); ?>

		<div class="wrap">
			<h2><?php echo esc_html__( 'Image Thumbnails', 'disable-thumbnails-and-threshold' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Remember you need to regenerate thumbnails for delete old thumbnails image already generated.', 'disable-thumbnails-and-threshold' ); ?></strong></p>
			<p><?php echo esc_html__( 'Plugin recommended for regenerate thumbnails', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/reg-thumb' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Regenerate Thumbnails', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'WP-CLI media regeneration', 'disable-thumbnails-and-threshold' ); ?> -> <a href="<?php echo esc_url( 'https://uskgm.it/WP-CLI-thumb-rgnrt' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Documentation', 'disable-thumbnails-and-threshold' ); ?></a></p>
			<p><?php echo esc_html__( 'Flagged image sizes will be disabled.', 'disable-thumbnails-and-threshold' ); ?></p>
			<?php settings_errors(); ?>
			
			<form method="post" action="options.php">
				<?php
					settings_fields( 'kgmdisablethumbnails_option_group' );
					do_settings_sections( 'kgmdisablethumbnails-admin' );
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
			'kgmdisablethumbnails_option_group',
			DTAT_THUMBNAILS_OPTION, 
			[ $this, 'sanitize' ] 
		);

		add_settings_section(
			'kgmdisablethumbnails_setting_section', 
			esc_html__( 'Settings', 'disable-thumbnails-and-threshold' ), 
			[ $this, 'section_info' ],
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
			if ( 1 === $image_size['crop'] ) {
				$crop = esc_html__( 'cropped', 'disable-thumbnails-and-threshold' );
			} else {
				$crop = '';
			}
			add_settings_field(
				$key,
				esc_html( $key ) . '<br><small>(' . esc_attr( $image_size['width'] ) . 'x' . esc_attr( $image_size['height'] ) . ')</small><br><small>' . esc_html( $crop ) . '</small>',
				[ $this, 'ext_callback' ],
				'kgmdisablethumbnails-admin',
				'kgmdisablethumbnails_setting_section',
				[ 'name' => $key ]
			);
		}
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
			return $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION, [] );
		}
		
		// Nonce is already verified by WordPress options.php handler via settings_fields().
		$sanitary_values = [];

		if ( isset( $input['thumbnail'] ) ) {
			$sanitary_values['thumbnail'] = sanitize_text_field( $input['thumbnail'] );
		}
		if ( isset( $input['medium'] ) ) {
			$sanitary_values['medium'] = sanitize_text_field( $input['medium'] );
		}
		if ( isset( $input['medium_large'] ) ) {
			$sanitary_values['medium_large'] = sanitize_text_field( $input['medium_large'] );
		}
		if ( isset( $input['large'] ) ) {
			$sanitary_values['large'] = sanitize_text_field( $input['large'] );
		}

		$image_sizes = wp_get_additional_image_sizes();
		foreach ( $image_sizes as $key => $image_size ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitary_values[ $key ] = sanitize_text_field( $input[ $key ] );
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

	}

	/**
	 * Render the Thumbnail size toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function thumbnail_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[thumbnail]" id="thumbnail" value="thumbnail" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['thumbnail'] ) && $this->dtat_disablethumbnails_options['thumbnail'] === 'thumbnail' ) ? 'checked' : '',
			esc_html__( 'Disable Thumbnail size', 'disable-thumbnails-and-threshold' )
		);
	}

	/**
	 * Render the Medium size toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function medium_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[medium]" id="medium" value="medium" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['medium'] ) && $this->dtat_disablethumbnails_options['medium'] === 'medium' ) ? 'checked' : '',
			esc_html__( 'Disable Medium size', 'disable-thumbnails-and-threshold' )
		);
	}

	/**
	 * Render the Medium Large size toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function medium_large_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[medium_large]" id="medium_large" value="medium_large" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['medium_large'] ) && $this->dtat_disablethumbnails_options['medium_large'] === 'medium_large' ) ? 'checked' : '',
			esc_html__( 'Disable Medium Large size', 'disable-thumbnails-and-threshold' )
		);
	}

	/**
	 * Render the Large size toggle checkbox.
	 *
	 * @since  0.1
	 * @return void
	 */
	public function large_callback() {
		printf(
			'<label class="switch"><input type="checkbox" name="%s[large]" id="large" value="large" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options['large'] ) && $this->dtat_disablethumbnails_options['large'] === 'large' ) ? 'checked' : '',
			esc_html__( 'Disable Large size', 'disable-thumbnails-and-threshold' )
		);
	}

	/**
	 * Render a toggle checkbox for a custom image size.
	 *
	 * @since  0.1
	 * @param  array $args Settings field arguments containing 'name' key.
	 * @return void
	 */
	public function ext_callback( array $args ): void {
		$name = $args['name'] ?? '';
		printf(
			'<label class="switch"><input type="checkbox" name="%s[%s]" id="%s" value="%s" %s><span class="screen-reader-text">%s</span><span class="slider" aria-hidden="true"></span></label>',
			esc_attr( DTAT_THUMBNAILS_OPTION ),
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( $name ),
			( is_array( $this->dtat_disablethumbnails_options ) && isset( $this->dtat_disablethumbnails_options[$name] ) && $this->dtat_disablethumbnails_options[$name] === $name ) ? 'checked' : '',
			/* translators: %s: Image size name */
			esc_html( sprintf( __( 'Disable %s size', 'disable-thumbnails-and-threshold' ), $name ) )
		);
	}

}
if ( is_admin() ) {
	$DTAT_Disable_Thumbnails = new DTAT_Disable_Thumbnails();
}
