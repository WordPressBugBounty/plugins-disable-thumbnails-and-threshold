<?php
/* 
Plugin Name: Disable Thumbnails, Threshold and Image Options
Version: 0.6.3
Description: Disable Thumbnails, Threshold and Image Options
Author: KGM Servizi
Author URI: https://kgmservizi.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants for better maintainability
define( 'KGM_QUALITY_OPTION', 'kgmimgquality_option_name' );
define( 'KGM_THRESHOLD_OPTION', 'kgmdisablethreshold_option_name' );
define( 'KGM_THUMBNAILS_OPTION', 'kgmdisablethumbnails_option_name' );

// Check WordPress version compatibility - requires WordPress 5.4+ for PHP 7.4+ support
// Use admin_init hook to ensure WordPress is fully loaded
add_action( 'admin_init', 'kgmdttio_check_wordpress_version' );

if ( is_admin() ) {
	// Use include_once to prevent multiple inclusions and potential fatal errors
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-thumbnails.php');
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-quality.php');
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-threshold-exif.php');
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'kgmimage_action_links' );
	add_action( 'admin_enqueue_scripts', 'kgmimage_admin_styles' );
}

/**
 *  
 * Retrieve options for quality and threshold
 * Cache options globally to avoid multiple database calls
 * 
 */
$GLOBALS['kgmimgquality_options']       = get_option( KGM_QUALITY_OPTION );
$GLOBALS['kgmdisablethreshold_options'] = get_option( KGM_THRESHOLD_OPTION );

// Initialize with current WordPress values if options don't exist
// This will be handled in the admin_init hook to ensure WordPress is fully loaded
add_action( 'admin_init', 'kgmdttio_initialize_options' );

// Hook to apply filters after themes are loaded to ensure priority
add_action( 'after_setup_theme', 'kgmdttio_apply_filters', 20 );

/**
 * Check WordPress version compatibility
 * Called on admin_init to ensure WordPress is fully loaded
 */
function kgmdttio_check_wordpress_version(): void {
	// Only run in admin
	if ( ! is_admin() ) {
		return;
	}
	
	// Check if WordPress version is compatible
	if ( version_compare( get_bloginfo( 'version' ), '5.4', '<' ) ) {
		add_action( 'admin_notices', fn() => 
			print '<div class="notice notice-error"><p><strong>Disable Thumbnails, Threshold and Image Options</strong> requires WordPress 5.4 or higher (PHP 7.4+). Please update WordPress.</p></div>'
		);
	}
}

/**
 * Initialize plugin options with current WordPress values
 * Called on admin_init to ensure WordPress is fully loaded
 */
