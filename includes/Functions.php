<?php
/**
 * Helper functions
 *
 * @package Standard Books for WooCommerce
 * @author Konekt
 */

use Konekt\WooCommerce\Standard_Books\Plugin;


/**
 * @since 1.0.0
 *
 * @return \Konekt\WooCommerce\Standard_Books\Plugin
 */
function wc_konekt_woocommerce_standard_books() {

	return Plugin::instance();
}