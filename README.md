# OctaHexa SLGB Heading Converter

Converts custom `slgb/h1` through `slgb/h6` blocks to native WordPress `core/heading` blocks with proper HTML tags (`<h1>`–`<h6>`).  
Useful when switching themes or AMP modes that do not support custom block types.

---

## Plugin Details

- **Plugin Name:** OctaHexa SLGB Heading Converter  
- **Author:** OctaHexa  
- **Version:** 1.0.1 
- **GitHub URI:** https://github.com/WPSpeedExpert/octahexa-slgb-heading-converter

---

## Requirements

- WordPress 5.0 or later
- PHP 7.4 or later
- User role: Administrator (for running the conversion)

---

## Installation

1. Download or clone this repository into your `/wp-content/plugins/` directory.
2. Make sure the file is named: `octahexa-slgb-heading-converter.php`
3. Activate the plugin from your WordPress admin panel (`Plugins → Installed Plugins`).

---

## Usage

1. Go to **Tools → Convert SLGB Headings** in the WordPress admin.
2. Click the **"Run Conversion"** button.
3. The plugin will:
   - Scan all posts for blocks like `<!-- wp:slgb/h2 {"text":"..."} /-->`
   - Replace them with native `core/heading` blocks:
     ```html
     <!-- wp:heading {"level":2} --><h2>Heading text</h2><!-- /wp:heading -->
     ```
4. A success message will show how many posts were updated.

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
