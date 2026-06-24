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

function rup_gb_gist_parse_accounts_raw(string $raw): array {
	$accounts = [];
	$lines = preg_split('/\r\n|\r|\n/', $raw);

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
			continue;
		}

		[$alias, $token] = array_map('trim', explode('=', $line, 2));
		$alias = sanitize_key($alias);
		$token = trim($token);

		if ($alias !== '' && $token !== '') {
			$accounts[$alias] = $token;
		}
	}

	return $accounts;
}

function rup_gb_gist_get_config_accounts(): array {
	if (!defined('RUP_GB_GIST_GITHUB_TOKENS')) {
		return [];
	}

	$config = constant('RUP_GB_GIST_GITHUB_TOKENS');
	$accounts = [];

	if (is_array($config)) {
		foreach ($config as $alias => $token) {
			$alias = sanitize_key((string) $alias);
			$token = trim((string) $token);
			if ($alias !== '' && $token !== '') {
				$accounts[$alias] = $token;
			}
		}
		return $accounts;
	}

	if (is_string($config)) {
		return rup_gb_gist_parse_accounts_raw($config);
	}

	return [];
}

function rup_gb_gist_crypto_key(): string {
	return hash('sha256', wp_salt('auth') . '|' . wp_salt('secure_auth') . '|rup-gb-gist', true);
}

function rup_gb_gist_encrypt_token(string $token): array {
	if (!function_exists('openssl_encrypt') || !function_exists('random_bytes')) {
		return ['plain' => base64_encode($token)];
	}

	$iv = random_bytes(12);
	$tag = '';
	$ciphertext = openssl_encrypt($token, 'aes-256-gcm', rup_gb_gist_crypto_key(), OPENSSL_RAW_DATA, $iv, $tag);

	if (!is_string($ciphertext) || $tag === '') {
		return ['plain' => base64_encode($token)];
	}

	return [
		'v'      => 1,
		'cipher' => base64_encode($ciphertext),
		'iv'     => base64_encode($iv),
		'tag'    => base64_encode($tag),
	];
}

function rup_gb_gist_decrypt_token($stored): string {
	if (is_string($stored)) {
		return $stored;
	}

	if (!is_array($stored)) {
		return '';
	}

	if (isset($stored['plain'])) {
		$decoded = base64_decode((string) $stored['plain'], true);
		return is_string($decoded) ? $decoded : '';
	}

	if (empty($stored['cipher']) || empty($stored['iv']) || empty($stored['tag']) || !function_exists('openssl_decrypt')) {
		return '';
	}

	$cipher = base64_decode((string) $stored['cipher'], true);
	$iv     = base64_decode((string) $stored['iv'], true);
	$tag    = base64_decode((string) $stored['tag'], true);

	if (!is_string($cipher) || !is_string($iv) || !is_string($tag)) {
		return '';
	}

	$plain = openssl_decrypt($cipher, 'aes-256-gcm', rup_gb_gist_crypto_key(), OPENSSL_RAW_DATA, $iv, $tag);

	return is_string($plain) ? $plain : '';
}

function rup_gb_gist_get_database_accounts(): array {
	$secure = get_option('rup_gb_gist_accounts_secure', []);
	$accounts = [];

	if (is_array($secure)) {
		foreach ($secure as $alias => $stored) {
			$alias = sanitize_key((string) $alias);
			$token = rup_gb_gist_decrypt_token($stored);
			if ($alias !== '' && $token !== '') {
				$accounts[$alias] = $token;
			}
		}
	}

	/* Backward compatibility for earlier test builds that stored plaintext tokens. */
	$legacy = get_option('rup_gb_gist_accounts', []);
	if (is_array($legacy)) {
		foreach ($legacy as $alias => $token) {
			$alias = sanitize_key((string) $alias);
			$token = trim((string) $token);
			if ($alias !== '' && $token !== '' && !isset($accounts[$alias])) {
				$accounts[$alias] = $token;
			}
		}
	}

	return $accounts;
}

function rup_gb_gist_save_database_accounts(array $accounts): void {
	$secure = [];

	foreach ($accounts as $alias => $token) {
		$alias = sanitize_key((string) $alias);
		$token = trim((string) $token);
		if ($alias !== '' && $token !== '') {
			$secure[$alias] = rup_gb_gist_encrypt_token($token);
		}
	}

	update_option('rup_gb_gist_accounts_secure', $secure, false);
}

function rup_gb_gist_get_accounts(): array {
	/* wp-config.php profiles deliberately override database profiles. */
	return array_merge(rup_gb_gist_get_database_accounts(), rup_gb_gist_get_config_accounts());
}

function rup_gb_gist_get_token(string $account): string {
	$account = sanitize_key($account);
	if ($account === '') {
		return '';
	}

	$accounts = rup_gb_gist_get_accounts();
	return isset($accounts[$account]) ? (string) $accounts[$account] : '';
}

