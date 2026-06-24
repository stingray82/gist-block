<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_menu', function (): void {
	add_options_page(
		__('Gist Embedded', 'rup-gist-embedded'),
		__('Gist Embedded', 'rup-gist-embedded'),
		'manage_options',
		'rup-gist-embedded',
		'rup_gb_gist_render_settings_page'
	);
});

add_action('admin_post_rup_gb_gist_save_settings', 'rup_gb_gist_handle_settings_save');

function rup_gb_gist_handle_settings_save(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to manage these settings.', 'rup-gist-embedded'));
	}

	check_admin_referer('rup_gb_gist_save_settings');

	$updated = false;

	if (!empty($_POST['rup_gb_gist_clear_accounts'])) {
		delete_option('rup_gb_gist_accounts_secure');
		delete_option('rup_gb_gist_accounts');
		delete_option('rup_gb_gist_accounts_raw');
		rup_gb_gist_clear_cache();
		$updated = true;
	} else {
		$raw = isset($_POST['rup_gb_gist_accounts_raw']) ? wp_unslash((string) $_POST['rup_gb_gist_accounts_raw']) : '';
		$accounts = rup_gb_gist_parse_accounts_raw($raw);

		if (!empty($accounts)) {
			$stored = rup_gb_gist_get_database_accounts();
			$stored = array_merge($stored, $accounts);
			rup_gb_gist_save_database_accounts($stored);
			delete_option('rup_gb_gist_accounts');
			delete_option('rup_gb_gist_accounts_raw');
			rup_gb_gist_clear_cache();
			$updated = true;
		}
	}

	$redirect = add_query_arg(
		[
			'page' => 'rup-gist-embedded',
			'rup_gb_gist_saved' => $updated ? '1' : '0',
		],
		admin_url('options-general.php')
	);

	wp_safe_redirect($redirect);
	exit;
}

function rup_gb_gist_sanitize_clear_accounts($value): string {
	if (rup_gb_gist_bool($value)) {
		delete_option('rup_gb_gist_accounts_secure');
		delete_option('rup_gb_gist_accounts');
		delete_option('rup_gb_gist_accounts_raw');
		rup_gb_gist_clear_cache();
	}

	return '';
}

function rup_gb_gist_sanitize_accounts_raw($value): string {
	$raw = (string) $value;
	$accounts = rup_gb_gist_parse_accounts_raw($raw);

	if (!empty($accounts)) {
		$stored = rup_gb_gist_get_database_accounts();
		$stored = array_merge($stored, $accounts);
		rup_gb_gist_save_database_accounts($stored);
		delete_option('rup_gb_gist_accounts');
		rup_gb_gist_clear_cache();
	}

	/* Never persist tokens back into this raw textarea option. */
	return '';
}

function rup_gb_gist_render_settings_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}

	$config_accounts = rup_gb_gist_get_config_accounts();
	$db_accounts     = rup_gb_gist_get_database_accounts();
	$has_openssl     = function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Gist Embedded', 'rup-gist-embedded'); ?></h1>
		<?php if (isset($_GET['rup_gb_gist_saved'])) : ?>
			<?php if ($_GET['rup_gb_gist_saved'] === '1') : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e('Settings saved. Database token profiles were updated securely.', 'rup-gist-embedded'); ?></p></div>
			<?php else : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e('No token profiles were changed. Add lines in alias=token format to save database profiles.', 'rup-gist-embedded'); ?></p></div>
			<?php endif; ?>
		<?php endif; ?>
		<div class="notice notice-warning inline">
			<p><strong><?php esc_html_e('Private content warning:', 'rup-gist-embedded'); ?></strong> <?php esc_html_e('Private gists and private repository files embedded with this plugin will be visible to anyone who can view the page. Your GitHub token remains server-side, but the rendered code is public on that page.', 'rup-gist-embedded'); ?></p>
		</div>

		<h2><?php esc_html_e('Recommended wp-config.php setup', 'rup-gist-embedded'); ?></h2>
		<p><?php esc_html_e('For the best security, define tokens in wp-config.php instead of saving them in the database:', 'rup-gist-embedded'); ?></p>
		<pre class="code" style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:900px;overflow:auto;"><code>define( 'RUP_GB_GIST_GITHUB_TOKENS', [
    'personal' =&gt; 'github_pat_xxx',
    'work'     =&gt; 'github_pat_yyy',
] );</code></pre>
		<p class="description"><?php esc_html_e('You can also use a string format: personal=github_pat_xxx on one line, work=github_pat_yyy on the next. wp-config profiles override database profiles with the same alias.', 'rup-gist-embedded'); ?></p>

		<h2><?php esc_html_e('Available profiles', 'rup-gist-embedded'); ?></h2>
		<?php if (empty($config_accounts) && empty($db_accounts)) : ?>
			<p><?php esc_html_e('No GitHub credential profiles configured yet.', 'rup-gist-embedded'); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px;">
				<thead><tr><th><?php esc_html_e('Alias', 'rup-gist-embedded'); ?></th><th><?php esc_html_e('Storage', 'rup-gist-embedded'); ?></th></tr></thead>
				<tbody>
				<?php foreach ($config_accounts as $alias => $token) : ?>
					<tr><td><code><?php echo esc_html((string) $alias); ?></code></td><td><?php esc_html_e('wp-config.php constant', 'rup-gist-embedded'); ?></td></tr>
				<?php endforeach; ?>
				<?php foreach ($db_accounts as $alias => $token) : ?>
					<?php if (isset($config_accounts[$alias])) { continue; } ?>
					<tr><td><code><?php echo esc_html((string) $alias); ?></code></td><td><?php echo $has_openssl ? esc_html__('Encrypted database option', 'rup-gist-embedded') : esc_html__('Database option', 'rup-gist-embedded'); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="rup_gb_gist_save_settings">
			<?php wp_nonce_field('rup_gb_gist_save_settings'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="rup_gb_gist_accounts_raw"><?php esc_html_e('Add or replace database profiles', 'rup-gist-embedded'); ?></label></th>
					<td>
						<textarea id="rup_gb_gist_accounts_raw" name="rup_gb_gist_accounts_raw" class="large-text code" rows="6" autocomplete="off" spellcheck="false" placeholder="personal=github_pat_...&#10;work=github_pat_..."></textarea>
						<p class="description"><?php esc_html_e('Add one profile per line as alias=token. Tokens are not shown again after saving. Existing aliases are replaced only when you submit a new token for that alias.', 'rup-gist-embedded'); ?></p>
						<p class="description"><?php esc_html_e('Encrypted database profiles are stored in the WordPress option rup_gb_gist_accounts_secure. You should not see the raw token value in the database.', 'rup-gist-embedded'); ?></p>
						<?php if (!$has_openssl) : ?>
							<p class="description" style="color:#b32d2e;"><?php esc_html_e('PHP OpenSSL is unavailable, so database token encryption cannot be used. wp-config.php constants are strongly recommended.', 'rup-gist-embedded'); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e('Clear database profiles', 'rup-gist-embedded'); ?></th>
					<td>
						<label><input type="checkbox" name="rup_gb_gist_clear_accounts" value="1"> <?php esc_html_e('Delete all database-stored GitHub token profiles', 'rup-gist-embedded'); ?></label>
						<p class="description"><?php esc_html_e('This does not affect profiles defined in wp-config.php.', 'rup-gist-embedded'); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
