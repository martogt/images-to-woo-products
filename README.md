<div align="center">

# 🖼️ **Images to Products for WooCommerce (Uploader Edition)**  
### _Bulk-create WooCommerce products directly from images_

<img src="https://raw.githubusercontent.com/martogt/images-to-woo-products/main/assets/banner-preview.png" alt="Images to Woo Products preview" width="100%" />

[![WordPress](https://img.shields.io/badge/WordPress-6.8%2B-blue?logo=wordpress)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-10.x-purple?logo=woocommerce)](https://woocommerce.com)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2%20or%20later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.3.2-brightgreen)](https://github.com/martogt/images-to-woo-products/releases)

</div>

---

## 🧠 Overview

**Images to Woo Products (Uploader Edition)** is a WordPress plugin that lets you **upload or select multiple images** and automatically create **WooCommerce Simple Products** from them — each image becomes its own product.

Perfect for artists, photographers, and gallery owners who manage large product catalogs based on images.

> ✅ Supports per-item customization, auto SKU numbering, multi-category selection, and duplicate detection.

---

## ⚙️ Features

| Feature | Description |
|----------|-------------|
| 🖼️ Bulk Image Upload | Select or upload multiple images and instantly convert them into products |
| 🧾 Auto Naming | Product name = image filename |
| 🪄 Featured Image | Same image automatically assigned as featured |
| 💰 Pricing | Set default or per-product prices |
| 🏷️ Tags & Categories | Multi-select categories and custom product tags |
| 🔢 Auto SKU | Sequential SKU numbers with optional prefixes |
| 🚫 Duplicate Check | Prevents creating duplicate product titles |
| 🧩 Reuse Control | Option to skip or reuse existing images |
| 🧭 Disable Onboarding | Removes WooCommerce setup redirects |
| 🎨 WooCommerce UI Style | Clean, responsive admin interface inspired by Woo design |

---

## 🪜 Installation

### 📦 Option 1: Direct Upload
1. Download the latest release from  
   👉 [GitHub Releases](https://github.com/martogt/images-to-woo-products/releases)
2. In your WordPress admin, go to  
   **Plugins → Add New → Upload Plugin**
3. Choose the ZIP and click **Install Now**
4. Activate **Images to Woo Products (Uploader Edition)**

### 🧰 Option 2: Manual Install
1. Extract the ZIP file.  
2. Upload the folder `images-to-woo-products` to:  
   `/wp-content/plugins/`
3. Activate the plugin from **Plugins → Installed Plugins**

---

## 🧭 Usage Guide

### 1️⃣ Open the Plugin
Go to  
**Products → Images → Products**

You’ll see an interface like this:

<img src="https://raw.githubusercontent.com/martogt/images-to-woo-products/main/assets/ui-overview.png" alt="Plugin UI" width="100%" />

---

### 2️⃣ Upload or Select Images
- Click **Select / Upload Images**
- Choose one or more images from your media library
- Selected images appear in a list with preview thumbnails

---

### 3️⃣ Configure Settings
At the top of the page, you can define global options:

| Setting | Description |
|----------|-------------|
| 💬 **Global Categories** | Assigns selected categories to all products (multi-select) |
| 💬 **SKU Prefix** | Sets a global SKU prefix (e.g., ART-) |
| 💬 **Auto-generate SKUs** | Ensures unique incremental SKUs |
| 💬 **Allow image reuse** | Enables reusing existing featured images |
| 💬 **Disable WC onboarding** | Prevents redirects to “Add Products” |

---

### 4️⃣ Customize Each Product
For each image, you can modify:
- **Price**
- **Categories**
- **Visibility**
- **SKU Prefix**
- **Tags**

Each image will become a standalone WooCommerce **Simple Product**.

<img src="https://raw.githubusercontent.com/martogt/images-to-woo-products/main/assets/ui-row-example.png" alt="Per-product settings example" width="100%" />

---

### 5️⃣ Create Products
When ready:
- Click **Create Products Now**
- The plugin checks for duplicates and asks what to do
- You’ll receive a success summary:
  > ✅ 22 products created — 2 skipped (duplicates)

---

## 📊 Result Example
In your WooCommerce **Products → All Products** list, you’ll see:

<img src="https://raw.githubusercontent.com/martogt/images-to-woo-products/main/assets/products-list.png" alt="All Products list example" width="100%" />

Each new product:
- Has the uploaded image as featured  
- Uses the image filename as title  
- Has unique SKU numbering

---

## 🧩 Technical Notes

| Parameter | Requirement |
|------------|--------------|
| WordPress | ≥ 6.0 |
| WooCommerce | ≥ 9.x |
| PHP | ≥ 7.4 |
| License | GPL v2 or later |

---

## 💡 Pro Tips

- To prevent accidental duplicates, enable “Check for duplicates before creation”.
- Use short prefixes (like `ART-`, `IMG-`) for SKU clarity.
- Combine with **Blocksy** or **Elementor** for custom gallery display.
- Works great for bulk import of paintings, art pieces, or product photography.

---

## 🧠 Roadmap

| Feature | Status |
|----------|---------|
| Real-time duplicate alerts | 🔜 Planned |
| Select2 dropdowns for tags/categories | ⚙️ In progress |
| Drag & drop row ordering | ⏳ Planned |
| WP-CLI support | 🔜 Planned |
| Thumbnail optimization | ✅ Implemented |

---

## 🧑‍💻 Author

**Developed by [Marv](https://github.com/martogt)**  
Inspired by simplicity, built for productivity.  
Every version increment includes small UI and UX refinements.

---

## 📸 Screenshots (recommended to include in `/assets/`)

| File | Purpose |
|------|----------|
| `banner-preview.png` | Header banner for GitHub |
| `ui-overview.png` | Main interface overview |
| `ui-row-example.png` | Single product configuration row |
| `products-list.png` | Example of results in WooCommerce |

---

## 🖌️ Logo Suggestion
You can add a logo file here:  
`/assets/logo.svg`

Design concept:
- Minimalist **camera + WooCommerce “W”** combination.  
- Purple gradient (#96588A) on white background.  
- Rounded square, Apple-like shading.

Example:
