<?php
/**
 * Integration
 *
 * @package Standard Books for WooCommerce
 * @author Konekt
 */

namespace Konekt\WooCommerce\Standard_Books;

defined( 'ABSPATH' ) or exit;

class Product_Data_Store extends \WC_Product_Data_Store_CPT implements \WC_Object_Data_Store_Interface, \WC_Product_Data_Store_Interface {


	public function read( &$product ) {
		parent::read( $product );

		if ( ! empty( $product->get_sku() ) ) {

			if ( 'yes' === $this->get_integration()->get_option( 'stock_sync_allowed', 'no' ) ) {
				$this->refetch_product_stock( $product );
			}

			if ( 'yes' === $this->get_integration()->get_option( 'product_sync_allowed', 'no' ) ) {
				$this->refetch_product_data( $product );
			}
		}
	}


	/**
	 * Fetch product stock from API and update it
	 *
	 * @param \WC_Product $product
	 *
	 * @return void
	 */
	private function refetch_product_stock( &$product ) {
		$stock_cache_key = $this->get_stock_cache_key( $product->get_sku() );

		if ( false === ( $cached = $this->get_plugin()->get_cache( $stock_cache_key ) ) ) {
			$article_stock = $this->get_api()->get_article_stock( $product->get_sku() );

			if ( $article_stock ) {
				$new_stock_count = wc_stock_amount( $article_stock->Instock );

				if ( $new_stock_count != $product->get_stock_quantity() ) {
					$this->update_product_stock( $product->get_id(), $new_stock_count, 'set' );
				}

				if ( ! $product->managing_stock() ) {
					$product->set_manage_stock( true );
					$product->set_stock_status( $new_stock_count > 0 ? 'instock' : 'outofstock' );
				}

				if ( $new_stock_count > 0 && 'instock' !== $product->get_stock_status() ) {
					$product->set_stock_status( 'instock' );
				}

				if ( ! empty( $product->get_changes() ) ) {
					$product->save();
				}

			}

			$this->get_plugin()->set_cache( $stock_cache_key, $article_stock, MINUTE_IN_SECONDS * intval( $this->get_integration()->get_option( 'stock_refresh_rate', 15 ) ) );
		}
	}


	/**
	 * Fetch product data from API and update it
	 *
	 * @param \WC_Product $product
	 *
	 * @return void
	 */
	private function refetch_product_data( &$product ) {

		$article_cache_key = $this->get_article_cache_key( $product->get_sku() );

		if ( false === ( $cached = $this->get_plugin()->get_cache( $article_cache_key ) ) ) {
			$article = $this->get_api()->get_article( $product->get_sku() );

			if ( $article ) {
				if ( $article->VATCode ) {
					$available_taxes = $this->get_integration()->get_option( 'taxes', [] );

					if ( array_key_exists( $article->VATCode, $available_taxes ) ) {
						$woocommerce_tax_id    = (int) $available_taxes[ $article->VATCode ];
						$woocommerce_tax_class = '';

						foreach ( $this->get_integration()->get_all_tax_rates() as $tax_id => $tax ) {
							if ( (int) $tax_id === $woocommerce_tax_id ) {
								$woocommerce_tax_class = $tax['slug'];

								break;
							}
						}

						if ( ! empty( $woocommerce_tax_class ) ) {
							if ( $woocommerce_tax_class !== $product->get_tax_class() ) {
								$product->set_tax_class( $woocommerce_tax_class );
								$product->save();
							}
						}
					}
				}
			}

			$this->get_plugin()->set_cache( $article_cache_key, $article, DAY_IN_SECONDS * intval( $this->get_integration()->get_option( 'product_refresh_rate', 30 ) ) );
		}
	}


	public function get_stock_cache_key( $product_sku ) {
		return 'article_stock_' . $product_sku;
	}


	public function get_article_cache_key( $product_sku ) {
		return 'article_' . $product_sku;
	}


	/**
	 * Get plugin
	 *
	 * @return Konekt\WooCommerce\Standard_Books\Plugin
	 */
	protected function get_plugin() {
		return wc_konekt_woocommerce_standard_books();
	}


	/**
	 * Get API connector
	 *
	 * @return Konekt\WooCommerce\Standard_Books\API
	 */
	protected function get_api() {
		return $this->get_plugin()->get_integration()->get_api();
	}


	/**
	 * Get integration
	 *
	 * @return Konekt\WooCommerce\Standard_Books\Integration
	 */
	protected function get_integration() {
		return $this->get_plugin()->get_integration();
	}


}