function rup_gb_gist_get_account_options(): array {
	$options = [];
	foreach (rup_gb_gist_get_accounts() as $alias => $token) {
		if ((string) $token !== '') {
			$options[] = ['label' => $alias, 'value' => $alias];
		}
	}
	return $options;
}

function rup_gb_gist_remote_get_json(string $url, string $account = ''): array {
	$key    = rup_gb_gist_cache_key('json', $account . '|' . $url);
	$cached = get_transient($key);

	if (is_array($cached)) {
		return $cached;
	}

	$response = wp_remote_get(
		$url,
		[
			'timeout' => 20,
			'headers' => array_filter([
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'WordPress RUP GitHub Gist Embed',
				'Authorization'        => rup_gb_gist_get_token($account) ? 'Bearer ' . rup_gb_gist_get_token($account) : '',
			]),
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


function rup_gb_gist_remote_get_body(string $url, string $account = '', string $accept = '*/*'): string {
	$key    = rup_gb_gist_cache_key('body', $account . '|' . $url);
	$cached = get_transient($key);

	if (is_string($cached)) {
		return $cached;
	}

	$token = rup_gb_gist_get_token($account);
	$response = wp_remote_get(
		$url,
		[
			'timeout' => 20,
			'headers' => array_filter([
				'Accept'        => $accept,
				'User-Agent'    => 'WordPress RUP GitHub Gist Embed',
				'Authorization' => $token ? 'Bearer ' . $token : '',
			]),
		]
	);

	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
		return '';
	}

	$body = wp_remote_retrieve_body($response);
	if (!is_string($body)) {
		return '';
	}

	set_transient($key, $body, RUP_GB_GIST_CACHE_TTL);

	return $body;
}

function rup_gb_gist_fetch_public_repo_raw_file(string $owner, string $repo, string $path, string $ref = ''): array {
	$owner = sanitize_text_field($owner);
	$repo  = sanitize_text_field($repo);
	$path  = ltrim($path, '/');
	$ref   = sanitize_text_field($ref);

	if ($owner === '' || $repo === '' || $path === '') {
		return [];
	}

	if ($ref === '') {
		$repo_data = rup_gb_gist_remote_get_json(sprintf('https://api.github.com/repos/%s/%s', rawurlencode($owner), rawurlencode($repo)));
		if (!empty($repo_data['default_branch'])) {
			$ref = sanitize_text_field((string) $repo_data['default_branch']);
		}
	}

	if ($ref === '') {
		$ref = 'main';
	}

	$url = sprintf(
		'https://raw.githubusercontent.com/%s/%s/%s/%s',
		rawurlencode($owner),
		rawurlencode($repo),
		implode('/', array_map('rawurlencode', explode('/', $ref))),
		implode('/', array_map('rawurlencode', explode('/', $path)))
	);

	$content = rup_gb_gist_remote_get_body($url, '', 'text/plain, */*');

	if ($content === '') {
		return [];
	}

	return [
		'name'         => basename($path),
		'path'         => $path,
		'content'      => $content,
		'html_url'     => sprintf('https://github.com/%s/%s/blob/%s/%s', rawurlencode($owner), rawurlencode($repo), rawurlencode($ref), implode('/', array_map('rawurlencode', explode('/', $path)))),
		'download_url' => $url,
	];
}

function rup_gb_gist_fetch_gist(string $gist_id, string $account = '', string $revision = 'latest'): array {
	$url = 'https://api.github.com/gists/' . rawurlencode($gist_id);
	if ($revision !== '' && $revision !== 'latest') {
		$url .= '/' . rawurlencode($revision);
	}

	return rup_gb_gist_remote_get_json($url, $account);
}

function rup_gb_gist_fetch_files(string $gist_id, string $account = ''): array {
	$data = rup_gb_gist_fetch_gist($gist_id, $account);

	if (empty($data['files']) || !is_array($data['files'])) {
		return [];
	}

	return array_keys($data['files']);
}

function rup_gb_gist_fetch_revisions(string $gist_id, string $account = ''): array {
	$data = rup_gb_gist_fetch_gist($gist_id, $account);

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


function rup_gb_gist_fetch_repo_file(string $owner, string $repo, string $path, string $ref = '', string $account = ''): array {
	$owner = sanitize_text_field($owner);
	$repo  = sanitize_text_field($repo);
	$path  = ltrim($path, '/');
	$ref   = sanitize_text_field($ref);

	if ($owner === '' || $repo === '' || $path === '') {
		return [];
	}

	$url = sprintf(
		'https://api.github.com/repos/%s/%s/contents/%s',
		rawurlencode($owner),
		rawurlencode($repo),
		implode('/', array_map('rawurlencode', explode('/', $path)))
	);

	if ($ref !== '') {
		$url = add_query_arg('ref', $ref, $url);
	}

	$data = rup_gb_gist_remote_get_json($url, $account);

	if (!empty($data['content']) && is_string($data['content'])) {
		$content = base64_decode(str_replace(["\r", "\n"], '', $data['content']), true);
		if (is_string($content)) {
			return [
				'name'         => isset($data['name']) ? sanitize_text_field((string) $data['name']) : basename($path),
				'path'         => isset($data['path']) ? sanitize_text_field((string) $data['path']) : $path,
				'content'      => $content,
				'html_url'     => isset($data['html_url']) ? esc_url_raw((string) $data['html_url']) : '',
				'download_url' => isset($data['download_url']) ? esc_url_raw((string) $data['download_url']) : '',
			];
		}
	}

	/* Public repositories can be embedded without a token. If the API path fails
	 * or is rate-limited, fall back to GitHub's raw file endpoint.
	 */
	if ($account === '') {
		return rup_gb_gist_fetch_public_repo_raw_file($owner, $repo, $path, $ref);
	}

	return [];
}

function rup_gb_gist_parse_repo_url(string $url): array {
	$parsed = wp_parse_url(esc_url_raw($url));
	$result = ['owner' => '', 'repo' => '', 'path' => '', 'ref' => ''];

	if (empty($parsed['host']) || empty($parsed['path'])) {
		return $result;
	}

	$host = strtolower((string) $parsed['host']);
	$bits = explode('/', trim((string) $parsed['path'], '/'));

	if ($host === 'github.com' && count($bits) >= 5 && $bits[2] === 'blob') {
		$result['owner'] = sanitize_text_field($bits[0]);
		$result['repo']  = sanitize_text_field($bits[1]);
		$result['ref']   = sanitize_text_field($bits[3]);
		$result['path']  = sanitize_text_field(implode('/', array_slice($bits, 4)));
	}

	if ($host === 'raw.githubusercontent.com' && count($bits) >= 4) {
		$result['owner'] = sanitize_text_field($bits[0]);
		$result['repo']  = sanitize_text_field($bits[1]);
		$result['ref']   = sanitize_text_field($bits[2]);
		$result['path']  = sanitize_text_field(implode('/', array_slice($bits, 3)));
	}

	return $result;
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
		'show_footer'    => '1',
		'footer_text'    => '',
		'show_source_link' => '1',
		'source'         => 'gist',
		'account'        => '',
		'repo_owner'     => '',
		'repo_name'      => '',
		'repo_path'      => '',
		'repo_ref'       => '',
	], $atts);

	$source        = sanitize_key((string) $atts['source']);
	if (!in_array($source, ['gist', 'repo'], true)) {
		$source = 'gist';
	}
	$account       = sanitize_key((string) $atts['account']);
	$user          = sanitize_user((string) $atts['user']);
	$id            = sanitize_text_field((string) $atts['id']);
	$file          = sanitize_text_field((string) $atts['file']);
	$revision      = sanitize_text_field((string) $atts['revision']);
	$fragment_slug = '';
	$repo_owner    = sanitize_text_field((string) $atts['repo_owner']);
	$repo_name     = sanitize_text_field((string) $atts['repo_name']);
	$repo_path     = sanitize_text_field((string) $atts['repo_path']);
	$repo_ref      = sanitize_text_field((string) $atts['repo_ref']);

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

		$repo_url = rup_gb_gist_parse_repo_url((string) $atts['url']);
		if (!empty($repo_url['owner'])) {
			$source = 'repo';
			$repo_owner = $repo_owner ?: $repo_url['owner'];
			$repo_name  = $repo_name ?: $repo_url['repo'];
			$repo_path  = $repo_path ?: $repo_url['path'];
			$repo_ref   = $repo_ref ?: $repo_url['ref'];
		}
	}

	/*
	 * Only call the GitHub API when we actually need to infer the file.
	 * Existing blocks/shortcodes with an explicit file should render immediately,
	 * which keeps the editor from waiting on a remote metadata lookup.
	 */
	if ($source === 'gist' && !empty($id) && empty($file)) {
		$files = rup_gb_gist_fetch_files($id, $account);

		if (!empty($fragment_slug)) {
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
		'source'         => $source,
		'account'        => $account,
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
		'show_footer'    => rup_gb_gist_bool($atts['show_footer']),
		'footer_text'    => sanitize_text_field((string) $atts['footer_text']),
		'show_source_link' => rup_gb_gist_bool($atts['show_source_link']),
		'repo_owner'     => $repo_owner,
		'repo_name'      => $repo_name,
		'repo_path'      => $repo_path,
		'repo_ref'       => $repo_ref,
	];
}
