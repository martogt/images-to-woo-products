<?php
/**
 * Admin UI and bulk creator.
 *
 * @package images-to-woo-products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ITPWC_Uploader' ) ) :

final class ITPWC_Uploader {

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		add_menu_page(
			esc_html__( 'Images → Products', 'images-to-woo-products' ),
			esc_html__( 'Images → Products', 'images-to-woo-products' ),
			'manage_woocommerce',
			'images-to-woo-products',
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
			$per_image   = isset( $_POST['itpwc_per_image'] ) ? json_decode( wp_unslash( $_POST['itpwc_per_image'] ), true ) : array();
			$ids_raw     = isset( $_POST['itpwc_attachment_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['itpwc_attachment_ids'] ) ) : '';
			$ids         = array_filter( array_map( 'absint', array_filter( array_map( 'trim', explode( ',', $ids_raw ) ) ) ) );

			$global_cats = isset( $_POST['itpwc_global_cats'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['itpwc_global_cats'] ) ) : array();
			$global_cats = array_values( array_filter( $global_cats ) );

			if ( empty( $ids ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select or upload at least one image.', 'images-to-woo-products' ) . '</p></div>';
			} else {
				$result = self::create_products( $ids, $per_image, $use_sku, $global_pref, $global_cats );

				/* translators: 1: created products count, 2: skipped images count */
				$msg = sprintf(
					esc_html__( 'Done: created %1$d products, skipped %2$d.', 'images-to-woo-products' ),
					intval( $result['created'] ),
					intval( $result['skipped'] )
				);

				echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
			}
		}

		// Build category <option> list once and print it as a hidden template (escaped).
		$cat_options_html = self::get_category_options_html();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Images → Woo Products (Uploader)', 'images-to-woo-products' ) . '</h1>';
		echo '<p>' . esc_html__( 'Select or upload images, then convert them into products.', 'images-to-woo-products' ) . '</p>';

		echo '<form method="post" id="itpwc_form">';
		wp_nonce_field( 'itpwc_run_nonce' );

		echo '<input type="hidden" id="itpwc_attachment_ids" name="itpwc_attachment_ids" value="" />';
		echo '<input type="hidden" id="itpwc_per_image" name="itpwc_per_image" value="{}" />';

		echo '<div class="itpwc-card itpwc-settings">';
		echo '<h2>' . esc_html__( 'Settings', 'images-to-woo-products' ) . '</h2>';
		echo '<div class="itpwc-grid-compact">';

		echo '<div class="itpwc-setting"><label><input type="checkbox" name="itpwc_use_sku" value="1" /> ' .
			esc_html__( 'Auto-generate SKU (prefix + incremental)', 'images-to-woo-products' ) .
		'</label></div>';

		echo '<div class="itpwc-setting"><label>' . esc_html__( 'SKU Prefix', 'images-to-woo-products' ) . '</label>' .
			'<input type="text" name="itpwc_sku_prefix" value="" class="regular-text" /></div>';

		echo '<div class="itpwc-setting"><label>' . esc_html__( 'Global categories (optional)', 'images-to-woo-products' ) . '</label>' .
			'<select name="itpwc_global_cats[]" multiple="multiple" style="min-width:260px">' .
			'<option value="0">' . esc_html__( '— No category —', 'images-to-woo-products' ) . '</option>' .
			wp_kses_post( $cat_options_html ) .
			'</select></div>';

		echo '</div></div>'; // .itpwc-grid-compact / .itpwc-card

		echo '<div class="itpwc-toolbar">';
		echo '<button type="button" class="button button-secondary" id="itpwc_select_btn">' . esc_html__( 'Select / Upload images', 'images-to-woo-products' ) . '</button> ';
	echo '<button type="button" class="button" id="itpwc_clear_btn">' . esc_html__( 'Clear selection', 'images-to-woo-products' ) . '</button>';
		echo '</div>';

		// Table (без data- атрибути с HTML вътре).
		echo '<table class="wp-list-table widefat fixed striped itpwc-table">';
		echo '<thead><tr>' .
			'<th class="column-thumb">' . esc_html__( 'Image', 'images-to-woo-products' ) . '</th>' .
			'<th class="column-name">' . esc_html__( 'Name', 'images-to-woo-products' ) . '</th>' .
			'<th class="column-sku">' . esc_html__( 'SKU', 'images-to-woo-products' ) . '</th>' .
			'<th class="column-cats">' . esc_html__( 'Categories', 'images-to-woo-products' ) . '</th>' .
		'</tr></thead><tbody id="the-list"></tbody></table>';

		echo '<p class="submit"><button type="submit" name="itpwc_run" class="button button-primary">' . esc_html__( 'Create Products', 'images-to-woo-products' ) . '</button></p>';

		// Скрит шаблон за <option>-ите на категориите (правилно пречистен).
		echo '<div id="itpwc-cat-options-tpl" style="display:none">' . wp_kses_post( $cat_options_html ) . '</div>';

		// Inline JS (WordPress helper печата правилен <script>).
		echo wp_print_inline_script_tag( self::inline_js(), array( 'type' => 'text/javascript' ) );

		echo '</form></div>'; // .wrap
	}

	/** Build <option> list of product categories (escaped later with wp_kses_post). */
	private static function get_category_options_html() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

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

	/** Core bulk creator */
	private static function create_products( $ids, $per_image, $use_sku, $global_pref, $global_cats ) {
		$created = 0;
		$skipped = 0;

		foreach ( (array) $ids as $att_id ) {
			$att_id = absint( $att_id );
			if ( ! $att_id ) {
				$skipped++;
				continue;
			}

			$img = get_post( $att_id );
			if ( ! $img || 'attachment' !== $img->post_type ) {
				$skipped++;
				continue;
			}

			$title = get_the_title( $att_id );
			if ( ! $title ) {
				$file  = get_attached_file( $att_id );
				$title = $file ? sanitize_text_field( wp_basename( $file ) ) : 'Image';
			}

			$pid = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => '',
				),
				true
			);

			if ( is_wp_error( $pid ) ) {
				$skipped++;
				continue;
			}

			wp_set_object_terms( $pid, 'simple', 'product_type', false );
			set_post_thumbnail( $pid, $att_id );

			if ( ! empty( $global_cats ) ) {
				wp_set_object_terms( $pid, $global_cats, 'product_cat', true );
			}

			if ( $use_sku ) {
				$sku_prefix = $global_pref ? $global_pref : 'SKU';
				$sku_val    = $sku_prefix . '-' . $pid;
				update_post_meta( $pid, '_sku', sanitize_text_field( $sku_val ) );
			}

			$created++;
		}

		return array(
			'created' => $created,
			'skipped' => $skipped,
		);
	}

	/** Admin inline JS */
	private static function inline_js() {
		ob_start();
		?>
(function($){
	const frame = wp.media({ multiple: true, title: '<?php echo esc_js( __( 'Select images', 'images-to-woo-products' ) ); ?>' });
	const $ids   = $('#itpwc_attachment_ids');
	const $per   = $('#itpwc_per_image');
	const $table = $('.itpwc-table tbody');

	function catOptionsHTML(){
		// Взимаме безопасно пречистения шаблон от скрития контейнер.
		const holder = document.getElementById('itpwc-cat-options-tpl');
		return holder ? holder.innerHTML : '';
	}

	$('#itpwc_select_btn').on('click', function(e){
		e.preventDefault();
		frame.off('select').on('select', function(){
			const sel = frame.state().get('selection').toJSON() || [];
			const ids = sel.map(i => i.id);
			$ids.val(ids.join(','));
			$table.empty();
			const catOpts = catOptionsHTML();

			sel.forEach(item => {
				const name = item.title || item.filename || ('#' + item.id);
				const img  = (item.sizes && item.sizes.thumbnail && item.sizes.thumbnail.url) ? item.sizes.thumbnail.url : item.icon;
				const row = `
					<tr>
						<td class="column-thumb"><img src="${img}" style="max-width:80px;height:auto" alt="thumb"/></td>
						<td class="column-name"><input type="text" value="${(name || '').replaceAll('"','&quot;')}" class="widefat"/></td>
						<td class="column-sku"><input type="text" value="" class="regular-text"/></td>
						<td class="column-cats"><select multiple="multiple" style="min-width:200px">${catOpts}</select></td>
					</tr>`;
				$table.append(row);
			});
		});
		frame.open();
	});

	$('#itpwc_clear_btn').on('click', function(e){
		e.preventDefault();
		$ids.val('');
		$per.val('{}');
		$table.empty();
	});

	// Serialize per-image data on submit.
	$('#itpwc_form').on('submit', function(){
		const data = {};
		$table.find('tr').each(function(idx, tr){
			const $tr = $(tr);
			data[idx] = {
				name: $tr.find('.column-name input').val() || '',
				sku:  $tr.find('.column-sku input').val() || '',
				cats: ($tr.find('.column-cats select').val() || []).map(v => parseInt(v, 10) || 0)
			};
		});
		$per.val(JSON.stringify(data));
	});
})(jQuery);
		<?php
		return (string) ob_get_clean();
	}
}

endif;
