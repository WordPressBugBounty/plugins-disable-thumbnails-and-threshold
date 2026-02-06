<?php
/* 
Plugin Name: Disable Thumbnails, Threshold and Image Options
Version: 0.6.5
Description: Disable Thumbnails, Threshold and Image Options
Author: KGM Servizi
Author URI: https://kgmservizi.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: disable-thumbnails-and-threshold
Requires at least: 5.4
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants for better maintainability
define( 'DTAT_QUALITY_OPTION', 'dtat_imgquality_option_name' );
define( 'DTAT_THRESHOLD_OPTION', 'dtat_disablethreshold_option_name' );
define( 'DTAT_THUMBNAILS_OPTION', 'dtat_disablethumbnails_option_name' );
define( 'DTAT_MIGRATION_OPTION', 'dtat_migration_done' );
define( 'DTAT_VERSION', '0.6.5' );

/**
 * PHPCS Suppressions
 * 
 * This file contains phpcs:ignore comments for the following cases:
 * - Line 151, 131 (option-quality.php): WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 *   Reason: Using WordPress core hook (jpeg_quality) which doesn't require plugin prefix
 * - Line 174, 172 (option-threshold-exif.php), 206 (option-threshold-exif.php): WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 *   Reason: Using WordPress core hook (big_image_size_threshold) which doesn't require plugin prefix
 * - Line 241 (option-threshold-exif.php): WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 *   Reason: Using WordPress core hook (wp_image_maybe_exif_rotate) which doesn't require plugin prefix
 */

// Check WordPress version compatibility - requires WordPress 5.4+ for PHP 7.4+ support
// Use admin_init hook to ensure WordPress is fully loaded
add_action( 'admin_init', 'dtat_check_wordpress_version' );

if ( is_admin() ) {
	// Use include_once to prevent multiple inclusions and potential fatal errors
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-thumbnails.php');
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-quality.php');
	include_once( plugin_dir_path( __FILE__ ) . 'includes/option-threshold-exif.php');
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'dtat_action_links' );
	add_action( 'admin_enqueue_scripts', 'dtat_admin_styles' );
}

dtat_maybe_migrate_options();

/**
 *  
 * Retrieve options for quality and threshold
 * Cache options globally to avoid multiple database calls
 * 
 */
$GLOBALS['dtat_imgquality_options']        = get_option( DTAT_QUALITY_OPTION );
$GLOBALS['dtat_disablethreshold_options']  = get_option( DTAT_THRESHOLD_OPTION );
$GLOBALS['dtat_disablethumbnails_options'] = get_option( DTAT_THUMBNAILS_OPTION );

/**
 * Migrate legacy option names (`kgm*`) to DTAT-prefixed names.
 *
 * @since 0.6.5
 *
 * Introduced to ensure backward compatibility when renaming options.
 * This migration runs once (per site) and copies existing values into
 * the new option names before deleting the legacy entries.
 */
function dtat_maybe_migrate_options(): void {
	static $processed = false;
	if ( $processed ) {
		return;
	}
	$processed = true;

	// Skip migration if already completed for this plugin version.
	if ( get_option( DTAT_MIGRATION_OPTION ) === DTAT_VERSION ) {
		return;
	}

	$option_map = [
		DTAT_QUALITY_OPTION     => 'kgmimgquality_option_name',
		DTAT_THRESHOLD_OPTION   => 'kgmdisablethreshold_option_name',
		DTAT_THUMBNAILS_OPTION  => 'kgmdisablethumbnails_option_name',
	];

	foreach ( $option_map as $new_option => $legacy_option ) {
		$new_value = get_option( $new_option, null );

		// If the new option already has data, skip migration for this entry.
		if ( false !== $new_value && null !== $new_value ) {
			continue;
		}

		$legacy_value = get_option( $legacy_option, null );
		if ( false === $legacy_value || null === $legacy_value ) {
			continue;
		}

		update_option( $new_option, $legacy_value );
		delete_option( $legacy_option );
	}

	update_option( DTAT_MIGRATION_OPTION, DTAT_VERSION );
}


// Initialize with current WordPress values if options don't exist
// This will be handled in the admin_init hook to ensure WordPress is fully loaded
add_action( 'admin_init', 'dtat_initialize_options' );

// Hook to apply filters after themes are loaded to ensure priority
add_action( 'after_setup_theme', 'dtat_apply_filters', 20 );

/**
 * Check WordPress version compatibility
 * Called on admin_init to ensure WordPress is fully loaded
 */
function dtat_check_wordpress_version(): void {
	// Only run in admin
	if ( ! is_admin() ) {
		return;
	}
	
	// Check if WordPress version is compatible
	if ( version_compare( get_bloginfo( 'version' ), '5.4', '<' ) ) {
		add_action( 'admin_notices', function() {
			printf( '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'Disable Thumbnails, Threshold and Image Options', 'disable-thumbnails-and-threshold' ),
				esc_html__( 'requires WordPress 5.4 or higher (PHP 7.4+). Please update WordPress.', 'disable-thumbnails-and-threshold' )
			);
		} );
	}
}

