<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

function rup_gb_gist_svg_icon(): string {
	return '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" focusable="false" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12a11.5 11.5 0 008 10.93c.58.1.79-.25.79-.56v-2.17c-3.26.71-3.95-1.39-3.95-1.39-.53-1.34-1.3-1.7-1.3-1.7-1.06-.73.08-.72.08-.72 1.18.08 1.8 1.2 1.8 1.2 1.04 1.78 2.74 1.27 3.4.97.1-.75.4-1.27.72-1.56-2.6-.3-5.34-1.3-5.34-5.78 0-1.28.46-2.33 1.2-3.15-.12-.3-.52-1.52.12-3.16 0 0 .98-.31 3.2 1.2a11.08 11.08 0 015.82 0c2.22-1.5 3.2-1.2 3.2-1.2.64 1.64.24 2.86.12 3.16.75.82 1.2 1.87 1.2 3.15 0 4.5-2.75 5.47-5.37 5.77.42.36.8 1.08.8 2.18v3.23c0 .31.2.67.8.56A11.5 11.5 0 0023.5 12C23.5 5.65 18.35.5 12 .5z"/></svg>';
}

function rup_gb_gist_copy_icon(): string {
	return '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" focusable="false" aria-hidden="true"><path d="M16 1H4a2 2 0 0 0-2 2v12h2V3h12V1zm3 4H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm0 16H8V7h11v14z"/></svg>';
}

function rup_gb_gist_font_css(array $gist): string {
	if ($gist['font'] === 'site') {
		return 'font-family: inherit !important;';
	}

	if ($gist['font'] === 'system') {
		return 'font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;';
	}

	if ($gist['font'] === 'mono') {
		return 'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace !important;';
	}

	if ($gist['font'] === 'custom' && !empty($gist['custom_font'])) {
		return 'font-family: ' . esc_html($gist['custom_font']) . ' !important;';
	}

	return '';
}

function rup_gb_gist_build_script_src(array $gist): string {
	if ($gist['revision'] !== 'latest') {
		$src = sprintf(
			'https://gist.github.com/%s/%s/%s.js',
			rawurlencode($gist['user']),
			rawurlencode($gist['id']),
			rawurlencode($gist['revision'])
		);
	} else {
		$src = sprintf(
			'https://gist.github.com/%s/%s.js',
			rawurlencode($gist['user']),
			rawurlencode($gist['id'])
		);
	}

	return add_query_arg('file', rawurlencode($gist['file']), $src);
}


function rup_gb_gist_iframe_js(): string {
	$path = RUP_GB_GIST_PATH . 'assets/js/iframe.js';

	if (!is_readable($path)) {
		return '';
	}

	$script = file_get_contents($path);

	return is_string($script) ? $script : '';
}

