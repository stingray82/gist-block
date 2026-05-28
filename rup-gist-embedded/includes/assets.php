<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

function rup_gb_gist_register_assets(): void {
	wp_register_style(
		'rup-gb-gist-frontend',
		RUP_GB_GIST_URL . 'assets/css/frontend.css',
		[],
		RUP_GB_GIST_VERSION
	);

	wp_register_script(
		'rup-gb-gist-frontend',
		RUP_GB_GIST_URL . 'assets/js/frontend.js',
		[],
		RUP_GB_GIST_VERSION,
		false
	);
}

function rup_gb_gist_enqueue_frontend_assets(): void {
	rup_gb_gist_register_assets();
	wp_enqueue_style('rup-gb-gist-frontend');
	wp_enqueue_script('rup-gb-gist-frontend');
}

add_action('init', 'rup_gb_gist_register_assets');
add_action('wp_enqueue_scripts', 'rup_gb_gist_enqueue_frontend_assets');
add_action('enqueue_block_editor_assets', 'rup_gb_gist_enqueue_frontend_assets');
