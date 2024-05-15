<?php
/**
 * Recharts Dashboard Widget.
 *
 * @package RechartsDashboardWidget
 */

defined( 'ABSPATH' ) || die( 'Access denied' );

/**
 * Dashboard widget API class.
 */
class Iriz_Widget_API {
	/**
	 * API endpoint namespace.
	 *
	 * @var string
	 */
	private $namespace = 'iriz-widget-api/v1';

	/**
	 * API endpoint base.
	 *
	 * @var string
	 */
	private $endpoint_base = 'data';

	/**
	 * API endpoint data table.
	 *
	 * @var string
	 */
	private $table = 'chartdata';

	/**
	 * Construtor.
	 * Configuring API endpoints.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Registering endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			$this->namespace,
			'/' . $this->endpoint_base,
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_table_data' ),
			)
		);
	}

	/**
	 * Query table records for endpoints.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 */
	public function get_table_data( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		$days  = ( null !== $request->get_param( 'days' ) ) ? $request->get_param( 'days' ) : '7';
		$data  = $wpdb->get_results( $wpdb->prepare( "SELECT name, uv, pv, amt, dys FROM $table WHERE dys = %d", $days ), ARRAY_A );
		return new WP_REST_Response( $data, 200 );
	}
}
