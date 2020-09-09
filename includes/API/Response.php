<?php
/**
 * API Response
 *
 * @package Standard Books for WooCommerce
 * @author Konekt
 */

namespace Konekt\WooCommerce\Standard_Books\API;

use SkyVerge\WooCommerce\PluginFramework\v5_6_1 as Framework;

defined( 'ABSPATH' ) or exit;


/**
 * Base API Response object.
 *
 * @since 1.0.0
 */
class Response extends Framework\SV_WC_API_XML_Response {


	public $messages = [];


	/**
	 * Build an XML object from the raw response.
	 *
	 * @since 4.3.0
	 * @param string $raw_response_xml The raw response XML
	 */
	public function __construct( $raw_response_xml ) {
		// LIBXML_NOCDATA ensures that any XML fields wrapped in [CDATA] will be included as text nodes
		$parsed_xml = @simplexml_load_string( $raw_response_xml, 'SimpleXMLElement', LIBXML_NOCDATA );

		if ( ! $parsed_xml || false === stristr( $raw_response_xml, '<data>' ) ) {
			// Dirty hack to get valid XML
			$raw_response_xml  = str_replace( "standalone='yes'?>", "standalone='yes'?><response>", $raw_response_xml );
			$raw_response_xml .= '</response>';
		}

		parent::__construct( $raw_response_xml );

		if ( ! empty( $this->response_data->error ) ) {
			if ( is_array( $this->response_data->error ) ) {
				foreach ( $this->response_data->error as $error ) {
					throw new Framework\SV_WC_Plugin_Exception( $error->{'@attributes'}->description, $error->{'@attributes'}->code );
				}

			} else {
				throw new Framework\SV_WC_Plugin_Exception( $this->response_data->error->{'@attributes'}->description, $this->response_data->error->{'@attributes'}->code );
			}
		}

		if ( ! empty( $this->response_data->message ) ) {
			if ( is_array( $this->response_data->message ) ) {
				foreach ( $this->response_data->message as $message ) {
					$this->add_message( $message->{'@attributes'}->description );
				}

			} else {
				$this->add_message( $this->response_data->message->{'@attributes'}->description );
			}
		}

		if ( ! empty( $this->response_data->data ) ) {
			$this->response_data = $this->response_data->data;
		}
	}


	public function add_message( $message ) {
		$this->messages[] = $message;
	}


	public function get_messages() {
		return $this->messages;
	}


}