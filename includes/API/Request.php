<?php
/**
 * API Request
 *
 * @package Standard Books for WooCommerce
 * @author Konekt
 */

namespace Konekt\WooCommerce\Standard_Books\API;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1 as Framework;

defined( 'ABSPATH' ) or exit;


/**
 * Base API request object.
 *
 * @since 1.0.0
 */
class Request extends Framework\SV_WC_API_XML_Request {


	public function __construct( $path, $method, $params = [], $data = [] ) {

		$this->method       = $method;
		$this->path         = $path;
		$this->params       = $params;
		$this->request_data = [
			$this->get_root_element() => $data,
		];
	}


	protected function get_root_element() {

		return 'data';
	}


}