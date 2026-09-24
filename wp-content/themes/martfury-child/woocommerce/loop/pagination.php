<?php
/**
 * Pagination catalog – texte în română (Loading / paginare).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
$format  = isset( $format ) ? $format : '';

$next_text = sprintf( '%s <i class="icon-chevron-right"></i>', 'Pagina următoare' );
$prev_text = sprintf( '<i class="icon-chevron-left"></i> %s', 'Pagina anterioară' );

$type = 'list';
if ( function_exists( 'martfury_get_option' ) && martfury_get_option( 'catalog_nav_type' ) == 'infinite' ) {
	$next_text = '<span id="martfury-products-loading" class="dots-loading"><span>.</span><span>.</span><span>.</span>Se încarcă<span>.</span><span>.</span><span>.</span></span>';
	$type      = 'plain';
}

if ( $total <= 1 ) {
	return;
}
?>
<nav class="woocommerce-pagination" aria-label="Paginare produse">
	<?php
	echo paginate_links( apply_filters( 'woocommerce_pagination_args', array( // WPCS: XSS ok.
		'base'      => $base,
		'format'    => $format,
		'add_args'  => false,
		'current'   => max( 1, $current ),
		'total'     => $total,
		'prev_text' => $prev_text,
		'next_text' => $next_text,
		'type'      => $type,
		'end_size'  => 3,
		'mid_size'  => 3,
	) ) );
	?>
</nav>