/**
 * Initialize plugin options with current WordPress values
 * Called on admin_init to ensure WordPress is fully loaded
 */
function dtat_initialize_options(): void {
	// Only run in admin and if user has proper capabilities
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Initialize JPEG quality option if it doesn't exist or is invalid
	if ( ! $GLOBALS['dtat_imgquality_options'] || 
		 ! is_array( $GLOBALS['dtat_imgquality_options'] ) || 
		 ! isset( $GLOBALS['dtat_imgquality_options']['jpeg_quality'] ) ||
		 ! is_numeric( $GLOBALS['dtat_imgquality_options']['jpeg_quality'] ) ||
		 intval( $GLOBALS['dtat_imgquality_options']['jpeg_quality'] ) < 1 ||
		 intval( $GLOBALS['dtat_imgquality_options']['jpeg_quality'] ) > 100 ) {
		
		// Get current WordPress JPEG quality (respects existing filters/plugins)
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook
		$current_quality = apply_filters( 'jpeg_quality', 82 );
		
		// Validate the quality value from filters
		if ( ! is_numeric( $current_quality ) || $current_quality < 1 || $current_quality > 100 ) {
			$current_quality = 82; // Fallback to WordPress default
		}
		
		// Preserve existing options and only update jpeg_quality
		if ( ! is_array( $GLOBALS['dtat_imgquality_options'] ) ) {
			$GLOBALS['dtat_imgquality_options'] = [];
		}
		$GLOBALS['dtat_imgquality_options']['jpeg_quality'] = intval( $current_quality );
		update_option( DTAT_QUALITY_OPTION, $GLOBALS['dtat_imgquality_options'] );
	}
	
	// Initialize threshold option if it doesn't exist or is invalid
	if ( ! $GLOBALS['dtat_disablethreshold_options'] || 
		 ! is_array( $GLOBALS['dtat_disablethreshold_options'] ) || 
		 ! isset( $GLOBALS['dtat_disablethreshold_options']['new_threshold'] ) ||
		 ! is_numeric( $GLOBALS['dtat_disablethreshold_options']['new_threshold'] ) ||
		 intval( $GLOBALS['dtat_disablethreshold_options']['new_threshold'] ) <= 0 ) {
		
		// Get current WordPress big image threshold (respects existing filters/plugins)
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook
		$current_threshold = apply_filters( 'big_image_size_threshold', 2560 );
		
		// Validate the threshold value from filters
		if ( ! is_numeric( $current_threshold ) || $current_threshold <= 0 ) {
			$current_threshold = 2560; // Fallback to WordPress default
		}
		
		// Preserve existing options and only update new_threshold
		if ( ! is_array( $GLOBALS['dtat_disablethreshold_options'] ) ) {
			$GLOBALS['dtat_disablethreshold_options'] = [];
		}
		$GLOBALS['dtat_disablethreshold_options']['new_threshold'] = intval( $current_threshold );
		update_option( DTAT_THRESHOLD_OPTION, $GLOBALS['dtat_disablethreshold_options'] );
	}
}

/**
 * JPEG Quality filter callback
 */
function dtat_jpeg_quality_filter(): int {
	$dtat_imgquality_options = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION, [] );
	
	if ( ! is_array( $dtat_imgquality_options ) || ! isset( $dtat_imgquality_options['jpeg_quality'] ) ) {
		return 82; // WordPress default
	}
	
	$quality = intval( $dtat_imgquality_options['jpeg_quality'] );
	
	// Validate quality range (0-100)
	if ( $quality < 0 || $quality > 100 ) {
		return 82; // WordPress default
	}
	
	return $quality;
}

/**
 * Big image threshold filter callback
 */
function dtat_big_image_threshold_filter(): int {
	$dtat_disablethreshold_options = $GLOBALS['dtat_disablethreshold_options'] ?? get_option( DTAT_THRESHOLD_OPTION, [] );
	
	if ( ! is_array( $dtat_disablethreshold_options ) || ! isset( $dtat_disablethreshold_options['new_threshold'] ) ) {
		return 2560; // WordPress default
	}
	
	$threshold = intval( $dtat_disablethreshold_options['new_threshold'] );
	
	// Validate threshold is positive
	if ( $threshold <= 0 ) {
		return 2560; // WordPress default
	}
	
	return $threshold;
}

/**
 * Thumbnail sizes filter callback
 */
