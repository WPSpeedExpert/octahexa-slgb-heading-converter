# OctaHexa SLGB Block Converter

Converts all custom SLGB blocks to native WordPress core blocks with proper HTML formatting:

- `slgb/h1-h6` → `core/heading` blocks
- `slgb/emph` → `core/paragraph` blocks
- `slgb/image` → `core/image` blocks
- `slgb/table` → `core/html` blocks with proper HTML tables
- `slgb/gb-subscribe` → `core/group` with paragraph and button
- `slgb/p-compare` → `core/columns` blocks
- `slgb/p-hints` → `core/html` blocks with tables
- `slgb/p-quote` → `core/quote` blocks
- `slgb/p-miniature` → `core/media-text` or `core/group` blocks
- `slgb/miniature` → `core/media-text` or `core/group` blocks
- `slgb/gb-cta` → `core/group` with heading and buttons
- `slgb/gb-emph` → `core/group` with preserved content
  

**Preserves CSS classes and styling attributes** to maintain your design while making content compatible with any WordPress theme.

---

## Plugin Details

- **Plugin Name:** OctaHexa SLGB Block Converter  
- **Author:** OctaHexa  
- **Version:** 2.2.18
- **GitHub URI:** https://github.com/WPSpeedExpert/octahexa-slgb-block-converter

---

## Requirements

- WordPress 5.0 or later
- PHP 7.4 or later
- User role: Administrator (for running the conversion)

---

## Installation

1. Download or clone this repository into your `/wp-content/plugins/` directory
2. Make sure the file is named: `octahexa-slgb-block-converter.php`
3. Activate the plugin from your WordPress admin panel (`Plugins → Installed Plugins`)

---

## Usage

1. Go to **Tools → Convert SLGB Blocks** in the WordPress admin
2. Click the **"Run Conversion"** button
3. The plugin will:
   - Scan all posts for SLGB custom blocks
   - Convert them to their native WordPress block equivalents
   - Preserve formatting, links, and content structure
   - Add CSS classes to help with styling
4. A success message will show how many posts were updated and which block types were converted

---

## CSS Styling

The plugin adds basic CSS to maintain the appearance of converted blocks, but you may want to add custom CSS to your theme for better styling. Each converted block type includes a specific class that you can target:

```css
/* Examples of CSS selectors for styling converted blocks */
.slgb-table-converted { /* Table styling */ }
.slgb-subscribe-converted { /* Subscribe form styling */ }
.slgb-compare-converted { /* Compare block styling */ }
.slgb-compare-column { /* Compare column styling */ }
.slgb-hints-converted { /* Hints table styling */ }
.slgb-quote-converted { /* Quote styling */ }
.slgb-miniature-converted { /* Miniature post styling */ }
.slgb-cta-converted { /* CTA block styling */ }
.slgb-gb-emph-converted { /* Emphasis block styling */ }
.slgb-emph { /* Regular emphasis styling */ }
```

---

## Notes

- This plugin **only affects posts** (`post_type = post`). If you need to include pages or custom post types, let us know
- Escaped Unicode characters (e.g., `\u003c` for `<`) are decoded properly
- Safe to deactivate and delete after running — it performs a one-time migration
- For complex blocks, the conversion attempts to maintain as much of the original structure as possible

---

## Disclaimer

Always **back up your database** before running any content modification plugin, especially when dealing with serialized or block content.

---

## Support

