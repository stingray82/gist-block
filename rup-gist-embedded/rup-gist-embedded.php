<?php
/**
 * Plugin Name:       Gist Embedded
 * Description:       Responsive, accessible GitHub Gist shortcode and Gutenberg block with file and revision support.
 * Tested up to:      7.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Version:           1.1.0-RC
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

define('RUP_GB_GIST_VERSION', '1.1.0-RC');
define('RUP_GB_GIST_FILE', __FILE__);
define('RUP_GB_GIST_PATH', plugin_dir_path(__FILE__));
define('RUP_GB_GIST_URL', plugin_dir_url(__FILE__));
define('RUP_GB_GIST_CACHE_TTL', WEEK_IN_SECONDS);
define('RUP_GB_GIST_SLUG', 'rup-gist-embedded');

require_once RUP_GB_GIST_PATH . 'includes/helpers.php';
require_once RUP_GB_GIST_PATH . 'includes/rest.php';
require_once RUP_GB_GIST_PATH . 'includes/settings.php';
require_once RUP_GB_GIST_PATH . 'includes/render.php';
require_once RUP_GB_GIST_PATH . 'includes/block.php';
require_once RUP_GB_GIST_PATH . 'includes/assets.php';


add_action('plugins_loaded', function () {
    $updater_file = RUP_GB_GIST_PATH . 'includes/updater.php';

    if (!file_exists($updater_file)) {
        return;
    }

    require_once $updater_file;

    if (!class_exists('\RUP\Updater\Updater_V2')) {
        return;
    }

    $updater_config = [
        'vendor'      => 'RUP',
        'plugin_file' => plugin_basename(__FILE__),
        'slug'        => RUP_GB_GIST_SLUG,
        'name'        => 'Guttenberg Link in Bio',
        'version'     => RUP_GB_GIST_VERSION,
        'key'         => '',
        'server'      => 'https://raw.githubusercontent.com/stingray82/gist-block/main/uupd/index.json',
    ];

    \RUP\Updater\Updater_V2::register($updater_config);
}, 20);

/**
 * MainWP icon support.
 */
add_filter('mainwp_child_stats_get_plugin_info', function ($info, $slug) {
    if ('rup-gist-embedded/rup-gist-embedded.php' === $slug) {
        $info['icon'] = 'https://raw.githubusercontent.com/stingray82//gist-block/main/uupd/icon-128.png';
    }

    return $info;
}, 10, 2);
