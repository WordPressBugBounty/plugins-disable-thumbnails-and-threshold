=== Disable Thumbnails, Threshold and Image Options ===
Contributors: kgmservizi
Donate link: https://kgmservizi.com
Tags: thumbnails, disable thumbnails, disable threshold, disable images, image options
Requires at least: 5.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.6.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable thumbnail sizes, default WordPress sizes and theme/plugins image size and others image advanced control.

== Description ==

**Don't work? Open ticket, we answer in max 48h.**

Disable thumbnail sizes, default WordPress sizes and theme/plugins image size and others image advanced control.

Useful for disable some unused image sizes from WooCommerce, theme or plugins. This can be a good choice for decrease images generated and them space.

**Smart Initialization**: The plugin intelligently imports current WordPress settings (from themes, other plugins, or custom functions.php) rather than using default values. This maximizes compatibility with existing sites and ensures smooth integration without disrupting current configurations.

**Important**: From the moment of installation, the plugin takes control of the settings. Any external overrides (from themes, other plugins, or custom code) will be clearly indicated in red warnings below the settings, allowing you to identify and resolve conflicts.

**

= Thumbnails =

* Disable WordPress default image size.
* Disable theme image size.
* Disable plugin image size.

= Threshold & EXIF =

* Change default image threshold size.
* Disable threshold.
* Disable image rotation by EXIF.

= Image Quality =

* Change JPEG image quality.

= Smart Initialization =

* Automatically imports current WordPress settings for maximum compatibility
* Respects existing theme and plugin configurations
* Seamless integration with active sites

= Conflict Detection =

* Red warning messages indicate external overrides
* Clear identification of conflicts with themes/plugins
* Easy troubleshooting and resolution guidance


== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/' directory, or install the plugin through the WordPress plugins page directly.
2. Activate the plugin through the 'Plugins' page in WordPress.
3. Go to settings page inside menù Tools -> Thumbnails, Image Quality or Image Threshold & EXIF


== Frequently Asked Questions ==

= Why disable thumbnails image sizes generation? =

When some image sizes aren't useful you can decrease number of files on your server and used space.


= How I can disable thumbnails image size? =

Go to settings page inside menù Tools -> Image Thumbnails

**Regenerate Thumbnails after disable/enable sizes.**


= Why I need to regenerate thumbnails? =

Because you need to delete or generate the image size disabled/enabled.


= How I can regenerate thumbnails with a plugin? =

**We recommend:** [Regenerate Thumbnails](https://uskgm.it/reg-thumb "Regenerate Thumbnails") by [Alex Mills - Viper007Bond](https://uskgm.it/alex-mills-viper007bond "Alex Mills - Viper007Bond")

= How I can regenerate with WP-CLI (dev use only)? =

**You can read WordPress guide:** [WP-CLI Thumbnail Regeneration](https://uskgm.it/WP-CLI-thumb-rgnrt "WP-CLI Thumbnail Regeneration")

= How does the plugin handle existing site settings? =

The plugin uses **Smart Initialization** to maximize compatibility:

* **JPEG Quality**: Imports current WordPress JPEG quality (from themes/plugins) instead of default 82%
* **Image Threshold**: Imports current big image threshold (from themes/plugins) instead of default 2560px
* **EXIF Rotation**: Respects existing EXIF rotation settings from other sources
* **Thumbnail Sizes**: Works with current thumbnail configuration

**After Installation**: The plugin takes full control of the settings. Any external overrides will be clearly indicated with red warning messages below each setting, helping you identify and resolve conflicts with themes, other plugins, or custom code.

= What do the red warning messages mean? =

Red warning messages below settings indicate that external code (themes, plugins, or custom functions.php) is overriding the plugin's settings:

* **"Plugin quality/threshold is being overridden"**: Another source is changing the value
* **"Plugin intends to disable threshold/exif rotation, but currently active"**: Plugin wants to disable but external code is keeping it active
* **"Currently disabled by external settings"**: External code disabled something the plugin wants to keep active

These warnings help you identify conflicts and take appropriate action (disable conflicting code, adjust priorities, etc.).

= How I can change image quality? =

Go to settings page inside menù Tools -> Image Quality


= How I can change or disable threshold? =

Go to settings page inside menù Tools -> Image Threshold&EXIF


= How I can disable image rotation by EXIF? =

Go to settings page inside menù Tools -> Image Threshold&EXIF


== Screenshots ==

1. Image Thumbnails page settings.
2. Image Quality page settings.
3. Image Threshold&EXIF page settings.


== Changelog ==

= 0.6.5 =
* Bugfix.

= 0.6.4 =
* Bugfix, removed option full.

= 0.6.3 =
* Fix for version check and update old settings.

**WordPress & PHP Requirements**: Updated minimum requirement to WordPress 5.4+ and PHP 7.4+
**Code Modernization**
**Performance Optimization**
**Smart Initialization**
**Intelligent Debug System**


== Upgrade Notice ==

= 0.6.5 =
Bugfix.

= 0.6.4 =
Bugfix, removed option full.

= 0.6.3 =
Fix for version check and update old settings.

**WordPress & PHP Requirements**: Updated minimum requirement to WordPress 5.4+ and PHP 7.4+
**Code Modernization**
**Performance Optimization**
**Smart Initialization**
**Intelligent Debug System**

`<?php code(); // goes in backticks ?>`