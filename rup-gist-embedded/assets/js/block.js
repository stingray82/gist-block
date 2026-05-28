const { registerBlockType } = wp.blocks;
const { TextControl, PanelBody, SelectControl, Spinner, ToggleControl, Button, Notice } = wp.components;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { useEffect, useState } = wp.element;
const ServerSideRender = wp.serverSideRender;
const apiFetch = wp.apiFetch;
const el = wp.element.createElement;

const githubIcon = el(
	'svg',
	{ viewBox: '0 0 24 24', width: 24, height: 24, fill: 'currentColor' },
	el('path', {
		d: 'M12 .5C5.65.5.5 5.65.5 12a11.5 11.5 0 008 10.93c.58.1.79-.25.79-.56v-2.17c-3.26.71-3.95-1.39-3.95-1.39-.53-1.34-1.3-1.7-1.3-1.7-1.06-.73.08-.72.08-.72 1.18.08 1.8 1.2 1.8 1.2 1.04 1.78 2.74 1.27 3.4.97.1-.75.4-1.27.72-1.56-2.6-.3-5.34-1.3-5.34-5.78 0-1.28.46-2.33 1.2-3.15-.12-.3-.52-1.52.12-3.16 0 0 .98-.31 3.2 1.2a11.08 11.08 0 015.82 0c2.22-1.5 3.2-1.2 3.2-1.2.64 1.64.24 2.86.12 3.16.75.82 1.2 1.87 1.2 3.15 0 4.5-2.75 5.47-5.37 5.77.42.36.8 1.08.8 2.18v3.23c0 .31.2.67.8.56A11.5 11.5 0 0023.5 12C23.5 5.65 18.35.5 12 .5z'
	})
);

