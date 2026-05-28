# GitHub Gist Embed — Usage Guide

## Overview

The **RUP GitHub Gist Embed** plugin allows you to embed individual GitHub Gist files into WordPress using:

- A Gutenberg block
- A shortcode
- Native GitHub Gist rendering
- Optional revision/version selection
- Cached Gist metadata
- Optional copy-to-clipboard support
- Custom fonts and styling
- Friendly titles
- Scrollable code areas

---

# Gutenberg Block Usage

## Add the Block

Search for:

```text
GitHub Gist
```

inside the block inserter.

---

# Block Options

## GitHub Gist URL

Paste a standard GitHub Gist URL.

Example:

```text
https://gist.github.com/stingray82/72465999b38b36a9ca3b86f0c4467f20
```

Or a direct file URL:

```text
https://gist.github.com/stingray82/1df73e9ac874f2fdc5488a21e10ca896#file-template-sales-page-2026-05-17-json
```

Revision URLs are also supported:

```text
https://gist.github.com/stingray82/c3f2c047d12660d09462c4626205db36/c2a170078a487fea5047ef59e7527397697f476f
```

---

## File to Display

After entering the Gist URL, the plugin automatically loads the files from the Gist.

The block displays individual files only. If no file has been manually selected, the first file returned by GitHub is selected automatically.

---

## Revision

Choose which Gist revision/version to display.

Default:

```text
Latest
```

When a pinned revision is selected, the plugin uses the native GitHub revision embed URL format:

```text
https://gist.github.com/user/gist-id/revision.js
```

---

## Friendly Title / Name

Overrides the displayed filename.

Example:

```text
Cloudflare WAF Rules
```

---

## Show Copy Code Button

Enable or disable the copy-to-clipboard button.

Default:

```text
Enabled
```

---

## Raw Link CSS Classes

Adds custom CSS classes to the “view raw” link.

Example:

```text
wp-block-button__link wp-element-button
```

---

## Outer Wrapper CSS Classes

Adds custom classes to the outer wrapper.

Useful for alignment and custom layouts.

Example:

```text
alignwide my-custom-gist
```

---

## Max Code Height (px)

Controls the maximum visible height before vertical scrolling appears.

Default:

```text
400
```

Example:

```text
600
```

---

## Code Font Size (px)

Controls code font size.

Default:

```text
13
```

The current testing build allows a wider range for testing, from approximately:

```text
6–96
```

---

## Font Options

Available fonts:

| Option | Description |
|---|---|
| Monospace | Default coding font |
| GitHub Default | GitHub-like styling |
| Site Font | Uses your theme font |
| System Sans Serif | Uses browser system UI fonts |
| Custom Font Stack | Your own CSS font-family |

---

## Clear Gist Cache

The block includes a **Clear Gist Cache** button.

This refreshes cached Gist metadata, including:

- File list
- Revision list
- Gist API metadata

The rendered Gist itself continues to use GitHub’s native embed script.

---

# Shortcode Usage

## Basic Usage

```text
[gist url="https://gist.github.com/stingray82/72465999b38b36a9ca3b86f0c4467f20"]
```

---

# Shortcode Options

## url

GitHub Gist URL.

Required.

Example:

```text
url="https://gist.github.com/stingray82/72465999b38b36a9ca3b86f0c4467f20"
```

---

## file

Specify a file manually.

Example:

```text
file="example.php"
```

---

## revision

Choose a specific Gist revision.

Default:

```text
latest
```

Example:

```text
revision="latest"
```

Pinned revision example:

```text
revision="c2a170078a487fea5047ef59e7527397697f476f"
```

---

## title

Friendly display title.

Example:

```text
title="Cloudflare WAF Rules"
```

---

## show_copy

Enable or disable copy button.

Values:

```text
1
0
true
false
```

Example:

```text
show_copy="0"
```

---

## max_height

Maximum code area height.

Default:

```text
400
```

Example:

```text
max_height="600"
```

---

## font_size

Code font size in pixels.

Default:

```text
13
```

Example:

```text
font_size="14"
```

---

## font

Available values:

```text
mono
github
site
system
custom
```

Example:

```text
font="site"
```

---

## custom_font

Custom CSS font-family stack.

Example:

```text
custom_font='"Inter", Arial, sans-serif'
```

---

## raw_link_class

Custom CSS classes for the “view raw” link.

Example:

```text
raw_link_class="wp-block-button__link"
```

Multiple classes are supported:

```text
raw_link_class="wp-block-button__link wp-element-button"
```

---

## wrapper_class

Custom CSS classes for the outer wrapper.

Example:

```text
wrapper_class="alignwide custom-gist"
```

---

# Full Shortcode Example

```text
[gist
	url="https://gist.github.com/stingray82/1df73e9ac874f2fdc5488a21e10ca896#file-template-sales-page-2026-05-17-json"
	title="Sales Page Template"
	revision="latest"
	show_copy="1"
	max_height="500"
	font="mono"
	font_size="13"
	raw_link_class="wp-block-button__link"
	wrapper_class="alignwide my-gist"
]
```

Pinned revision example:

```text
[gist
	url="https://gist.github.com/stingray82/c3f2c047d12660d09462c4626205db36"
	file="example.php"
	revision="c2a170078a487fea5047ef59e7527397697f476f"
	title="Pinned Gist Revision"
]
```

---

# Features

- Native GitHub Gist rendering
- Gutenberg block support
- Shortcode support
- Individual file selection
- Revision/version selection
- Copy code button
- Horizontal scrolling for long lines
- Vertical scrolling for long files
- Friendly titles
- Custom CSS classes
- Multiple font options
- Accessible markup
- Lazy-loaded iframe embeds
- Mobile responsive rendering
- Cached Gist metadata
- Manual cache clearing from the block sidebar

---

# Caching Behaviour

The plugin caches Gist metadata for performance.

Cached items include:

| Item | Cached |
|---|---|
| Gist file list | Yes |
| Gist revision list | Yes |
| Gist API metadata | Yes |
| GitHub native rendered code | No |
| GitHub `.js` embed output | No |

Default cache duration:

```text
1 week
```

This keeps the block controls fast while allowing the frontend render to stay as close as possible to GitHub’s native embed behaviour.

---

# Accessibility Notes

The plugin includes:

- Keyboard accessible copy button
- Proper iframe titles
- Semantic figure/figcaption markup
- Accessible SVG icons
- Responsive scrolling support

---

# Performance Notes

The plugin:

- Uses GitHub’s native Gist rendering
- Caches Gist metadata
- Lazy-loads iframes
- Auto-resizes embeds dynamically

For pages with many Gists, a future enhancement could be an optional “click to load” mode.

---

# Example URLs

## Standard Gist

```text
https://gist.github.com/stingray82/72465999b38b36a9ca3b86f0c4467f20
```

## File-Specific Gist

```text
https://gist.github.com/stingray82/1df73e9ac874f2fdc5488a21e10ca896#file-template-sales-page-2026-05-17-json
```

## Revision-Specific Gist

```text
https://gist.github.com/stingray82/c3f2c047d12660d09462c4626205db36/c2a170078a487fea5047ef59e7527397697f476f
```