<?php
/**
 * Admin UI and bulk creator.
 *
 * @package images-to-woo-products
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'ITPWC_Uploader' ) ) :

final class ITPWC_Uploader {

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		add_menu_page(
			esc_html__( 'Images → Products', 'images-to-products-for-woocommerce' ),
			esc_html__( 'Images → Products', 'images-to-products-for-woocommerce' ),
			'manage_woocommerce',
			'images-to-products-for-woocommerce',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-format-image',
			58
		);
	}

	/** Render admin page */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( 'jquery' );

		// Handle form submit.
		if ( isset( $_POST['itpwc_run'] ) && check_admin_referer( 'itpwc_run_nonce' ) ) {

			$use_sku     = ! empty( $_POST['itpwc_use_sku'] );
			$global_pref = isset( $_POST['itpwc_sku_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['itpwc_sku_prefix'] ) ) : '';
			check_admin_referer( 'itpwc_run_nonce' );
			$per_image = isset( $_POST['itpwc_per_image'] )
				? json_decode( wp_unslash( $_POST['itpwc_per_image'] ), true )
				: array();
			if ( ! is_array( $per_image ) ) { $per_image = array(); }

			$clean = array();
			foreach ( $per_image as $k => $row ) {
				$r = is_array( $row ) ? $row : array();
				$clean[ absint( $k ) ] = array(
					'name'  => isset( $r['name'] ) ? sanitize_text_field( $r['name'] ) : '',
					'sku'   => isset( $r['sku'] ) ? sanitize_text_field( $r['sku'] ) : '',
					'desc'  => isset( $r['desc'] ) ? wp_kses_post( $r['desc'] ) : '',
					'price' => isset( $r['price'] ) ? floatval( $r['price'] ) : 0,
					'cats'  => isset( $r['cats'] ) ? array_map( 'absint', (array) $r['cats'] ) : array(),
				);
			}
			$per_image = $clean;
			// $per_image   = isset( $_POST['itpwc_per_image'] ) ? json_decode( wp_unslash( $_POST['itpwc_per_image'] ), true ) : array();
			// if ( ! is_array( $per_image ) ) { $per_image = array(); }
			// // Deep sanitize
			// $_clean = array();
			// foreach ( $per_image as $k => $row ) {
			// 	$r = is_array( $row ) ? $row : array();
			// 	$_clean[ intval( $k ) ] = array(
			// 		'name'  => isset( $r['name'] ) ? sanitize_text_field( $r['name'] ) : '',
			// 		'sku'   => isset( $r['sku'] ) ? sanitize_text_field( $r['sku'] ) : '',
			// 		'desc'  => isset( $r['desc'] ) ? wp_kses_post( $r['desc'] ) : '',
			// 		'price' => isset( $r['price'] ) ? floatval( $r['price'] ) : 0,
			// 		'cats'  => isset( $r['cats'] ) ? array_map( 'absint', (array) $r['cats'] ) : array(),
			// 	);
			// }
			// $per_image = $_clean;
			$ids_raw     = isset( $_POST['itpwc_attachment_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['itpwc_attachment_ids'] ) ) : '';
			$ids         = array_filter( array_map( 'absint', array_filter( array_map( 'trim', explode( ',', $ids_raw ) ) ) ) );

			$global_cats = isset( $_POST['itpwc_global_cats'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['itpwc_global_cats'] ) ) : array();
			$global_cats = array_values( array_filter( $global_cats ) );

			if ( empty( $ids ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select or upload at least one image.', 'images-to-products-for-woocommerce' ) . '</p></div>';
			} else {
				$result = self::create_products( $ids, $per_image, $use_sku, $global_pref, $global_cats );
				$created = isset( $result['created'] ) ? (int) $result['created'] : 0;
				$skipped = isset( $result['skipped'] ) ? (int) $result['skipped'] : 0;

				/* translators: 1: created products count, 2: skipped images count */
				$msg = sprintf(
					esc_html__( 'Done: created %1$d products, skipped %2$d.', 'images-to-products-for-woocommerce' ),
					$created,
					$skipped
				);

				echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
			}
		}

		// Build category <option> list once.
		$cat_options_html = self::get_category_options_html();
		$allowed_option   = array( 'option' => array( 'value' => true, 'selected' => true ) );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Images → Woo Products (Uploader)', 'images-to-products-for-woocommerce' ) . '</h1>';
		echo '<p>' . esc_html__( 'Select or upload images, then convert them into products.', 'images-to-products-for-woocommerce' ) . '</p>';

		// Simple layout CSS.
		
		echo '<style>
        .itpwc-table{table-layout:fixed;border-collapse:separate;border-spacing:0}
        .itpwc-table th{white-space:nowrap;text-align:left;padding:8px}
        .itpwc-table td{vertical-align:top;padding:8px}
        .itpwc-table .column-thumb img{max-width:80px;height:auto}
        .itpwc-table .column-name input{width:100%}
        .itpwc-table .column-sku input{width:100%}
        .itpwc-table .column-price input{width:100%;text-align:right}
        .itpwc-table .column-desc textarea{width:100%;height:64px;resize:vertical}
        .itpwc-table .column-cats select{width:100%;min-height:120px}
        /* Settings layout */
        .itpwc-settings .itpwc-grid-compact{display:grid;grid-template-columns:repeat(3,minmax(240px,1fr));gap:14px;align-items:start}
        .itpwc-settings .itpwc-setting label{display:block;margin-bottom:6px;font-weight:600}
        .itpwc-settings .itpwc-setting input[type="text"]{width:100%}
        .itpwc-settings .itpwc-setting select{width:100%;min-height:120px}
        .itpwc-toolbar{margin:10px 0}
        </style>';


		echo '<form method="post" id="itpwc_form">';
		wp_nonce_field( 'itpwc_run_nonce' );

		echo '<input type="hidden" id="itpwc_attachment_ids" name="itpwc_attachment_ids" value="" />';
		echo '<input type="hidden" id="itpwc_per_image" name="itpwc_per_image" value="{}" />';

		echo '<div class="itpwc-card itpwc-settings">';
		echo '<h2>' . esc_html__( 'Settings', 'images-to-products-for-woocommerce' ) . '</h2>';
		echo '<div class="itpwc-grid-compact">';

		echo '<div class="itpwc-setting"><label><input type="checkbox" name="itpwc_use_sku" value="1" checked="checked" /> ' .
			esc_html__( 'Auto-generate SKU (prefix + incremental)', 'images-to-products-for-woocommerce' ) .
		'</label></div>';

		echo '<div class="itpwc-setting"><label for="itpwc_sku_prefix">' . esc_html__( 'SKU Prefix', 'images-to-products-for-woocommerce' ) . '</label>' .
			'<input type="text" id="itpwc_sku_prefix" name="itpwc_sku_prefix" value="" class="regular-text" /></div>';

		echo '<div class="itpwc-setting"><label for="itpwc_global_cats">' . esc_html__( 'Global categories (optional)', 'images-to-products-for-woocommerce' ) . '</label>' .
			'<select id="itpwc_global_cats" name="itpwc_global_cats[]" multiple="multiple" style="min-width:260px;min-height:96px">' .
				'<option value="0">' . esc_html__( '— No category —', 'images-to-products-for-woocommerce' ) . '</option>' .
				wp_kses( $cat_options_html, $allowed_option ) .
			'</select></div>';

		echo '</div></div>';

		
		
		echo '<style>
        .itpwc-table{table-layout:fixed;border-collapse:separate;border-spacing:0}
        .itpwc-table th{white-space:nowrap;text-align:left;padding:8px}
        .itpwc-table td{vertical-align:top;padding:8px}
        .itpwc-table .column-thumb img{max-width:80px;height:auto}
        .itpwc-table .column-name input{width:100%}
        .itpwc-table .column-sku input{width:100%}
        .itpwc-table .column-price input{width:100%;text-align:right}
        .itpwc-table .column-desc textarea{width:100%;height:64px;resize:vertical}
        .itpwc-table .column-cats select{width:100%;min-height:120px}
        /* Settings layout */
        .itpwc-settings .itpwc-grid-compact{display:grid;grid-template-columns:repeat(3,minmax(240px,1fr));gap:14px;align-items:start}
        .itpwc-settings .itpwc-setting label{display:block;margin-bottom:6px;font-weight:600}
        .itpwc-settings .itpwc-setting input[type="text"]{width:100%}
        .itpwc-settings .itpwc-setting select{width:100%;min-height:120px}
        .itpwc-toolbar{margin:10px 0}
        </style>';

echo '<div class="itpwc-toolbar">';
		echo '<button type="button" class="button button-secondary" id="itpwc_select_btn">' . esc_html__( 'Select / Upload images', 'images-to-products-for-woocommerce' ) . '</button> ';
		echo '<button type="button" class="button" id="itpwc_clear_btn">' . esc_html__( 'Clear selection', 'images-to-products-for-woocommerce' ) . '</button>';
		echo '</div>';

		echo '<table class="wp-list-table widefat fixed striped itpwc-table">';
		echo '<colgroup>
				<col style="width:90px" />
				<col style="width:auto" />
				<col style="width:320px" />
				<col style="width:110px" />
				<col style="width:280px" />
				<col style="width:240px" />
			</colgroup>';
		echo '<thead><tr>' .
			'<th class="column-thumb">' . esc_html__( 'Image', 'images-to-products-for-woocommerce' ) . '</th>' .
			'<th class="column-name">' . esc_html__( 'Name', 'images-to-products-for-woocommerce' ) . '</th>' .
			'<th class="column-sku">' . esc_html__( 'SKU', 'images-to-products-for-woocommerce' ) . '</th>' .
			'<th class="column-price">' . esc_html__( 'Price', 'images-to-products-for-woocommerce' ) . '</th>' .
			'<th class="column-desc">' . esc_html__( 'Description', 'images-to-products-for-woocommerce' ) . '</th>' .
			'<th class="column-cats">' . esc_html__( 'Categories', 'images-to-products-for-woocommerce' ) . '</th>' .
		'</tr></thead><tbody id="the-list"></tbody></table>';
echo '<p class="submit"><button type="submit" name="itpwc_run" class="button button-primary">' . esc_html__( 'Create Products', 'images-to-products-for-woocommerce' ) . '</button></p>';

		echo '<div id="itpwc-cat-options-tpl" style="display:none">' . wp_kses( $cat_options_html, $allowed_option ) . '</div>';
/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo wp_print_inline_script_tag( self::inline_js(), array( 'type' => 'text/javascript' ) );
		echo '</form></div>';
	}

	/** Build <option> list of product categories. */
	private static function get_category_options_html() {
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$out = '';
		foreach ( $terms as $t ) {
			$out .= sprintf(
				'<option value="%1$d">%2$s</option>',
				absint( $t->term_id ),
				esc_html( $t->name )
			);
		}
		return $out;
	}

	/** Compare two arrays of IDs as sets (order-insensitive). */
	private static function same_id_set( $a, $b ) {
		$a = array_values( array_unique( array_map( 'absint', (array) $a ) ) );
		$b = array_values( array_unique( array_map( 'absint', (array) $b ) ) );
		sort( $a ); sort( $b );
		return $a === $b;
	}

	
/** Find existing product by exact title (non-trash only). */
private static function find_existing_product_by_title( $title ) {
    $title = sanitize_text_field( $title );
    $q = new WP_Query( array(
        'post_type'      => 'product',
        'post_status'    => array( 'publish','pending','draft','private' ),
        'title'          => $title,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    if ( $q instanceof WP_Query && ! empty( $q->posts ) ) {
        return (int) $q->posts[0];
    }
    return 0;
}


	/** Generate a category prefix like "[POSTERS] ". */
	private static function category_prefix( $cat_ids ) {
		$cat_ids = array_values( array_filter( array_map( 'absint', (array) $cat_ids ) ) );
		if ( empty( $cat_ids ) ) { return ''; }
		$term = get_term( $cat_ids[0], 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$slug = strtoupper( sanitize_key( $term->slug ) );
			$slug = substr( $slug, 0, 10 );
			return '[' . $slug . '] ';
		}
		return '';
	}

	/**
	 * Create products.
	 * Rules:
	 * - Products in trash do NOT block creation.
	 * - Same title + same cats => skip.
	 * - Same title + different cats => create but prefix title with category label.
	 */
	private static function create_products( $ids, $per_image, $use_sku, $global_pref, $global_cats ) {
		$created = 0;
		$skipped = 0;

		$idx = 0;

		foreach ( (array) $ids as $att_id ) {
			$att_id = absint( $att_id );
			if ( ! $att_id ) { $skipped++; $idx++; continue; }

			$img = get_post( $att_id );
			if ( ! $img || 'attachment' !== $img->post_type ) { $skipped++; $idx++; continue; }

			$row = ( isset( $per_image[ $idx ] ) && is_array( $per_image[ $idx ] ) ) ? $per_image[ $idx ] : array();

			// Base title.
			if ( ! empty( $row['name'] ) ) {
				$title = sanitize_text_field( $row['name'] );
			} else {
				$title = get_the_title( $att_id );
				if ( ! $title ) {
					$file  = get_attached_file( $att_id );
					$title = $file ? sanitize_text_field( wp_basename( $file ) ) : 'Image';
				}
			}

			// Effective categories = per-row ∪ global.
			$row_cats      = isset( $row['cats'] ) ? array_map( 'absint', (array) $row['cats'] ) : array();
			$effective_cats = array_values( array_unique( array_filter( array_merge( $row_cats, $global_cats ) ) ) );

			// Duplicate handling.
			$existing = self::find_existing_product_by_title( $title );

			if ( $existing ) {
				$existing_cats = wp_get_post_terms( $existing, 'product_cat', array( 'fields' => 'ids' ) );

				if ( self::same_id_set( $existing_cats, $effective_cats ) ) {
					// Same name + same cats → skip.
					$skipped++; $idx++; continue;
				}

				// Same name + different cats → prefix title with category label and ensure uniqueness.
				$prefix    = self::category_prefix( $effective_cats );
				$new_title = $prefix . $title;
				$counter   = 2;
				while ( self::find_existing_product_by_title( $new_title ) ) {
					$new_title = $prefix . $title . ' #' . $counter;
					$counter++;
				}
				$title = $new_title;
			}

			// Insert product.
			$pid = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => isset( $row['desc'] ) ? wp_kses_post( $row['desc'] ) : '',
				),
				true
			);
			if ( is_wp_error( $pid ) ) { $skipped++; $idx++; continue; }

			wp_set_object_terms( $pid, 'simple', 'product_type', false );
			set_post_thumbnail( $pid, $att_id );

			if ( ! empty( $effective_cats ) ) {
				wp_set_object_terms( $pid, $effective_cats, 'product_cat', true );
			}

			// SKU.
			$final_sku = '';
			if ( ! empty( $row['sku'] ) ) {
				$maybe = sanitize_text_field( $row['sku'] );
				if ( function_exists( 'wc_product_has_unique_sku' ) ) {
					if ( wc_product_has_unique_sku( $maybe, 0 ) ) { $final_sku = $maybe; }
				} else {
					$final_sku = $maybe;
				}
			}
			if ( empty( $final_sku ) && $use_sku ) {
				$prefix    = $global_pref ? $global_pref : 'SKU';
				$final_sku = $prefix . '-' . $pid;
			}
			

// Persist SKU (if any).
if ( ! empty( $final_sku ) ) {
    update_post_meta( $pid, '_sku', $final_sku );
}

// Price.
$price = 0;
if ( isset( $row['price'] ) ) {
    $price = floatval( $row['price'] );
}
if ( $price > 0 ) {
    update_post_meta( $pid, '_regular_price', $price );
    update_post_meta( $pid, '_price', $price );
    if ( function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( $pid );
        if ( $product ) {
            $product->set_regular_price( $price );
            $product->save();
        }
    }
}
if ( ! empty( $final_sku ) ) {
				update_post_meta( $pid, '_sku', $final_sku );
			}

			$created++;
			$idx++;
		}

		return array( 'created' => $created, 'skipped' => $skipped );
	}

	/** Admin inline JS */
	private static function inline_js() {
		ob_start(); ?>
(function($){
	let mediaFrame = null;

	function getCatOptionsHTML(){
		const holder = document.getElementById('itpwc-cat-options-tpl');
		return holder ? holder.innerHTML : '';
	}

	function openMediaFrame(){
		if (mediaFrame) { mediaFrame.open(); return; }
		mediaFrame = wp.media({ multiple: true, title: '<?php echo esc_js( __( 'Select images', 'images-to-products-for-woocommerce' ) ); ?>' });
		mediaFrame.on('select', function(){
			const sel   = mediaFrame.state().get('selection').toJSON() || [];
			const ids   = sel.map(i => i.id);
			const $ids  = $('#itpwc_attachment_ids');
			const $per  = $('#itpwc_per_image');
			const $tb   = $('.itpwc-table tbody');
			const cat   = getCatOptionsHTML();

			$ids.val(ids.join(','));
			$per.val('{}');
			$tb.empty();

			sel.forEach((item) => {
				const name  = item.title || item.filename || ('#' + item.id);
				const thumb = (item.sizes && item.sizes.thumbnail && item.sizes.thumbnail.url) ? item.sizes.thumbnail.url : item.icon;
				const row = `
					<tr>
						<td class="column-thumb"><img src="${thumb}" style="max-width:80px;height:auto" alt="thumb"/></td>
						<td class="column-name"><input type="text" value="${String(name).replace(/"/g,'&quot;')}" class="widefat"/></td>
						<td class="column-sku"><input type="text" value="" class="regular-text"/></td>
						<td class="column-price"><input type="number" step="0.01" min="0" value="" class="small-text"/></td>
						<td class="column-desc"><textarea rows="2" class="widefat" placeholder=""></textarea></td>
						<td class="column-cats"><select multiple="multiple" style="min-width:200px">${cat}</select></td>
					</tr>`;
				$tb.append(row);
			});
		});
		mediaFrame.open();
	}

	$(document).on('click', '#itpwc_select_btn', function(e){ e.preventDefault(); openMediaFrame(); });
	$(document).on('click', '#itpwc_clear_btn',  function(e){ e.preventDefault(); $('#itpwc_attachment_ids').val(''); $('#itpwc_per_image').val('{}'); $('.itpwc-table tbody').empty(); });

	// Serialize per-image data on submit (index-based).
	$(document).on('submit', '#itpwc_form', function(){
		const data = {};
		$('.itpwc-table tbody tr').each(function(idx, tr){
			const $tr = $(tr);
			data[idx] = {
				name: $tr.find('.column-name input').val() || '',
				sku:  $tr.find('.column-sku input').val() || '',
				desc: $tr.find('.column-desc textarea').val() || '',
				price: parseFloat($tr.find('.column-price input').val()) || 0,
				cats: ($tr.find('.column-cats select').val() || []).map(v => parseInt(v,10) || 0)
			};
		});
		$('#itpwc_per_image').val(JSON.stringify(data));
	});
})(jQuery);
<?php
		return (string) ob_get_clean();
	}
}

endif;