function RupGbGistEdit(props) {
	const attrs = props.attributes;
	const [files, setFiles] = useState([]);
	const [revisions, setRevisions] = useState([]);
	const [loadingMeta, setLoadingMeta] = useState(false);
	const [cacheCleared, setCacheCleared] = useState(false);

	function loadMeta(url) {
		if (!url) {
			setFiles([]);
			setRevisions([]);
			return;
		}

		setLoadingMeta(true);

		apiFetch({
			path: '/rup-gb/v1/gist-meta?url=' + encodeURIComponent(url)
		}).then(function(response) {
			const foundFiles = response && response.files ? response.files : [];
			const foundRevisions = response && response.revisions ? response.revisions : [];

			setFiles(foundFiles);
			setRevisions(foundRevisions);

			if (foundFiles.length && (!attrs.file || foundFiles.indexOf(attrs.file) === -1)) {
				props.setAttributes({ file: foundFiles[0] });
			}
		}).catch(function() {
			setFiles([]);
			setRevisions([]);
		}).finally(function() {
			setLoadingMeta(false);
		});
	}

	useEffect(function() {
		const timer = setTimeout(function() {
			loadMeta(attrs.url);
		}, 400);

		return function() {
			clearTimeout(timer);
		};
	}, [attrs.url]);

	const fileOptions = files.length
		? files.map(function(file) {
			return { label: file, value: file };
		})
		: [{ label: 'No files found', value: '' }];

	const revisionOptions = [{ label: 'Latest', value: 'latest' }].concat(revisions);

	return el(
		'div',
		useBlockProps(),
		el(
			InspectorControls,
			{},
			el(
				PanelBody,
				{ title: 'Gist Settings', initialOpen: true },
				el(TextControl, {
					label: 'GitHub Gist URL',
					value: attrs.url,
					placeholder: 'https://gist.github.com/user/gistid',
					onChange: value => props.setAttributes({ url: value, file: '', revision: 'latest' })
				}),
				loadingMeta
					? el('p', {}, el(Spinner, {}), ' Loading Gist metadata…')
					: [
						el(SelectControl, {
							key: 'file',
							label: 'File to display',
							value: attrs.file,
							options: fileOptions,
							onChange: value => props.setAttributes({ file: value })
						}),
						el(SelectControl, {
							key: 'revision',
							label: 'Revision',
							value: attrs.revision || 'latest',
							options: revisionOptions,
							onChange: value => props.setAttributes({ revision: value })
						})
					],
				el(TextControl, {
					label: 'Friendly title/name',
					value: attrs.title,
					placeholder: 'Cloudflare WAF Rule Builder',
					onChange: value => props.setAttributes({ title: value })
				}),
				el(ToggleControl, {
					label: 'Show copy code button',
					checked: attrs.show_copy !== false,
					onChange: value => props.setAttributes({ show_copy: value })
				}),
				el(TextControl, {
					label: 'Raw link CSS classes',
					value: attrs.raw_link_class,
					placeholder: 'wp-block-button__link wp-element-button',
					onChange: value => props.setAttributes({ raw_link_class: value })
				}),
				el(TextControl, {
					label: 'Outer wrapper CSS classes',
					value: attrs.wrapper_class,
					placeholder: 'my-gist-wrapper alignwide',
					onChange: value => props.setAttributes({ wrapper_class: value })
				}),
				el(TextControl, {
					label: 'Max code height in pixels',
					value: attrs.max_height,
					placeholder: '400',
					onChange: value => props.setAttributes({ max_height: value })
				}),
				el(TextControl, {
					label: 'Code font size in pixels',
					value: attrs.font_size,
					placeholder: '13',
					onChange: value => props.setAttributes({ font_size: value })
				}),
				el(SelectControl, {
					label: 'Font',
					value: attrs.font,
					options: [
						{ label: 'Monospace', value: 'mono' },
						{ label: 'GitHub default', value: 'github' },
						{ label: 'Site font', value: 'site' },
						{ label: 'System sans-serif', value: 'system' },
						{ label: 'Custom font stack', value: 'custom' }
					],
					onChange: value => props.setAttributes({ font: value })
				}),
				attrs.font === 'custom'
					? el(TextControl, {
						label: 'Custom font stack',
						value: attrs.custom_font,
						placeholder: '"Inter", Arial, sans-serif',
						onChange: value => props.setAttributes({ custom_font: value })
					})
					: null,
				el(Button, {
					variant: 'secondary',
					onClick: function() {
						apiFetch({ path: '/rup-gb/v1/gist-cache', method: 'DELETE' }).then(function() {
							setCacheCleared(true);
							loadMeta(attrs.url);
							setTimeout(function() { setCacheCleared(false); }, 2500);
						});
					}
				}, 'Clear Gist Cache'),
				cacheCleared ? el(Notice, { status: 'success', isDismissible: false }, 'Gist cache cleared.') : null
			)
		),
		attrs.url && attrs.file
			? el(ServerSideRender, {
				block: 'rup-gb/github-gist',
				attributes: attrs
			})
			: el(
				'div',
				{ className: 'rup-gb-gist-placeholder' },
				el('div', { className: 'rup-gb-gist-placeholder-icon' }, githubIcon),
				el('strong', {}, 'GitHub Gist'),
				attrs.url
					? el('p', {}, loadingMeta ? 'Loading Gist metadata…' : 'Choose a file in the block settings to preview the Gist.')
					: el('p', {}, 'Add a GitHub Gist URL in the block settings.')
			)
	);
}

registerBlockType('rup-gb/github-gist', {
	apiVersion: 3,
	title: 'GitHub Gist',
	icon: githubIcon,
	category: 'embed',
	attributes: {
		url: { type: 'string', default: '' },
		file: { type: 'string', default: '' },
		revision: { type: 'string', default: 'latest' },
		title: { type: 'string', default: '' },
		raw_link_class: { type: 'string', default: '' },
		wrapper_class: { type: 'string', default: '' },
		max_height: { type: 'string', default: '400' },
		font: { type: 'string', default: 'mono' },
		custom_font: { type: 'string', default: '' },
		font_size: { type: 'string', default: '13' },
		show_copy: { type: 'boolean', default: true }
	},
	edit: RupGbGistEdit,
	save: function() {
		return null;
	}
});
