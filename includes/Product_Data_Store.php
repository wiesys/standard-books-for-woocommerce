<?php
/**
 * Integration
 *
 * @package Standard Books for WooCommerce
 * @author Konekt
 */

namespace Konekt\WooCommerce\Standard_Books;

defined( 'ABSPATH' ) or exit;

class Product_Data_Store {


	public function read( $product ) {

		if ( 'yes' === $this->get_integration()->get_option( 'stock_sync_allowed', 'no' ) ) {
			$this->refetch_product_stock( $product );
		}

		if ( 'yes' === $this->get_integration()->get_option( 'product_sync_allowed', 'no' ) ) {
			$this->refetch_product_data( $product );
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
		$sku = $product->get_sku();
		$stock_cache_key = $this->get_plugin()->get_stock_cache_key( $sku );
		$article_stock   = $this->get_plugin()->get_cache( $stock_cache_key );

		if ( false === $article_stock ) {
			$all_stock_cache_key = $this->get_plugin()->get_all_stock_cache_key();
			$all_stock   = $this->get_plugin()->get_cache( $all_stock_cache_key );

			if ( false === $all_stock ) {
				$all_stock = $this->get_api()->get_all_stock();
				$this->get_plugin()->set_cache( $all_stock_cache_key, $all_stock, MINUTE_IN_SECONDS * intval( $this->get_integration()->get_option( 'stock_refresh_rate', 15 ) ) );
			}
			
			foreach ( $all_stock as $item ) {
				if ( $item->Code === $sku ) {
					$article_stock = $item;
					break;
				}
			}

			$this->get_plugin()->set_cache( $stock_cache_key, $article_stock, MINUTE_IN_SECONDS * intval( $this->get_integration()->get_option( 'stock_refresh_rate', 15 ) ) );
		}

		$product->set_manage_stock( true );

		if ( ! empty( $article_stock ) ) {
			$new_stock_count = wc_stock_amount( $article_stock->Instock );

			$product->set_stock_quantity( $new_stock_count );

			if ( $new_stock_count > 0 ) {
				$product->set_stock_status( 'instock' );
				$product->set_backorders( 'yes' ); // just in case, but "in general" not necessary
			} else {
				$product->set_backorders( 'notify' );
			}
		} else {
			$product->set_backorders( 'notify' );
		}

		if ( ! empty( $product->get_changes() ) ) {
			$product->save();
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
		$sku = $product->get_sku();
		$article_cache_key = $this->get_plugin()->get_article_cache_key( $sku );

		if ( false === ( $article = $this->get_plugin()->get_cache( $article_cache_key ) ) ) {
			$all_article_cache_key = $this->get_plugin()->get_all_article_cache_key();
			$all_article   = $this->get_plugin()->get_cache( $all_article_cache_key );

			if ( false === $all_article ) {
				$all_article = $this->get_api()->get_all_articles();
				$this->get_plugin()->set_cache( $all_article_cache_key, $all_article, MINUTE_IN_SECONDS * intval( $this->get_integration()->get_option( 'stock_refresh_rate', 15 ) ) );
			}
			
			foreach ( $all_article as $item ) {
				if ( $item->Code === $sku ) {
					$article = $item;
					break;
				}
			}

			$this->get_plugin()->set_cache( $article_cache_key, $article, MINUTE_IN_SECONDS * intval( $this->get_integration()->get_option( 'stock_refresh_rate', 15 ) ) );

			if ( $article ) {
				if ( $article->VATCode && ! is_object( $article->VATCode ) ) {
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
		}
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
		return $this->get_integration()->get_api();
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