<?php
/**
 * WooCommerce loop item — single MMI spec card.
 *
 * @package MMI_Mobile_List
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

static $mmi_menu_index = 0;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
echo MMI_MLL_Renderer::render_card( $product, $mmi_menu_index );
++$mmi_menu_index;