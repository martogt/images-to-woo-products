=== Images to Products for WooCommerce ===
Contributors: martogt
Tags: woocommerce, bulk, products, uploader, images, sku, gallery
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk‑create WooCommerce products from selected images — each image becomes a Simple product with the same image as featured image.

== Description ==
**Images to Products for WooCommerce (Uploader Edition)** lets you select or upload multiple images and quickly create **Simple** products out of them. Per‑item options include **Name**, **SKU**, **Price**, **Description**, and **Categories**. The plugin also supports **global categories** and **SKU prefix** for faster bulk entry.

== Installation ==
1. Upload the folder `images-to-products-for-woocommerce` to `/wp-content/plugins/`, or install the ZIP through **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Products → Images → Products (Uploader)**.

== Screenshots ==
1. Uploader UI — pick images and edit per‑row fields.
2. Result in **Products → All Products** after creation.

== Frequently Asked Questions ==
= Does it create Variable products? =
Not yet — currently every image becomes a Simple product.

= Can I control SKUs? =
Yes. You can auto‑generate SKUs with a prefix, or type a custom SKU per image.

== Changelog ==
= 2.3.5 =
* Readme/assets update; small admin UI fixes.
* i18n/escaping/sanitization improvements.
* Replaced deprecated `get_page_by_title()` with `WP_Query`.

= 2.3.4 =
* Fixed table column layout and spacing.

= 2.3.3 =
* WP.org prep: text domain and headers aligned.

== Upgrade Notice ==
= 2.3.5 =
This is a maintenance release focusing on stability, compatibility, and documentation.
