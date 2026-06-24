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


function rup_gb_gist_code_iframe_srcdoc(array $gist, string $code, string $instance_id): string {
	$font_css    = rup_gb_gist_font_css($gist);
	$font_size   = absint($gist['font_size']);
	$encoded     = base64_encode($code);
	$encoded_json = wp_json_encode($encoded);
	$instance_json = wp_json_encode($instance_id);

	return '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Security-Policy" content="default-src &#039;none&#039;; script-src &#039;unsafe-inline&#039;; style-src &#039;unsafe-inline&#039;; base-uri &#039;none&#039;; form-action &#039;none&#039;;">
<style>
	html, body { margin: 0; padding: 0; background: #fff; overflow: hidden; width: 100%; scrollbar-gutter: stable; }
	::-webkit-scrollbar { width: 12px; height: 12px; }
	::-webkit-scrollbar-thumb { background: #8c959f; border-radius: 999px; border: 3px solid transparent; background-clip: content-box; }
	::-webkit-scrollbar-track { background: #f6f8fa; }
	.gist-data { max-height: ' . absint($gist['max_height']) . 'px; overflow: auto; }
	table { border-spacing: 0; border-collapse: collapse; width: max-content; min-width: 100%; }
	td { vertical-align: top; font-size: ' . $font_size . 'px; line-height: 1.45; }
	.blob-num { width: 1%; min-width: 38px; padding: 0 10px; color: #57606a; text-align: right; border-right: 1px solid #d8dee4; user-select: none; }
	.blob-code { padding: 0 10px; }
	pre { margin: 0; white-space: pre; word-break: normal; overflow-wrap: normal; font-size: ' . $font_size . 'px; line-height: 1.45; }
	' . ($font_css ? 'body, body * { ' . $font_css . ' }' : '') . '
</style>
</head>
<body>
<div class="gist-data"><table><tbody id="rup-gb-code-lines"></tbody></table></div>
<script>
(function(){
	var instance = ' . $instance_json . ';
	var encodedCode = ' . $encoded_json . ';
	var code = "";

	function decodeBase64Utf8(value) {
		try {
			var binary = atob(value || "");
			var bytes = new Uint8Array(binary.length);
			for (var i = 0; i < binary.length; i++) {
				bytes[i] = binary.charCodeAt(i);
			}
			if (window.TextDecoder) {
				return new TextDecoder("utf-8", { fatal: false }).decode(bytes);
			}
			var text = "";
			for (var j = 0; j < bytes.length; j++) {
				text += String.fromCharCode(bytes[j]);
			}
			return decodeURIComponent(escape(text));
		} catch (e) {
			return "";
		}
	}

	function renderCode() {
		var tbody = document.getElementById("rup-gb-code-lines");
		if (!tbody) { return; }

		code = decodeBase64Utf8(encodedCode);
		var lines = code.split("\n");
		var fragment = document.createDocumentFragment();

		for (var i = 0; i < lines.length; i++) {
			var tr = document.createElement("tr");
			var num = document.createElement("td");
			var cell = document.createElement("td");
			var pre = document.createElement("pre");

			num.className = "blob-num";
			num.textContent = String(i + 1);
			cell.className = "blob-code";
			pre.textContent = lines[i];

			cell.appendChild(pre);
			tr.appendChild(num);
			tr.appendChild(cell);
			fragment.appendChild(tr);
		}

		tbody.textContent = "";
		tbody.appendChild(fragment);
	}

	function send(){
		if (!parent) { return; }
		parent.postMessage({ type: "rupGbGistCode", instance: instance, code: code }, "*");
		parent.postMessage({ type: "rupGbGistResize", instance: instance, height: document.documentElement.scrollHeight }, "*");
	}

	renderCode();
	window.addEventListener("load", send);
	setTimeout(send, 50);
	setTimeout(send, 250);
})();
</script>
</body>
</html>';
}

function rup_gb_gist_render_shell(array $gist, string $instance_id, string $display_name, string $footer_text, string $link_url, string $srcdoc): string {
	$raw_link_class = $gist['raw_link_class'] ?: 'rup-gb-gist-embed__raw-link';
	$outer_classes  = trim('wp-block-rup-gb-github-gist rup-gb-gist-wrap ' . $gist['wrapper_class']);

	$copy_button = '';
	if ($gist['show_copy']) {
		$copy_button = sprintf(
			'<button type="button" class="rup-gb-gist-copy" aria-label="%1$s" title="%1$s" data-rup-gb-gist-copy="%2$s">%3$s</button>',
			esc_attr__('Copy code', 'rup-gist-embedded'),
			esc_attr($instance_id),
			rup_gb_gist_copy_icon()
		);
	}

	$footer = '';
	if (!empty($gist['show_footer'])) {
		$custom_footer_text = isset($gist['footer_text']) ? trim((string) $gist['footer_text']) : '';
		$footer_label = $custom_footer_text !== '' ? $custom_footer_text : $footer_text;
		$source_link = '';
		if (!empty($gist['show_source_link']) && $link_url !== '') {
			$source_link = sprintf(
				'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">view source</a>',
				esc_attr($raw_link_class),
				esc_url($link_url)
			);
		}
		$footer = sprintf(
			'<figcaption class="rup-gb-gist-embed__footer"><span>%1$s</span>%2$s</figcaption>',
			esc_html($footer_label),
			$source_link
		);
	}

	return sprintf(
		'<div class="%10$s">
			<figure class="rup-gb-gist-embed rup-gb-gist-font-%9$s" data-rup-gb-gist-instance="%1$s" style="--rup-gb-gist-max-height:%2$dpx;" aria-label="%3$s">
				<div class="rup-gb-gist-embed__header">
					<span class="rup-gb-gist-embed__icon" aria-hidden="true">%4$s</span>
					%8$s
					<strong>%5$s</strong>
				</div>
				<iframe class="rup-gb-gist-embed__frame" title="%3$s" srcdoc="%6$s" loading="lazy" scrolling="yes" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"></iframe>
				%7$s
			</figure>
		</div>',
		esc_attr($instance_id),
		esc_attr((string) $gist['max_height']),
		esc_attr($display_name),
		rup_gb_gist_svg_icon(),
		esc_html($display_name),
		esc_attr($srcdoc),
		$footer,
		$copy_button,
		esc_attr($gist['font']),
		esc_attr($outer_classes)
	);
}

function rup_gb_render_api_code_embed(array $gist): string {
	if ($gist['source'] === 'repo') {
		$file = rup_gb_gist_fetch_repo_file($gist['repo_owner'], $gist['repo_name'], $gist['repo_path'], $gist['repo_ref'], $gist['account']);
		if (empty($file['content'])) {
			return '<p class="rup-gb-gist-error">Unable to fetch GitHub repository file.</p>';
		}
		$display_name = $gist['title'] ?: $file['path'];
		$link_url     = $file['html_url'] ?: sprintf('https://github.com/%s/%s', rawurlencode($gist['repo_owner']), rawurlencode($gist['repo_name']));
		$footer_text  = sprintf('%s/%s repository file', $gist['repo_owner'], $gist['repo_name']);
		$code         = $file['content'];
	} else {
		$data = rup_gb_gist_fetch_gist($gist['id'], $gist['account'], $gist['revision']);
		if (empty($data['files'][$gist['file']]['content'])) {
			return '<p class="rup-gb-gist-error">Unable to fetch GitHub Gist file.</p>';
		}
		$display_name = $gist['title'] ?: $gist['file'];
		$link_url     = $data['html_url'] ?? sprintf('https://gist.github.com/%s/%s', rawurlencode($gist['user']), rawurlencode($gist['id']));
		$footer_text  = 'GitHub Gist rendered from the API';
		$code         = (string) $data['files'][$gist['file']]['content'];
	}

	$instance_id = 'rup-gb-gist-' . wp_generate_uuid4();
	$srcdoc      = rup_gb_gist_code_iframe_srcdoc($gist, $code, $instance_id);

	return rup_gb_gist_render_shell($gist, $instance_id, $display_name, $footer_text, $link_url, $srcdoc);
}

function rup_gb_render_gist_embed(array $atts = []): string {
	$gist = rup_gb_gist_parse_atts($atts);

	if ($gist['source'] === 'repo') {
		if (empty($gist['repo_owner']) || empty($gist['repo_name']) || empty($gist['repo_path'])) {
			return '<p class="rup-gb-gist-error">Invalid GitHub repository file embed.</p>';
		}
		return rup_gb_render_api_code_embed($gist);
	}

	if (empty($gist['user']) || empty($gist['id']) || empty($gist['file']) || !preg_match('/^[a-f0-9]+$/i', $gist['id'])) {
		return '<p class="rup-gb-gist-error">Invalid GitHub Gist embed.</p>';
	}

	if (!empty($gist['account'])) {
		return rup_gb_render_api_code_embed($gist);
	}

	$instance_id = 'rup-gb-gist-' . wp_generate_uuid4();
	$src         = rup_gb_gist_build_script_src($gist);

	if ($gist['revision'] !== 'latest') {
		$gist_url = sprintf('https://gist.github.com/%s/%s/%s#file-%s', rawurlencode($gist['user']), rawurlencode($gist['id']), rawurlencode($gist['revision']), sanitize_title($gist['file']));
	} else {
		$gist_url = sprintf('https://gist.github.com/%s/%s#file-%s', rawurlencode($gist['user']), rawurlencode($gist['id']), sanitize_title($gist['file']));
	}

	$display_name = $gist['title'] ?: $gist['file'];
	$srcdoc       = rup_gb_gist_iframe_srcdoc($gist, $src, $instance_id);

	return rup_gb_gist_render_shell($gist, $instance_id, $display_name, $display_name . ' hosted with ❤ by GitHub', $gist_url, $srcdoc);

}

add_shortcode('gist', 'rup_gb_render_gist_embed');