function kgmdttio_initialize_options(): void {
	// Only run in admin and if user has proper capabilities
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Initialize JPEG quality option if it doesn't exist or is invalid
	if ( ! $GLOBALS['kgmimgquality_options'] || 
		 ! is_array( $GLOBALS['kgmimgquality_options'] ) || 
		 ! isset( $GLOBALS['kgmimgquality_options']['jpeg_quality'] ) ||
		 ! is_numeric( $GLOBALS['kgmimgquality_options']['jpeg_quality'] ) ||
		 intval( $GLOBALS['kgmimgquality_options']['jpeg_quality'] ) < 1 ||
		 intval( $GLOBALS['kgmimgquality_options']['jpeg_quality'] ) > 100 ) {
		
		// Get current WordPress JPEG quality (respects existing filters/plugins)
		$current_quality = apply_filters( 'jpeg_quality', 82 );
		
		// Validate the quality value from filters
		if ( ! is_numeric( $current_quality ) || $current_quality < 1 || $current_quality > 100 ) {
			$current_quality = 82; // Fallback to WordPress default
		}
		
		// Preserve existing options and only update jpeg_quality
		if ( ! is_array( $GLOBALS['kgmimgquality_options'] ) ) {
			$GLOBALS['kgmimgquality_options'] = [];
		}
		$GLOBALS['kgmimgquality_options']['jpeg_quality'] = intval( $current_quality );
		update_option( KGM_QUALITY_OPTION, $GLOBALS['kgmimgquality_options'] );
	}
	
	// Initialize threshold option if it doesn't exist or is invalid
	if ( ! $GLOBALS['kgmdisablethreshold_options'] || 
		 ! is_array( $GLOBALS['kgmdisablethreshold_options'] ) || 
		 ! isset( $GLOBALS['kgmdisablethreshold_options']['new_threshold'] ) ||
		 ! is_numeric( $GLOBALS['kgmdisablethreshold_options']['new_threshold'] ) ||
		 intval( $GLOBALS['kgmdisablethreshold_options']['new_threshold'] ) <= 0 ) {
		
		// Get current WordPress big image threshold (respects existing filters/plugins)
		$current_threshold = apply_filters( 'big_image_size_threshold', 2560 );
		
		// Validate the threshold value from filters
		if ( ! is_numeric( $current_threshold ) || $current_threshold <= 0 ) {
			$current_threshold = 2560; // Fallback to WordPress default
		}
		
		// Preserve existing options and only update new_threshold
		if ( ! is_array( $GLOBALS['kgmdisablethreshold_options'] ) ) {
			$GLOBALS['kgmdisablethreshold_options'] = [];
		}
		$GLOBALS['kgmdisablethreshold_options']['new_threshold'] = intval( $current_threshold );
		update_option( KGM_THRESHOLD_OPTION, $GLOBALS['kgmdisablethreshold_options'] );
	}
}

/**
 * JPEG Quality filter callback
 */
function kgmdttio_jpeg_quality_filter(): int {
	$kgmimgquality_options = $GLOBALS['kgmimgquality_options'] ?? get_option( KGM_QUALITY_OPTION, [] );
	
	if ( ! is_array( $kgmimgquality_options ) || ! isset( $kgmimgquality_options['jpeg_quality'] ) ) {
		return 82; // WordPress default
	}
	
	$quality = intval( $kgmimgquality_options['jpeg_quality'] );
	
	// Validate quality range (0-100)
	if ( $quality < 0 || $quality > 100 ) {
		return 82; // WordPress default
	}
	
	return $quality;
}

/**
 * Big image threshold filter callback
 */
function kgmdttio_big_image_threshold_filter(): int {
	$kgmdisablethreshold_options = $GLOBALS['kgmdisablethreshold_options'] ?? get_option( KGM_THRESHOLD_OPTION, [] );
	
	if ( ! is_array( $kgmdisablethreshold_options ) || ! isset( $kgmdisablethreshold_options['new_threshold'] ) ) {
		return 2560; // WordPress default
	}
	
	$threshold = intval( $kgmdisablethreshold_options['new_threshold'] );
	
	// Validate threshold is positive
	if ( $threshold <= 0 ) {
		return 2560; // WordPress default
	}
	
	return $threshold;
}

/**
 * Thumbnail sizes filter callback
 */
function kgmdttio_thumbnail_sizes_filter( array $sizes ): array {
	$kgmdisablethumbnails_options = $GLOBALS['kgmdisablethumbnails_options'] ?? get_option( KGM_THUMBNAILS_OPTION, [] );
	
	if ( ! is_array( $kgmdisablethumbnails_options ) || empty( $kgmdisablethumbnails_options ) ) {
		return $sizes;
	}
	
	// Validate that all values in the array are strings (security check)
	$valid_options = array_filter( $kgmdisablethumbnails_options, 'is_string' );
	
	return array_diff( $sizes, $valid_options );
}

/**
 * Apply plugin filters after themes are loaded to ensure priority
 */