function rup_gb_gist_iframe_srcdoc(array $gist, string $src, string $instance_id): string {
	$font_css = rup_gb_gist_font_css($gist);
	$font_size = absint($gist['font_size']);

	return '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
	html, body { margin: 0; padding: 0; background: transparent; overflow: hidden; width: 100%; scrollbar-gutter: stable; }
	::-webkit-scrollbar { width: 12px; height: 12px; }
	::-webkit-scrollbar-thumb { background: #8c959f; border-radius: 999px; border: 3px solid transparent; background-clip: content-box; }
	::-webkit-scrollbar-track { background: #f6f8fa; }
	.gist { margin: 0 !important; width: 100%; }
	.gist-file { margin: 0 !important; border-radius: 0 !important; border-left: 0 !important; border-right: 0 !important; border-bottom: 0 !important; width: 100%; }
	.gist-meta { display: none !important; }
	.gist .gist-data { max-height: ' . absint($gist['max_height']) . 'px !important; overflow: auto !important; }
	.gist .blob-wrapper { overflow: visible !important; }
	.gist table { width: max-content !important; min-width: 100% !important; }
	.gist, .gist *, .gist table, .gist tbody, .gist tr, .gist td, .gist pre, .gist .blob-code, .gist .blob-code-inner, .gist .blob-num { font-size: ' . $font_size . 'px !important; line-height: 1.45 !important; }
	.gist .blob-code, .gist .blob-code-inner, .gist pre { white-space: pre !important; word-break: normal !important; overflow-wrap: normal !important; font-size: ' . $font_size . 'px !important; line-height: 1.45 !important; }
	.gist .blob-num { min-width: 38px !important; }
	' . ($font_css ? '.gist, .gist * { ' . $font_css . ' }' : '') . '
	@media (max-width: 640px) { .gist, .gist *, .gist table, .gist tbody, .gist tr, .gist td, .gist pre, .gist .blob-code, .gist .blob-code-inner, .gist .blob-num { font-size: max(10px, calc(' . $font_size . 'px - 1px)) !important; } }
</style>
</head>
<body>
<script src="' . esc_url($src) . '"></script>
<script data-instance="' . esc_attr($instance_id) . '">
' . rup_gb_gist_iframe_js() . '
</script>
</body>
</html>';
}

function rup_gb_render_gist_embed(array $atts = []): string {
	$gist = rup_gb_gist_parse_atts($atts);

	if (empty($gist['user']) || empty($gist['id']) || empty($gist['file']) || !preg_match('/^[a-f0-9]+$/i', $gist['id'])) {
		return '<p class="rup-gb-gist-error">Invalid GitHub Gist embed.</p>';
	}

	$instance_id = 'rup-gb-gist-' . wp_generate_uuid4();
	$src         = rup_gb_gist_build_script_src($gist);

	if ($gist['revision'] !== 'latest') {
		$gist_url = sprintf('https://gist.github.com/%s/%s/%s#file-%s', rawurlencode($gist['user']), rawurlencode($gist['id']), rawurlencode($gist['revision']), sanitize_title($gist['file']));
	} else {
		$gist_url = sprintf('https://gist.github.com/%s/%s#file-%s', rawurlencode($gist['user']), rawurlencode($gist['id']), sanitize_title($gist['file']));
	}

	$display_name   = $gist['title'] ?: $gist['file'];
	$raw_link_class = $gist['raw_link_class'] ?: 'rup-gb-gist-embed__raw-link';
	$outer_classes  = trim('wp-block-rup-gb-github-gist rup-gb-gist-wrap ' . $gist['wrapper_class']);

	$copy_button = '';
	if ($gist['show_copy']) {
		$copy_button = sprintf(
			'<button type="button" class="rup-gb-gist-copy" aria-label="%1$s" title="%1$s" data-rup-gb-gist-copy="%2$s">%3$s</button>',
			esc_attr__('Copy code', 'rup-gist-embded'),
			esc_attr($instance_id),
			rup_gb_gist_copy_icon()
		);
	}

	$srcdoc = rup_gb_gist_iframe_srcdoc($gist, $src, $instance_id);

	return sprintf(
		'<div class="%11$s">
			<figure class="rup-gb-gist-embed rup-gb-gist-font-%10$s" data-rup-gb-gist-instance="%1$s" style="--rup-gb-gist-max-height:%2$dpx;" aria-label="%3$s">
				<div class="rup-gb-gist-embed__header">
					<span class="rup-gb-gist-embed__icon" aria-hidden="true">%4$s</span>
					%9$s
					<strong>%5$s</strong>
				</div>
				<iframe class="rup-gb-gist-embed__frame" title="%3$s" srcdoc="%6$s" loading="lazy" scrolling="yes" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"></iframe>
				<figcaption class="rup-gb-gist-embed__footer">
					<span>%5$s hosted with ❤ by GitHub</span>
					<a class="%7$s" href="%8$s" target="_blank" rel="noopener noreferrer">view raw</a>
				</figcaption>
			</figure>
		</div>',
		esc_attr($instance_id),
		esc_attr((string) $gist['max_height']),
		esc_attr($display_name),
		rup_gb_gist_svg_icon(),
		esc_html($display_name),
		esc_attr($srcdoc),
		esc_attr($raw_link_class),
		esc_url($gist_url),
		$copy_button,
		esc_attr($gist['font']),
		esc_attr($outer_classes)
	);
}

add_shortcode('gist', 'rup_gb_render_gist_embed');
