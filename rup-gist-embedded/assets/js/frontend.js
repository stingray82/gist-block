window.rupGbGistCodeStore = window.rupGbGistCodeStore || {};

function rupGbGistCssEscape(value) {
	if (window.CSS && typeof window.CSS.escape === 'function') {
		return window.CSS.escape(value);
	}

	return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
}

window.addEventListener('message', function(event) {
	if (!event.data || !event.data.type) {
		return;
	}

	const instance = String(event.data.instance || '');

	if (!instance) {
		return;
	}

	if (event.data.type === 'rupGbGistCode') {
		window.rupGbGistCodeStore[instance] = String(event.data.code || '');
		return;
	}

	if (event.data.type !== 'rupGbGistResize') {
		return;
	}

	const height = parseInt(event.data.height, 10);

	if (!height || height < 1 || height > 50000) {
		return;
	}

	document
		.querySelectorAll('.rup-gb-gist-embed[data-rup-gb-gist-instance="' + rupGbGistCssEscape(instance) + '"]')
		.forEach(function(embed) {
			const frame = embed.querySelector('iframe');
			const maxHeight = parseInt(
				getComputedStyle(embed).getPropertyValue('--rup-gb-gist-max-height'),
				10
			) || 400;

			if (!frame) {
				return;
			}

			frame.style.height = Math.min(height, maxHeight) + 'px';
		});
});

document.addEventListener('click', async function(event) {
	const button = event.target.closest('.rup-gb-gist-copy');

	if (!button) {
		return;
	}

	const instance = button.getAttribute('data-rup-gb-gist-copy');

	if (!instance) {
		return;
	}

	const code = window.rupGbGistCodeStore && window.rupGbGistCodeStore[instance]
		? window.rupGbGistCodeStore[instance]
		: '';

	if (!code) {
		return;
	}

	try {
		await navigator.clipboard.writeText(code);

		const originalTitle = button.title || 'Copy code';

		button.classList.add('is-copied');
		button.title = 'Copied!';
		button.setAttribute('aria-label', 'Copied!');

		setTimeout(function() {
			button.classList.remove('is-copied');
			button.title = originalTitle;
			button.setAttribute('aria-label', originalTitle);
		}, 2000);
	} catch (err) {
		console.error('Failed to copy Gist code', err);
	}
});