Need help or a custom variation? Reach out at [https://octahexa.com](https://octahexa.com) this repository into your `/wp-content/plugins/` directory.
2. Make sure the file is named: `octahexa-slgb-block-converter.php`
3. Activate the plugin from your WordPress admin panel (`Plugins → Installed Plugins`).

---

## Usage

1. Go to **Tools → Convert SLGB Blocks** in the WordPress admin.
2. Click the **"Run Conversion"** button.
3. The plugin will:
   - Scan all posts for SLGB blocks
   - Replace heading blocks (`slgb/h1`-`slgb/h6`) with native heading blocks:
     ```html
     <!-- wp:heading {"level":2} --><h2>Heading text</h2><!-- /wp:heading -->
     ```
   - Replace emphasis blocks (`slgb/emph`) with paragraph blocks:
     ```html
     <!-- wp:paragraph --><p><strong>Title</strong> Content text</p><!-- /wp:paragraph -->
     ```
4. A success message will show how many posts were updated and which block types were converted.

---

## Block Conversion Details

### Heading Blocks
- Format: `<!-- wp:slgb/h2 {"text":"Heading text", "className":"custom-class"} /-->`
- Converts to: `<!-- wp:heading {"level":2,"className":"custom-class"} --><h2 class="custom-class">Heading text</h2><!-- /wp:heading -->`

### Emphasis Blocks
- Format: `<!-- wp:slgb/emph {"title":"TL;DR","text":"Content text...","className":"highlight-box"} /-->`
- Converts to: `<!-- wp:paragraph {"className":"highlight-box"} --><p class="highlight-box"><strong>TL;DR</strong> Content text...</p><!-- /wp:paragraph -->`
- Note: If no class exists, a default `slgb-emph` class is added to help with styling

### Image Blocks
- Format: `<!-- wp:slgb/image {"data":{"id":"123","src":"image.jpg","width":"800","height":"600","alt":"Alt text"}} --><img src="image.jpg" width="800" height="600" alt="Alt text"/><!-- /wp:slgb/image -->`
- Converts to: `<!-- wp:image {"id":123,"sizeSlug":"full","width":800,"height":600} --><figure class="wp-block-image size-full"><img src="image.jpg" alt="Alt text" class="wp-image-123" width="800" height="600"/></figure><!-- /wp:image -->`
- Preserves links, captions, alignments, and CSS classes

### Table Blocks
- Converts `slgb/table` blocks to HTML tables wrapped in `core/html` blocks
- Preserves all table content, including HTML formatting within cells
- Adds `slgb-table-converted` class for styling

### Subscribe Blocks
- Converts `slgb/gb-subscribe` to a group with a paragraph and button
- Preserves title text and adds `slgb-subscribe-converted` class for styling

### Compare Blocks
- Converts `slgb/p-compare` with nested `slgb/p-compare-column` to `core/columns` blocks
- Preserves column titles and content
- Adds `slgb-compare-converted` and `slgb-compare-column` classes for styling

### Hints Blocks
- Converts `slgb/p-hints` blocks to HTML tables wrapped in `core/html` blocks
- Preserves the existing table structure with "DO" and "DO NOT" columns
- Adds `slgb-hints-converted` class for styling

### Quote Blocks
- Converts `slgb/p-quote` to `core/quote` blocks
- Preserves text, author, and featured status
- Adds `slgb-quote-converted` class for styling

### Miniature Blocks
- Converts `slgb/p-miniature` to `core/media-text` blocks (or `core/group` if no image)
- Preserves post image, title, link, and description
- Adds `slgb-miniature-converted` class for styling

### CTA Blocks
- Converts `slgb/gb-cta` to `core/group` blocks with heading and buttons
- Preserves title, description, button text, links, and "open in new tab" settings
- Adds `slgb-cta-converted` class for styling

### GB Emphasis Blocks
- Converts `slgb/gb-emph` to `core/group` blocks
- Preserves all content inside the div
- Adds `slgb-gb-emph-converted` class for styling

---

## Notes

- This plugin **only affects posts** (`post_type = post`). If you need to include pages or custom post types, let us know.
- Escaped Unicode characters (e.g., `\u003cstrong\u003e`) are decoded properly.
- Safe to deactivate and delete after running — it performs a one-time migration.

---

## Disclaimer

Always **back up your database** before running any content modification plugin, especially when dealing with serialized or block content.

---

## Support

Need help or a custom variation? Reach out at [https://octahexa.com](https://octahexa.com)