function kgmdttio_apply_filters(): void {
	// Prevent multiple applications
	static $applied = false;
	if ( $applied ) {
		return;
	}
	$applied = true;
	
	// Remove any existing filters to prevent accumulation
	remove_filter( 'jpeg_quality', 'kgmdttio_jpeg_quality_filter' );
	remove_filter( 'big_image_size_threshold', 'kgmdttio_big_image_threshold_filter' );
	remove_filter( 'intermediate_image_sizes', 'kgmdttio_thumbnail_sizes_filter' );
	
	// Safely get options with fallback
	$kgmimgquality_options = $GLOBALS['kgmimgquality_options'] ?? get_option( KGM_QUALITY_OPTION, [] );
	$kgmdisablethreshold_options = $GLOBALS['kgmdisablethreshold_options'] ?? get_option( KGM_THRESHOLD_OPTION, [] );
	$kgmdisablethumbnails_options = $GLOBALS['kgmdisablethumbnails_options'] ?? get_option( KGM_THUMBNAILS_OPTION, [] );
	
	// Apply JPEG quality filter
	$jpeg_quality = null;
	if ( is_array( $kgmimgquality_options ) ) {
		$jpeg_quality = $kgmimgquality_options['jpeg_quality'] ?? null;
	}
	
	if ( $jpeg_quality && is_numeric($jpeg_quality) && ($quality = intval($jpeg_quality)) > 0 && $quality <= 100 ) {
		add_filter("jpeg_quality", 'kgmdttio_jpeg_quality_filter', 100);
	}
	
	// Apply threshold filters
	if ( is_array( $kgmdisablethreshold_options ) ) {
		// Set new threshold
		if ( !empty( $kgmdisablethreshold_options['new_threshold'] ) && is_numeric($kgmdisablethreshold_options['new_threshold']) && ($threshold_int = intval($kgmdisablethreshold_options['new_threshold'])) > 0 ) {
			add_filter("big_image_size_threshold", 'kgmdttio_big_image_threshold_filter', 100);
		}
		
		// Disable threshold
		if ( $kgmdisablethreshold_options['disable_threshold'] ?? null ) {
			if ($kgmdisablethreshold_options['disable_threshold'] == 'disable_threshold') {
				add_filter( 'big_image_size_threshold', '__return_false', 100 );
			}
		}
		
		// Disable EXIF rotation
		if ( $kgmdisablethreshold_options['disable_image_rotation_exif'] ?? null ) {
			if ($kgmdisablethreshold_options['disable_image_rotation_exif'] == 'disable_image_rotation_exif') {
				add_filter( 'wp_image_maybe_exif_rotate', '__return_zero', 100, 2 );
			}
		}
	}
	
	// Apply thumbnail size filters
	if ( !empty( $kgmdisablethumbnails_options ) ) {
		add_filter( 'intermediate_image_sizes', 'kgmdttio_thumbnail_sizes_filter', 100);
	}
}

// All filters are now applied in kgmdttio_apply_filters() function after themes are loaded

/**
 * 
 * Add link on plugin list page
 * 
 */
function kgmimage_action_links( array $actions ): array {
	// Check if user has proper capabilities before showing action links
	if ( ! current_user_can( 'manage_options' ) ) {
		return $actions;
	}
	
	$mylinks = [ 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmdisablethumbnails') ) .'">Image sizes</a>', 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmimgquality') ) .'">Image Quality</a>', 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmdisablethreshold') ) .'">Threshold & EXIF</a>' 
	];
	return array_merge( $mylinks, $actions );
}

/**
 * 
 * Load admin styles
 * 
 */
function kgmimage_admin_styles( string $hook ): void {
	/**
	 * Check if in plugin options page and user has proper capabilities
	 */
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	
	if ( $screen->base === 'tools_page_kgmdisablethumbnails' || $screen->base === 'tools_page_kgmdisablethreshold' ) {
		$css_url = plugins_url('includes/admin.css', __FILE__);
		if ( $css_url ) {
			wp_enqueue_style( 'kgmdimage_admin_css', $css_url );
		}
	}
}

/**
 *  
 * Uninstallation
 * 
 */
register_uninstall_hook( __FILE__, 'kgmdttio_plugin_uninstall' );
function kgmdttio_plugin_uninstall(): void {
    delete_option( KGM_THUMBNAILS_OPTION );
    delete_option( KGM_THRESHOLD_OPTION );
    delete_option( KGM_QUALITY_OPTION );
}
