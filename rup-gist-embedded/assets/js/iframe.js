(function() {
	var currentScript = document.currentScript || (function() {
		var scripts = document.getElementsByTagName('script');
		return scripts[scripts.length - 1];
	})();
	var instance = currentScript ? currentScript.getAttribute('data-instance') : '';
	var lastHeight = 0;
	var attempts = 0;

	function resetScrollLeft() {
		window.scrollTo(0, window.scrollY || 0);
		document.documentElement.scrollLeft = 0;
		document.body.scrollLeft = 0;
	}

	function getCodeText() {
		var lines = Array.prototype.slice.call(document.querySelectorAll('.blob-code-inner'));

		if (!lines.length) {
			var pre = document.querySelector('pre');
			return pre ? pre.innerText : '';
		}

		return lines.map(function(line) {
			return line.innerText.replace(/\n$/, '');
		}).join('\n');
	}

	function sendCode() {
		parent.postMessage({
			type: 'rupGbGistCode',
			instance: instance,
			code: getCodeText()
		}, '*');
	}

	function getHeight() {
		var gist = document.querySelector('.gist');
		var file = document.querySelector('.gist-file');

		return Math.max(
			gist ? gist.offsetHeight : 0,
			file ? file.offsetHeight : 0,
			document.body ? document.body.offsetHeight : 0,
			document.documentElement ? document.documentElement.offsetHeight : 0
		);
	}

	function sendHeight(force) {
		var height = getHeight();

		if (!height) {
			return;
		}

		if (force || Math.abs(height - lastHeight) > 2) {
			lastHeight = height;
			resetScrollLeft();
			sendCode();

			parent.postMessage({
				type: 'rupGbGistResize',
				instance: instance,
				height: height
			}, '*');
		}
	}

	function start() {
		resetScrollLeft();
		sendHeight(true);
		sendCode();

		var timer = setInterval(function() {
			attempts++;
			resetScrollLeft();
			sendHeight(false);
			sendCode();

			if (attempts > 12) {
				clearInterval(timer);
			}
		}, 250);

		if ('ResizeObserver' in window && document.body) {
			new ResizeObserver(function() {
				sendHeight(false);
				sendCode();
			}).observe(document.body);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}

	window.addEventListener('load', function() {
		resetScrollLeft();
		sendHeight(true);
		sendCode();
	});
})();
