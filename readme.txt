=== Images to Woo Products (Uploader Edition) ===
Contributors: martogt
Tags: woocommerce, products, images, bulk, upload, sku, gallery
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk-create WooCommerce products directly from selected images. Per-item fields, auto SKU, multi-category, duplicate detection, and optional onboarding guard.

== Description ==

**Images to Woo Products (Uploader Edition)** lets you upload or select multiple images and instantly create **WooCommerce Simple Products** from them — each image becomes a product:

- Product title = image filename (formatted)
- Featured image = the same file
- Publish immediately (no drafts)
- Per-item controls (price, categories, visibility, status, tags, SKU prefix)
- Global settings (auto-SKU with persisted counter, global categories)
- Duplicate detection modal (preview existing vs new, choose skip or create)
- Optional: allow reusing images already used as featured images
- Optional: disable WooCommerce onboarding redirects (opt-in)

Perfect for artists, galleries, photographers, and anyone who manages image-based product catalogs.

= Highlights =
* Bulk image → product conversion
* Clean WooCommerce-style admin UI
* Auto-increment SKU with optional prefixes (global/per-row)
* Category multi-select (global and per-row)
* Clear creation summary: created, skipped (duplicates/used/non-image)

= Privacy =
The plugin does not track users or send data to external services.

== Installation ==

1. Upload the `images-to-woo-products` folder to `/wp-content/plugins/`, or install the ZIP from **Plugins → Add New → Upload Plugin**.
2. Activate **Images to Woo Products (Uploader Edition)**.
3. Go to **Products → Images → Products** to start.

== Frequently Asked Questions ==

= Will it overwrite existing products? =
No. If a product with the same title exists, you’ll see a duplicate modal and can choose to skip or create anyway.

= Can I reuse an image already used as featured on another product? =
Yes — enable the **Allow image reuse** option in the settings (off by default).

= Does it support variable products? =
This tool creates **Simple Products** only (by design, for speed and clarity).

= Can I set categories globally and per product? =
Yes. Global categories apply automatically to rows that don’t set their own.

= Does it require the WooCommerce onboarding to be completed? =
No. There’s an optional setting to disable onboarding redirects and hide the task list.

== Screenshots ==
1. Main interface (select/upload images and list them in a Woo-style table)
2. Per-item controls: categories, visibility, status, price, SKU prefix, tags
3. Result in **Products → All Products** after creation

== Changelog ==

= 2.3.2 =
* Stable release for GitHub / WordPress.org
* Duplicate detection modal and clear summary notices
* Optional onboarding redirect guard (opt-in)
* UI polish, improved validation, and security hardening

== Upgrade Notice ==
2.3.2 — Stable release. Includes duplicate modal, onboarding guard (opt-in), and UI/UX improvements.
