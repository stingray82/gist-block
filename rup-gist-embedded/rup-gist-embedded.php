<?php
/**
 * Plugin Name:       Gist Embedded
 * Description:       Responsive, accessible GitHub Gist shortcode and Gutenberg block with file and revision support.
 * Tested up to:      7.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Version:           1.0.2
 * Author:            Reallyusefulplugins.com
 * Author URI:        https://reallyusefulplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rup-gist-embedded
 * Website:           https://reallyusefulplugins.com
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('RUP_GB_GIST_VERSION', '1.0.2');
define('RUP_GB_GIST_FILE', __FILE__);
define('RUP_GB_GIST_PATH', plugin_dir_path(__FILE__));
define('RUP_GB_GIST_URL', plugin_dir_url(__FILE__));
define('RUP_GB_GIST_CACHE_TTL', WEEK_IN_SECONDS);

require_once RUP_GB_GIST_PATH . 'includes/helpers.php';
require_once RUP_GB_GIST_PATH . 'includes/rest.php';
require_once RUP_GB_GIST_PATH . 'includes/render.php';
require_once RUP_GB_GIST_PATH . 'includes/block.php';
require_once RUP_GB_GIST_PATH . 'includes/assets.php';
