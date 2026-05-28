<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

function rup_gb_gist_clean_classes(string $classes): string {
	$parts = preg_split('/\s+/', trim($classes));
	$clean = [];

	foreach ($parts as $part) {
		$part = sanitize_html_class($part);
		if ($part !== '') {
			$clean[] = $part;
		}
	}

	return implode(' ', array_unique($clean));
}

function rup_gb_gist_bool($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	$value = strtolower(trim((string) $value));

	return !in_array($value, ['0', 'false', 'no', 'off'], true);
}

function rup_gb_gist_file_slug(string $filename): string {
	$slug = strtolower($filename);
	$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
	$slug = trim((string) $slug, '-');

	return 'file-' . $slug;
}

function rup_gb_gist_cache_key(string $type, string $value): string {
	return 'rup_gb_gist_' . $type . '_' . md5($value);
}

function rup_gb_gist_remote_get_json(string $url): array {
	$key    = rup_gb_gist_cache_key('json', $url);
	$cached = get_transient($key);

	if (is_array($cached)) {
		return $cached;
	}

	$response = wp_remote_get(
		$url,
		[
			'timeout' => 20,
			'headers' => [
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'WordPress RUP GitHub Gist Embed',
			],
		]
	);

	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
		return [];
	}

	$data = json_decode(wp_remote_retrieve_body($response), true);

	if (!is_array($data)) {
		return [];
	}

	set_transient($key, $data, RUP_GB_GIST_CACHE_TTL);

	return $data;
}

function rup_gb_gist_fetch_gist(string $gist_id): array {
	return rup_gb_gist_remote_get_json(
		'https://api.github.com/gists/' . rawurlencode($gist_id)
	);
}

function rup_gb_gist_fetch_files(string $gist_id): array {
	$data = rup_gb_gist_fetch_gist($gist_id);

	if (empty($data['files']) || !is_array($data['files'])) {
		return [];
	}

	return array_keys($data['files']);
}

function rup_gb_gist_fetch_revisions(string $gist_id): array {
	$data = rup_gb_gist_fetch_gist($gist_id);

	if (empty($data['history']) || !is_array($data['history'])) {
		return [];
	}

	$revisions = [];

	foreach ($data['history'] as $revision) {
		if (empty($revision['version'])) {
			continue;
		}

		$version = sanitize_text_field((string) $revision['version']);

		$date = !empty($revision['committed_at'])
			? date_i18n('Y-m-d H:i', strtotime((string) $revision['committed_at']))
			: substr($version, 0, 8);

		$revisions[] = [
			'label' => $date . ' — ' . substr($version, 0, 8),
			'value' => $version,
		];
	}

	return $revisions;
}

function rup_gb_gist_clear_cache(): void {
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '_transient_rup_gb_gist_%'
		OR option_name LIKE '_transient_timeout_rup_gb_gist_%'"
	);
}

function rup_gb_gist_parse_atts(array $atts): array {
	$atts = shortcode_atts([
		'url'            => '',
		'user'           => '',
		'id'             => '',
		'file'           => '',
		'revision'       => 'latest',
		'title'          => '',
		'raw_link_class' => '',
		'wrapper_class'  => '',
		'max_height'     => '400',
		'font'           => 'mono',
		'custom_font'    => '',
		'font_size'      => '13',
		'show_copy'      => '1',
	], $atts);

	$user          = sanitize_user((string) $atts['user']);
	$id            = sanitize_text_field((string) $atts['id']);
	$file          = sanitize_text_field((string) $atts['file']);
	$revision      = sanitize_text_field((string) $atts['revision']);
	$fragment_slug = '';

	if (!empty($atts['url'])) {
		$url = wp_parse_url(esc_url_raw((string) $atts['url']));

		if (!empty($url['host']) && $url['host'] === 'gist.github.com' && !empty($url['path'])) {
			$bits = explode('/', trim($url['path'], '/'));

			if (count($bits) >= 2) {
				$user = sanitize_user($bits[0]);
				$id   = sanitize_text_field($bits[1]);
			}

			if (count($bits) >= 3 && ($revision === '' || $revision === 'latest')) {
				$revision = sanitize_text_field($bits[2]);
			}
		}

		if (empty($file) && !empty($url['fragment']) && str_starts_with((string) $url['fragment'], 'file-')) {
			$fragment_slug = sanitize_title((string) $url['fragment']);
		}
	}

	if (!empty($id)) {
		$files = rup_gb_gist_fetch_files($id);

		if (empty($file) && !empty($fragment_slug)) {
			foreach ($files as $filename) {
				if (rup_gb_gist_file_slug($filename) === $fragment_slug) {
					$file = $filename;
					break;
				}
			}
		}

		if (empty($file) && !empty($files[0])) {
			$file = $files[0];
		}
	}

	$max_height = absint($atts['max_height']);
	if ($max_height < 1) {
		$max_height = 400;
	}

	$font_size = absint($atts['font_size']);
	if ($font_size < 6) {
		$font_size = 6;
	}
	if ($font_size > 96) {
		$font_size = 96;
	}

	$font = sanitize_key((string) $atts['font']);
	if (!in_array($font, ['github', 'site', 'system', 'mono', 'custom'], true)) {
		$font = 'mono';
	}

	if ($revision === '') {
		$revision = 'latest';
	}

	return [
		'user'           => $user,
		'id'             => $id,
		'file'           => $file,
		'revision'       => $revision,
		'title'          => sanitize_text_field((string) $atts['title']),
		'raw_link_class' => rup_gb_gist_clean_classes((string) $atts['raw_link_class']),
		'wrapper_class'  => rup_gb_gist_clean_classes((string) $atts['wrapper_class']),
		'max_height'     => $max_height,
		'font'           => $font,
		'custom_font'    => sanitize_text_field((string) $atts['custom_font']),
		'font_size'      => $font_size,
		'show_copy'      => rup_gb_gist_bool($atts['show_copy']),
	];
}
