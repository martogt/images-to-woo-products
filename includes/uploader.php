<?php
/**
 * Plugin Name: ImToWOOPro – Images to WooCommerce Products
 * Description: Upload images and convert them to Woo products (bulk, auto SKU, categories, etc.).
 * Version: 2.0.9
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Marv
 * License: GPLv2 or later
 * Update URI: https://github.com/USERNAME/im-to-woo-pro   // ВАЖНО за не-WP.org плъгини
 */


if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('ITPWC_Uploader') ) :
final class ITPWC_Uploader {

    public static function boot(){
        if ( ! class_exists('WooCommerce') ) return;
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_footer', [__CLASS__, 'print_inline_assets']);
    }

    public static function add_menu(){
        add_submenu_page(
            'edit.php?post_type=product',
            __('Images → Products', 'im-to-woo-pro'),
            __('Images → Products', 'im-to-woo-pro'),
            'manage_woocommerce',
            'imtowoopro-images-products',
            [__CLASS__, 'render_admin_page']
        );
    }

    /** Render admin page */
    public static function render_admin_page(){
        if ( ! current_user_can('manage_woocommerce') ) return;
        if ( function_exists('wp_enqueue_media') ) wp_enqueue_media();
        wp_enqueue_script('jquery');

        // Handle POST
        if ( isset($_POST['itpwc_run']) && check_admin_referer('itpwc_run_nonce') ) {
            $use_sku      = !empty($_POST['itpwc_use_sku']);
            $global_pref  = isset($_POST['itpwc_sku_prefix']) ? sanitize_text_field($_POST['itpwc_sku_prefix']) : '';
            $per_image    = isset($_POST['itpwc_per_image']) ? json_decode(wp_unslash($_POST['itpwc_per_image']), true) : [];
            $ids_raw      = isset($_POST['itpwc_attachment_ids']) ? sanitize_text_field($_POST['itpwc_attachment_ids']) : '';
            $ids          = array_filter(array_map('absint', array_filter(array_map('trim', explode(',', $ids_raw)))));

            // Global categories (multi‑select)
            $global_cats  = isset($_POST['itpwc_global_cats']) ? array_map('intval', (array)$_POST['itpwc_global_cats']) : [];
            $global_cats  = array_values(array_filter($global_cats)); // remove zeros

            if ( empty($ids) ) {
                echo '<div class="notice notice-error"><p>'.esc_html__('Please select or upload at least one image.', 'im-to-woo-pro').'</p></div>';
            } else {
                $result = self::create_products($ids, $per_image, $use_sku, $global_pref, $global_cats);
                echo '<div class="notice notice-success"><p>'
                    . sprintf( esc_html__('Done: created %d products, skipped %d.', 'im-to-woo-pro'), intval($result['created']), intval($result['skipped']) )
                    . '</p></div>';
            }
        }

        // Category options (HTML reused in rows & global control)
        $cat_options_html = self::get_category_options_html();
        $next_seed = (int) get_option('itpwc_sku_next', 1);

        echo '<div class="wrap">';
        echo '<h1>'.esc_html__('Images → Woo Products (Uploader)', 'im-to-woo-pro').'</h1>';
        echo '<p>'.esc_html__('Select or upload images, then fill per-product fields below. The interface mirrors the Products list.', 'im-to-woo-pro').'</p>';

        echo '<form method="post" id="itpwc_form">';
        wp_nonce_field('itpwc_run_nonce');
        echo '<input type="hidden" id="itpwc_attachment_ids" name="itpwc_attachment_ids" value="" />';
        echo '<input type="hidden" id="itpwc_per_image" name="itpwc_per_image" value="{}" />';

        // SETTINGS card (top)
        echo '<div class="itpwc-card itpwc-settings">';
        echo '<h2>'.esc_html__('Settings', 'im-to-woo-pro').'</h2>';
        echo '<div class="itpwc-grid-compact">';
        echo '  <div class="itpwc-setting">'
            .'<label class="itpwc-label"><input type="checkbox" name="itpwc_use_sku" value="1" checked> '.esc_html__('Auto-generate SKU numbers', 'im-to-woo-pro').'</label>'
            .'<p class="description">'.esc_html__('Sequential and persisted between runs. Live preview shown in rows.', 'im-to-woo-pro').'</p>'
            .'</div>';
        echo '  <div class="itpwc-setting">'
            .'<label class="itpwc-label">'.esc_html__('Global SKU prefix (optional)', 'im-to-woo-pro').'</label>'
            .'<input type="text" name="itpwc_sku_prefix" value="" class="regular-text" placeholder="ART-" />'
            .'</div>';
        echo '  <div class="itpwc-setting">'
            .'<label class="itpwc-label">'.esc_html__('Global categories (optional)', 'im-to-woo-pro').'</label>'
            .'<select name="itpwc_global_cats[]" multiple size="5" class="itpwc-select-multi itpwc-compact-select">'
            .'<option value="0">'.esc_html__('— No category —','itpwc').'</option>'
            .$cat_options_html
            .'</select>'
            .'<p class="description">'.esc_html__('Applied to rows that keep “— No category —”. Per-row selection has priority.', 'im-to-woo-pro').'</p>'
            .'</div>';
        echo '  <div class="itpwc-setting">'
            .'<label class="itpwc-label">'.esc_html__('Next SKU number (preview)', 'im-to-woo-pro').'</label>'
            .'<input type="text" value="'.esc_attr( str_pad((string)$next_seed, 5, '0', STR_PAD_LEFT) ).'" class="regular-text" disabled />'
            .'</div>';
        echo '</div>'; // compact grid
        echo '</div>';

        // Toolbar
        echo '<div class="itpwc-toolbar">';
        echo '<button type="button" class="button button-secondary button-hero" id="itpwc_select_btn">'.esc_html__('Select / Upload images', 'im-to-woo-pro').'</button> ';
        echo '<button type="button" class="button" id="itpwc_clear_btn">'.esc_html__('Clear selection', 'im-to-woo-pro').'</button>';
        echo '<span class="itpwc-count"><strong id="itpwc_count">0</strong> '.esc_html__('images selected', 'im-to-woo-pro').'</span>';
        echo '</div>';

        // List table
        echo '<table class="wp-list-table widefat fixed striped table-view-list posts itpwc-table" data-cat-options="'.esc_attr($cat_options_html).'">';
        echo '  <thead><tr>'
            .'<th class="column-thumb">'.esc_html__('Image','itpwc').'</th>'
            .'<th class="column-name">'.esc_html__('Name','itpwc').'</th>'
            .'<th class="column-cat">'.esc_html__('Categories','itpwc').'</th>'
            .'<th class="column-vis">'.esc_html__('Visibility','itpwc').'</th>'
            .'<th class="column-status">'.esc_html__('Status','itpwc').'</th>'
            .'<th class="column-price">'.esc_html__('Price','itpwc').'</th>'
            .'<th class="column-sku">'.esc_html__('SKU prefix / preview','itpwc').'</th>'
            .'<th class="column-tags">'.esc_html__('Tags','itpwc').'</th>'
            .'</tr></thead>';
        echo '  <tbody id="itpwc_rows"></tbody>';
        echo '</table>';

        echo '<div class="itpwc-actions">';
        submit_button( __('Create products now', 'im-to-woo-pro'), 'primary button-hero', 'itpwc_run', false );
        echo '<span class="spinner is-active" id="itpwc_spinner" style="display:none"></span>';
        echo '</div>';

        echo '</form>';

        echo '<hr />';
        echo '<h2>'.esc_html__('Notes', 'im-to-woo-pro').'</h2>';
        echo '<ul style="list-style:disc;padding-left:20px">'
            .'<li>'.esc_html__('All editable fields are per row. Leave “— No category —” to inherit global categories.', 'im-to-woo-pro').'</li>'
            .'<li>'.esc_html__('Name derives from image filename. Products are Simple.', 'im-to-woo-pro').'</li>'
            .'<li>'.esc_html__('If an image is already used as a featured image of a product, it is skipped.', 'im-to-woo-pro').'</li>'
            .'</ul>';

        echo '</div>';

        echo '<script>window.ITPWC_NEXT_SEED='.intval($next_seed).';</script>';
    }

