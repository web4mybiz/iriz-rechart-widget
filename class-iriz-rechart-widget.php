<?php
/**
 * Plugin Name: Recharts Dashboard Widget
 *
 * @package RechartsDashboardWidget
 *
 * Description: A simple plugin to load charts using recharts.org
 * Plugin URI: https://github.com/web4mybiz/dashboard-widget
 * Version: 1.0
 * Requires at least: 3.0
 * Requires PHP: 5.0
 * Author: Rizwan Iliyas
 * Author URI: https://github.com/web4mybiz
 * License:  GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:
 * Text Domain: irizweb-dashboard-widget
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || die( 'Access denied' );

// Endpoint URL constant.
define( 'CHART_DATA_URL', home_url( '/wp-json/iriz-widget-api/v1/data/' ) );

// Include the class file for API endpoint.
if ( ! class_exists( 'Iriz_Widget_API' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-iriz-widget-api.php';
}
/**
 * Recharts Dashboard base class
 *  */
class Iriz_Rechart_Widget {
	/**
	 * Chart data table.
	 *
	 * @var string
	 */

	private $table = 'chartdata';

	/**
	 * Constructor.
	 * Configuring sctips and database on plugin activation.
	 */
	public function __construct() {
		// Enqueue required scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Dashboard widget hook.
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
	}

	/**
	 * Function to load scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'babel', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), wp_rand( 1, 100 ), true );
		wp_enqueue_script( 'prop-types', 'https://unpkg.com/prop-types/prop-types.min.js', array(), wp_rand( 1, 100 ), true );
		wp_enqueue_script( 'recharts', plugins_url( 'recharts/umd/Recharts.js', __FILE__ ), array( 'react', 'react-dom' ), wp_rand( 1, 100 ), true );
	}

	/**
	 * Create table on plugin activation.
	 */
	public function plugin_activation() {
		$this->create_table();
	}

	/**
	 * Delete table on plugin deactivation.
	 */
	public function plugin_deactivation() {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $table ) );
	}

	/**
	 * Query for table creation.
	 */
	public function create_table() {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			$sql = "CREATE TABLE $table(
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `uv` mediumint(10) NOT NULL,
                `pv` mediumint(10) NOT NULL,
                `amt` mediumint(10) NOT NULL,
                `dys` mediumint(10) NOT NULL,
                PRIMARY KEY (`id`)
            )";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
		// Insert sample data.
		$this->insert_sample_data();
	}

	/**
	 * Dashboard widget sample data for chart.
	 */
	public function insert_sample_data() {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$sample_data = array(
			array( 'Page A', '4000', '2400', '2400', '7' ),
			array( 'Page B', '6000', '1200', '2100', '7' ),
			array( 'Page C', '2000', '6000', '2500', '7' ),
			array( 'Page D', '1000', '300', '3500', '15' ),
			array( 'Page A', '4000', '2400', '2400', '15' ),
			array( 'Page B', '6000', '1200', '2100', '15' ),
			array( 'Page C', '2000', '6000', '2500', '15' ),
			array( 'Page D', '8000', '300', '3500', '30' ),
			array( 'Page A', '4000', '2400', '2400', '30' ),
			array( 'Page B', '6000', '1200', '2100', '30' ),
			array( 'Page C', '2000', '6000', '2500', '30' ),
			array( 'Page D', '1000', '300', '3500', '30' ),
		);
		foreach ( $sample_data as $data ) {
			$wpdb->insert(
				$table,
				array(
					'name' => $data[0],
					'uv'   => $data[1],
					'pv'   => $data[2],
					'amt'  => $data[3],
					'dys'  => $data[4],
				),
				array( '%s', '%d', '%d', '%d', '%d' )
			);
		}
	}
	/**
	 * Dashboard widget callback function.
	 */
	public function recharts_dashboard_widget() {
		?>
		<div id="widget-loader"></div>
		<script type="text/babel">
			const { useState, useEffect} = React;

			function LoadWidget({data}) {
				
				return (
					<Recharts.LineChart width={500} height={300} data={data} margin={{ top: 50, right: 20, bottom: 5, left: 0 }}>
						<Recharts.Line type="monotone" dataKey="uv" stroke="#8884d8" />
						<Recharts.CartesianGrid stroke="#ccc" strokeDasharray="5 5" />
						<Recharts.XAxis dataKey="name" />
						<Recharts.YAxis />
						<Recharts.Tooltip />
					</Recharts.LineChart>
				);
			}

			const App = () =>{
				const [selectedValue, setSelectedValue] = useState('7');
				const [chartData, setChartData] = useState([]);
				const divStyle = {
					'margin-top': '-44px',
					'display': 'flex',
					'flex-direction': 'column',
					'align-content': 'space-around',
					'align-items': 'center'
				};

				const fetchChartData = (days) =>{
					const url = '<?php echo esc_js( CHART_DATA_URL ); ?>?days=' + days;
					fetch(url)
						.then( response => response.json())
						.then( data => {
							setChartData(data);
						})
						.catch( error => {
							console.error('Error fetching chart data', error);
						})
				};
				
				useEffect( () => {
					fetchChartData(selectedValue)
				},[selectedValue]);
				
				const handleSelectChange = (e) => {
					setSelectedValue(e.target.value);
				};
				return (
					<div style={divStyle}>
						<select value={selectedValue} onChange={handleSelectChange}>
							<option value="7">7 days</option>
							<option value="15">15 days</option>
							<option value="30">30 days</option>
						</select>
						<LoadWidget data={chartData}/>
					</div>
				);
			};

			const container = document.getElementById('widget-loader');
			const root = ReactDOM.createRoot(container);
			root.render(<App />)
		</script>
		<?php
	}
	/**
	 * Configuring dashboard widget.
	 */
	public function dashboard_widget() {
		wp_add_dashboard_widget( 'recharts_dashboard_widget', 'Recharts Dashboard Widget', array( $this, 'recharts_dashboard_widget' ) );
	}
}
if ( class_exists( 'Iriz_Rechart_Widget' ) ) {
	new Iriz_Rechart_Widget();
}

if ( class_exists( 'Iriz_Widget_API' ) ) {
	new Iriz_Widget_API();
}