function dtat_thumbnail_sizes_filter( array $sizes ): array {
	$dtat_disablethumbnails_options = $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION, [] );
	
	if ( ! is_array( $dtat_disablethumbnails_options ) || empty( $dtat_disablethumbnails_options ) ) {
		return $sizes;
	}
	
	// Validate that all values in the array are strings (security check)
	$valid_options = array_filter( $dtat_disablethumbnails_options, 'is_string' );
	
	return array_diff( $sizes, $valid_options );
}

/**
 * Apply plugin filters after themes are loaded to ensure priority
 */
function dtat_apply_filters(): void {
	// Prevent multiple applications
	static $applied = false;
	if ( $applied ) {
		return;
	}
	$applied = true;
	
	// Remove any existing filters to prevent accumulation
	remove_filter( 'jpeg_quality', 'dtat_jpeg_quality_filter' );
	remove_filter( 'big_image_size_threshold', 'dtat_big_image_threshold_filter' );
	remove_filter( 'intermediate_image_sizes', 'dtat_thumbnail_sizes_filter' );
	
	// Safely get options with fallback
	$dtat_imgquality_options = $GLOBALS['dtat_imgquality_options'] ?? get_option( DTAT_QUALITY_OPTION, [] );
	$dtat_disablethreshold_options = $GLOBALS['dtat_disablethreshold_options'] ?? get_option( DTAT_THRESHOLD_OPTION, [] );
	$dtat_disablethumbnails_options = $GLOBALS['dtat_disablethumbnails_options'] ?? get_option( DTAT_THUMBNAILS_OPTION, [] );
	
	// Apply JPEG quality filter
	$jpeg_quality = null;
	if ( is_array( $dtat_imgquality_options ) ) {
		$jpeg_quality = $dtat_imgquality_options['jpeg_quality'] ?? null;
	}
	
	if ( $jpeg_quality && is_numeric($jpeg_quality) && ($quality = intval($jpeg_quality)) > 0 && $quality <= 100 ) {
		add_filter("jpeg_quality", 'dtat_jpeg_quality_filter', 100);
	}
	
	// Apply threshold filters
	if ( is_array( $dtat_disablethreshold_options ) ) {
		// Set new threshold
		if ( !empty( $dtat_disablethreshold_options['new_threshold'] ) && is_numeric($dtat_disablethreshold_options['new_threshold']) && ($threshold_int = intval($dtat_disablethreshold_options['new_threshold'])) > 0 ) {
			add_filter("big_image_size_threshold", 'dtat_big_image_threshold_filter', 100);
		}
		
		// Disable threshold
		if ( $dtat_disablethreshold_options['disable_threshold'] ?? null ) {
			if ($dtat_disablethreshold_options['disable_threshold'] == 'disable_threshold') {
				add_filter( 'big_image_size_threshold', '__return_false', 100 );
			}
		}
		
		// Disable EXIF rotation
		if ( $dtat_disablethreshold_options['disable_image_rotation_exif'] ?? null ) {
			if ($dtat_disablethreshold_options['disable_image_rotation_exif'] == 'disable_image_rotation_exif') {
				add_filter( 'wp_image_maybe_exif_rotate', '__return_zero', 100, 2 );
			}
		}
	}
	
	// Apply thumbnail size filters
	if ( !empty( $dtat_disablethumbnails_options ) ) {
		add_filter( 'intermediate_image_sizes', 'dtat_thumbnail_sizes_filter', 100);
	}
}

// All filters are now applied in dtat_apply_filters() function after themes are loaded

/**
 * 
 * Add link on plugin list page
 * 
 */
function dtat_action_links( array $actions ): array {
	// Check if user has proper capabilities before showing action links
	if ( ! current_user_can( 'manage_options' ) ) {
		return $actions;
	}
	
	$mylinks = [ 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmdisablethumbnails') ) .'">' . esc_html__( 'Image sizes', 'disable-thumbnails-and-threshold' ) . '</a>', 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmimgquality') ) .'">' . esc_html__( 'Image Quality', 'disable-thumbnails-and-threshold' ) . '</a>', 
		'<a href="'. esc_url( get_admin_url(null, 'tools.php?page=kgmdisablethreshold') ) .'">' . esc_html__( 'Threshold & EXIF', 'disable-thumbnails-and-threshold' ) . '</a>' 
	];
	return array_merge( $mylinks, $actions );
}

/**
 * 
 * Load admin styles
 * 
 */
function dtat_admin_styles( string $hook ): void {
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
			wp_enqueue_style( 'dtat_admin_css', $css_url, array(), DTAT_VERSION );
		}
	}
}

/**
 *  
 * Uninstallation
 * 
 */
register_uninstall_hook( __FILE__, 'dtat_plugin_uninstall' );
function dtat_plugin_uninstall(): void {
    delete_option( DTAT_THUMBNAILS_OPTION );
    delete_option( DTAT_THRESHOLD_OPTION );
    delete_option( DTAT_QUALITY_OPTION );
    delete_option( DTAT_MIGRATION_OPTION );
}