    /** Footer assets */
    public static function print_inline_assets(){
        if ( ! function_exists('get_current_screen') ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'product_page_imtowoopro-images-products') return;
        if ( function_exists('wp_enqueue_media') ) wp_enqueue_media();
        wp_enqueue_script('jquery');

        echo '<style>
        .itpwc-toolbar{display:flex;gap:12px;align-items:center;margin:12px 0}
        .itpwc-count{color:#555}
        .itpwc-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px;box-shadow:0 1px 2px rgba(0,0,0,.04);margin-top:6px}
        .itpwc-grid-compact{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
        .itpwc-setting .description{margin:.25rem 0 0;color:#666}
        .itpwc-label{display:block;margin:6px 0 4px;font-weight:600}
        .itpwc-select-multi{width:100%;}
        .itpwc-compact-select{max-height:140px;overflow:auto}
        .itpwc-actions{margin-top:16px;display:flex;align-items:center;gap:12px}
        .itpwc-table .column-thumb{width:64px}
        .itpwc-table .column-cat{width:260px}
        .itpwc-table .column-vis{width:150px}
        .itpwc-table .column-status{width:120px}
        .itpwc-table .column-price{width:120px}
        .itpwc-table .column-sku{width:260px}
        .itpwc-table .column-tags{width:220px}
        .itpwc-row-img{width:48px;height:48px;object-fit:cover;border-radius:4px;border:1px solid #e0e0e0}
        .itpwc-miniinput{width:100%}
        .itpwc-sku-flex{display:flex;gap:6px;align-items:center}
        .itpwc-sku-preview{opacity:.7}
        .itpwc-multi{min-height:34px;max-height:120px;overflow:auto}
        @media (max-width:1300px){ .itpwc-grid-compact{grid-template-columns:1fr 1fr} }
        @media (max-width:900px){ .itpwc-grid-compact{grid-template-columns:1fr} }
        </style>';

        echo '<script type="text/javascript">'. self::inline_js() .'</script>';
    }

    /** Category options HTML reused in rows & settings */
    private static function get_category_options_html(){
        $html = '';
        $terms = get_terms([ 'taxonomy'=>'product_cat', 'hide_empty'=>false, 'parent'=>0 ]);
        if ( is_wp_error($terms) ) return $html;
        foreach ($terms as $t) { $html .= self::cat_option_branch_html($t, 0); }
        return $html;
    }
    private static function cat_option_branch_html($term, $depth){
        $pad = str_repeat('— ', $depth);
        $html = '<option value="'.intval($term->term_id).'">'.esc_html($pad.$term->name).'</option>';
        $children = get_terms([ 'taxonomy'=>'product_cat', 'hide_empty'=>false, 'parent'=>$term->term_id ]);
        if ( is_wp_error($children) || empty($children) ) return $html;
        foreach ($children as $c) { $html .= self::cat_option_branch_html($c, $depth+1); }
        return $html;
    }

    /** Create products */
    private static function create_products($attachment_ids, $per, $use_sku, $global_prefix, $global_cats){
        $created=0; $skipped=0;
        foreach ($attachment_ids as $attachment_id){
            $att = get_post($attachment_id);
            if ( ! $att || 'attachment' !== $att->post_type ) { $skipped++; continue; }
            $mime = get_post_mime_type($attachment_id);
            if ( strpos((string)$mime, 'image/') !== 0 ) { $skipped++; continue; }

            // Skip if image already used as featured
            $existing = get_posts([
                'post_type'=>'product', 'meta_key'=>'_thumbnail_id', 'meta_value'=>$attachment_id,
                'fields'=>'ids', 'posts_per_page'=>1
            ]);
            if ( $existing ) { $skipped++; continue; }

            $file_path = get_attached_file($attachment_id);
            $basename = $file_path ? pathinfo($file_path, PATHINFO_FILENAME) : ('product-'.$attachment_id);
            $title = ucwords(trim(str_replace(['-','_'], ' ', $basename)));

            $row = isset($per[$attachment_id]) && is_array($per[$attachment_id]) ? $per[$attachment_id] : [];
            // Per‑row categories: array of IDs
            $cats_row = [];
            if ( isset($row['cats']) ) {
                $cats_row = array_values(array_filter(array_map('intval', (array)$row['cats'])));
            } elseif ( isset($row['cat']) && $row['cat'] ) { // backward compat
                $cats_row = [ intval($row['cat']) ];
            }
            $vis       = isset($row['vis']) ? sanitize_text_field($row['vis']) : 'visible';
            $status    = isset($row['status']) ? sanitize_text_field($row['status']) : 'publish';
            $price     = isset($row['price']) && $row['price'] !== '' ? wc_format_decimal($row['price']) : '0';
            $tags_raw  = isset($row['tags']) ? $row['tags'] : '';
            $tags_arr  = array_filter(array_map('trim', explode(',', (string)$tags_raw)));
            $pref_row  = isset($row['sku_prefix']) ? sanitize_text_field($row['sku_prefix']) : '';

            $product = new WC_Product_Simple();
            $product->set_name($title);
            $product->set_status($status);
            $product->set_catalog_visibility($vis);
            $product->set_manage_stock(false);
            $product->set_image_id($attachment_id);

            // Categories: per‑row (if any) else global multi
            if ( !empty($cats_row) ) {
                $product->set_category_ids($cats_row);
            } elseif ( !empty($global_cats) ) {
                $product->set_category_ids($global_cats);
            }

            $product->set_regular_price( $price );

            if ( $use_sku ){
                $sku = self::next_sequential_sku( $pref_row !== '' ? $pref_row : $global_prefix );
                $product->set_sku($sku);
            }

            $product_id = $product->save();
            if ($product_id){
                self::regenerate_attachment_sizes($attachment_id);
                if ( !empty($tags_arr) ) { wp_set_object_terms($product_id, $tags_arr, 'product_tag', true); }
                $created++;
            }
        }
        return compact('created','skipped');
    }

    /** Persistent sequential SKU generator */
    private static function next_sequential_sku($prefix = ''){
        $n = (int) get_option('itpwc_sku_next', 1);
        $max_attempts = 100000;
        do {
            $sku = $prefix . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
            $n++;
        } while ( wc_get_product_id_by_sku($sku) && --$max_attempts > 0 );
        update_option('itpwc_sku_next', $n, false);
        return $sku;
    }

    /** Ensure thumbnails exist */
    private static function regenerate_attachment_sizes($attachment_id){
        $file = get_attached_file($attachment_id);
        if ( ! $file || ! file_exists($file) ) return;
        if ( function_exists('wp_update_image_subsizes') ) { wp_update_image_subsizes($attachment_id); return; }
        $metadata = wp_generate_attachment_metadata($attachment_id, $file);
        if ( ! empty($metadata) ) { wp_update_attachment_metadata($attachment_id, $metadata); }
    }

    /** Inline JS */
    private static function inline_js(){
        return <<<JS
(function($){
    var frame, idsField, perField, rowsBody, per = {}, seed = window.ITPWC_NEXT_SEED || 1;
    $(function(){
        idsField = $('#itpwc_attachment_ids');
        perField = $('#itpwc_per_image');
        rowsBody = $('#itpwc_rows');

        $('#itpwc_select_btn').on('click', function(e){
            e.preventDefault();
            if (!frame){
                frame = wp.media({ title: 'Select or Upload Images', button: { text: 'Use these images' }, multiple: true });
                frame.on('select', function(){
                    var selection = frame.state().get('selection');
                    selection.each(function(att){
                        var a = att.toJSON();
                        addRow(a.id, a.filename || a.title, (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url);
                    });
                    syncHidden();
                });
            }
            frame.open();
        });

        $('#itpwc_clear_btn').on('click', function(e){ e.preventDefault(); per = {}; rowsBody.empty(); syncHidden(); });
        rowsBody.on('click', '.itpwc-remove', function(){ var tr=$(this).closest('tr'); var id=tr.data('id'); delete per[id]; tr.remove(); syncHidden(); });

        // Per‑row listeners
        rowsBody.on('change', '.itpwc-cats', function(){ ensureRow(rowId(this)).cats = $(this).val() || []; syncHidden(false); });
        rowsBody.on('change', '.itpwc-vis', function(){ ensureRow(rowId(this)).vis = $(this).val(); syncHidden(false); });
        rowsBody.on('change', '.itpwc-status', function(){ ensureRow(rowId(this)).status = $(this).val(); syncHidden(false); });
        rowsBody.on('input',  '.itpwc-price', function(){ ensureRow(rowId(this)).price = $(this).val(); syncHidden(false); });
        rowsBody.on('input',  '.itpwc-prefix', function(){ ensureRow(rowId(this)).sku_prefix = $(this).val(); updateSkuPreview($(this).closest('tr')); syncHidden(false); });
        rowsBody.on('input',  '.itpwc-tags', function(){ ensureRow(rowId(this)).tags = $(this).val(); syncHidden(false); });

        function rowId(el){ return $(el).closest('tr').data('id'); }
        function ensureRow(id){ if(!per[id]) per[id] = { cats:[], vis:'visible', status:'publish', price:'0', sku_prefix:'', tags:'' }; return per[id]; }

        function addRow(id, name, url){
            if (rowsBody.find('tr[data-id="'+id+'"]').length) return;
            ensureRow(id);
            var idx = rowsBody.find('tr').length; // preview index
            var html = '<tr data-id="'+id+'">'
                + '<td class="column-thumb"><div style="position:relative"><img class="itpwc-row-img" src="'+url+'" alt="" />'
                + '<span class="itpwc-remove dashicons dashicons-no-alt" title="Remove" style="position:absolute;top:-8px;right:-8px;background:#ca4a1f;color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center"></span>'
                + '</div></td>'
                + '<td class="column-name"><strong>'+ escapeHtml(String(name).replace(/\.[^/.]+$/, "").replace(/[\-_]/g,' ')) +'</strong></td>'
                + '<td class="column-cat">'
                    + '<select multiple size="4" class="itpwc-cats itpwc-miniinput itpwc-multi">'
                    + '<option value="0">— No category —</option>' + $('.itpwc-table').attr('data-cat-options')
                    + '</select>'
                + '</td>'
                + '<td class="column-vis"><select class="itpwc-vis itpwc-miniinput">'
                    + '<option value="visible">Visible</option>'
                    + '<option value="catalog">Catalog only</option>'
                    + '<option value="search">Search only</option>'
                    + '<option value="hidden">Hidden</option>'
                  + '</select></td>'
                + '<td class="column-status"><select class="itpwc-status itpwc-miniinput">'
                    + '<option value="publish" selected>Publish</option>'
                    + '<option value="draft">Draft</option>'
                  + '</select></td>'
                + '<td class="column-price"><input type="number" step="0.01" min="0" class="itpwc-price itpwc-miniinput" placeholder="0.00" value="0" /></td>'
                + '<td class="column-sku"><div class="itpwc-sku-flex">'
                    + '<input type="text" class="itpwc-prefix" placeholder="SKU prefix" style="width:130px" />'
                    + '<span class="itpwc-sku-preview">'+ skuPreview('', idx) +'</span>'
                  + '</div><div class="description">Prefix overrides the global one. Number is auto.</div></td>'
                + '<td class="column-tags"><input type="text" class="itpwc-tags itpwc-miniinput" placeholder="tags: portrait, oil" /></td>'
              + '</tr>';
            rowsBody.append(html);
        }

        function skuPreview(prefix, index){
            var num = (seed + index).toString();
            while (num.length < 5) num = '0'+num;
            return (prefix||'') + num;
        }
        function updateSkuPreview(tr){
            var index = tr.index();
            var prefix = tr.find('.itpwc-prefix').val() || '';
            tr.find('.itpwc-sku-preview').text( skuPreview(prefix, index) );
        }

        function syncHidden(updateCountToo=true){
            var ids=[]; rowsBody.find('tr').each(function(){ ids.push($(this).data('id')); updateSkuPreview($(this)); });
            idsField.val(ids.join(','));
            perField.val(JSON.stringify(per));
            if (updateCountToo) $('#itpwc_count').text(ids.length);
        }

        function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]); }); }
    });
})(jQuery);
JS;
    }
}
endif;

add_action('plugins_loaded', ['ITPWC_Uploader','boot']);