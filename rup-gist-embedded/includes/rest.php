<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('rest_api_init', function (): void {
	register_rest_route('rup-gb/v1', '/gist-meta', [
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function (WP_REST_Request $request) {
			$parsed = rup_gb_gist_parse_atts([
				'url' => (string) $request->get_param('url'),
				'id'      => (string) $request->get_param('id'),
				'account' => (string) $request->get_param('account'),
			]);

			if (empty($parsed['id']) || !preg_match('/^[a-f0-9]+$/i', $parsed['id'])) {
				return rest_ensure_response([
					'files'     => [],
					'revisions' => [],
				]);
			}

			return rest_ensure_response([
				'files'     => rup_gb_gist_fetch_files($parsed['id'], $parsed['account']),
				'revisions' => rup_gb_gist_fetch_revisions($parsed['id'], $parsed['account']),
			]);
		},
	]);


	register_rest_route('rup-gb/v1', '/accounts', [
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
		'callback'            => function () {
			return rest_ensure_response([
				'accounts' => rup_gb_gist_get_account_options(),
			]);
		},
	]);

	register_rest_route('rup-gb/v1', '/gist-cache', [
		'methods'             => 'DELETE',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
		'callback'            => function () {
			rup_gb_gist_clear_cache();

			return rest_ensure_response([
				'success' => true,
			]);
		},
	]);
});
