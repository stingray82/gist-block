<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', function (): void {
	wp_register_script(
		'rup-gb-gist-block',
		RUP_GB_GIST_URL . 'assets/js/block.js',
		[
			'wp-blocks',
			'wp-element',
			'wp-components',
			'wp-block-editor',
			'wp-server-side-render',
			'wp-api-fetch',
		],
		RUP_GB_GIST_VERSION,
		true
	);

	wp_register_style(
		'rup-gb-gist-editor',
		RUP_GB_GIST_URL . 'assets/css/editor.css',
		[],
		RUP_GB_GIST_VERSION
	);

	rup_gb_gist_register_assets();

	register_block_type('rup-gb/github-gist', [
		'editor_script'   => 'rup-gb-gist-block',
		'style'           => 'rup-gb-gist-frontend',
		'view_script'     => 'rup-gb-gist-frontend',
		'editor_style'    => 'rup-gb-gist-editor',
		'render_callback' => 'rup_gb_render_gist_embed',
		'attributes'      => [
			'url'            => ['type' => 'string', 'default' => ''],
			'file'           => ['type' => 'string', 'default' => ''],
			'revision'       => ['type' => 'string', 'default' => 'latest'],
			'title'          => ['type' => 'string', 'default' => ''],
			'raw_link_class' => ['type' => 'string', 'default' => ''],
			'wrapper_class'  => ['type' => 'string', 'default' => ''],
			'max_height'     => ['type' => 'string', 'default' => '400'],
			'font'           => ['type' => 'string', 'default' => 'mono'],
			'custom_font'    => ['type' => 'string', 'default' => ''],
			'font_size'      => ['type' => 'string', 'default' => '13'],
			'show_copy'      => ['type' => 'boolean', 'default' => true],
		],
	]);
});
