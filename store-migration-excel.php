<?php
/**
 * Plugin Name: Migrate Import Export WooCommerce Store with Excel
 * Description: Migrate - import and/or export - your Products, Reviews, Customers, Orders to WooCommerce with Excel.
 * Version: 3.0.0-dev
 * Author: WPFactory
 * Author URI: https://wpfactory.com
 *
 * WC requires at least: 2.2
 * WC tested up to: 9.8
 *
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Created On: 09-09-2020
 * Updated On: 16-05-2025
 *
 * Text Domain: store-migration-products-orders-import-export-with-excel
 * Domain Path: /langs
 */

defined( 'ABSPATH' ) || exit;

defined( 'WPFACTORY_WC_PROCM_VERSION' ) || define( 'WPFACTORY_WC_PROCM_VERSION', '3.0.0-dev-20250516-1423' );

defined( 'WPFACTORY_WC_PROCM_FILE' ) || define( 'WPFACTORY_WC_PROCM_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfactory-wc-procm.php';

if ( ! function_exists( 'wpfactory_wc_procm' ) ) {
	/**
	 * Returns the main instance of WPFactory_WC_PROCM to prevent the need to use globals.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function wpfactory_wc_procm() {
		return WPFactory_WC_PROCM::instance();
	}
}

add_action( 'plugins_loaded', 'wpfactory_wc_procm' );

/**
 * includes.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-main.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/products.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/customers.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/orders.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/coupons.php';

/**
 * StoreMigrationWooCommerce class.
 *
 * @version 3.0.0
 */
class StoreMigrationWooCommerce extends StoreMigrationWooCommerceInit {

	public $plugin       = 'eshopMigrationWooCommerce';
	public $name         = 'Store Migration Products Orders Import Export with Excel for WooCommerce';
	public $shortName    = 'Store Migration';
	public $slug         = 'store-migration-woocommerce';
	public $dashicon     = 'dashicons-cart';
	public $proUrl       = 'https://extend-wp.com/product/products-reviews-orders-customers-woocommerce-migration-excel';
	public $menuPosition = '50';
	public $description  = 'Migrate -import and/or export - your Products, Reviews, Customers, Orders to WooCommerce with Excel';

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'adminPanels' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'BackEndScripts' ) );

		add_action( 'wpfactory_wc_procm_output_settings', array( $this, 'init' ) );

		add_action( 'admin_footer', array( $this, 'proModal' ) );

		$products = new StoreMigrationWooCommerce_Products();
		$users    = new StoreMigrationWooCommerce_Customers();
		$orders   = new StoreMigrationWooCommerce_Orders();
		$coupons  = new StoreMigrationWooCommerce_Coupons();

		add_action( 'wp_ajax_nopriv_import_process', array( $products, 'import_process' ) );
		add_action( 'wp_ajax_import_process',        array( $products, 'import_process' ) );

		add_action( 'wp_ajax_export_process',        array( $products, 'export_process' ) );
		add_action( 'wp_ajax_nopriv_export_process', array( $products, 'export_process' ) );

		add_action( 'wp_ajax_exportUsers_process',        array( $users, 'exportUsers_process' ) );
		add_action( 'wp_ajax_nopriv_exportUsers_process', array( $users, 'exportUsers_process' ) );

		add_action( 'wp_ajax_exportCoupons_process',        array( $coupons, 'exportCoupons_process' ) );
		add_action( 'wp_ajax_nopriv_exportCoupons_process', array( $coupons, 'exportCoupons_process' ) );

		add_action( 'wp_ajax_exportOrders_process',        array( $orders, 'exportOrders_process' ) );
		add_action( 'wp_ajax_nopriv_exportOrders_process', array( $orders, 'exportOrders_process' ) );

		add_action( 'wp_ajax_importCustomers_process',        array( $users, 'importCustomers_process' ) );
		add_action( 'wp_ajax_nopriv_importCustomers_process', array( $users, 'importCustomers_process' ) );

		add_action( 'wp_ajax_importCoupons_process',        array( $coupons, 'importCoupons_process' ) );
		add_action( 'wp_ajax_nopriv_importCoupons_process', array( $coupons, 'importCoupons_process' ) );

		add_action( 'wp_ajax_importOrders_process',        array( $orders, 'importOrders_process' ) );
		add_action( 'wp_ajax_nopriv_importOrders_process', array( $orders, 'importOrders_process' ) );

		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_query_var' ), 10, 2 );

		// Deactivation survey

		include plugin_dir_path( __FILE__ ) . '/lib/codecabin/plugin-deactivation-survey/deactivate-feedback-form.php';
		add_filter(
			'codecabin_deactivate_feedback_form_plugins',
			function ( $plugins ) {

				$plugins[] = (object) array(
					'slug'    => 'store-migration-products-orders-import-export-with-excel',
					'version' => '2.0',
				);

				return $plugins;
			}
		);

		register_activation_hook( __FILE__, array( $this, 'notification_hook' ) );

		add_action( 'admin_notices', array( $this, 'notification' ) );
		add_action( 'wp_ajax_nopriv_push_not', array( $this, 'push_not' ) );
		add_action( 'wp_ajax_push_not',        array( $this, 'push_not' ) );

	}

	/**
	 * notification.
	 */
	public function notification() {

		$screen = get_current_screen();
		if ( 'toplevel_page_store-migration-woocommerce' !== $screen->base ) {
			return;
		}

		// Check transient, if available display notice
		if ( get_transient( $this->plugin . '_notification' ) ) {
			?>
			<div class="updated notice eshopMigrationWooCommerce_notification">
				<a href="#" class='dismiss' style='float:right;padding:4px' >close</a>
				<h3><?php esc_html_e( 'Add your Email below & get ', 'store-migration-products-orders-import-export-with-excel' ); ?><strong style='color:#00a32a'>10%</strong><?php esc_html_e( ' in our PRO plugins! ', 'store-migration-products-orders-import-export-with-excel' ); ?></h3>
				<form method='post' id='eshopMigrationWooCommerce_signup'>
					<p>
					<input required type='email' name='woopei_email' />
					<input required type='hidden' name='product' value='3525' />
					<input type='submit' class='button button-primary' name='submit' value='<?php esc_html_e( 'Sign up', 'store-migration-products-orders-import-export-with-excel' ); ?>' />
					<i><?php esc_html_e( 'By adding your email you will be able to use your email as coupon to a future purchase at ', 'store-migration-products-orders-import-export-with-excel' ); ?><a href='https://extend-wp.com' target='_blank' >extend-wp.com</a></i>
					</p>

				</form>
			</div>
			<?php
		}
	}

	/**
	 * push_not.
	 */
	public function push_not() {
		delete_transient( $this->plugin . '_notification' );
	}

	/**
	 * notification_hook.
	 */
	public function notification_hook() {
		set_transient( $this->plugin . '_notification', true );
	}

	/**
	 * handle_custom_query_var.
	 */
	public function handle_custom_query_var( $query, $query_vars ) {
		if ( isset( $query_vars['s'] ) && ! empty( $query_vars['s'] ) ) {
			$query['s'] = esc_attr( $query_vars['s'] );
		}
		return $query;
	}

	/**
	 * BackEndScripts.
	 *
	 * @version 3.0.0
	 */
	public function BackEndScripts( $hook ) {

		$screen = get_current_screen();

		wp_enqueue_style( $this->plugin . 'adminCss', plugins_url( '/css/backend.css?v=olu', __FILE__ ) );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( $this->plugin . 'ui_style', plugins_url( '/css/jquery-ui.css', __FILE__ ) );
		wp_enqueue_script( $this->plugin . 'xlsx', plugins_url( '/js/xlsx.js', __FILE__ ), array( 'jquery' ), null, true );

		wp_enqueue_script( $this->plugin . 'filesaver', plugins_url( '/js/filesaver.js', __FILE__ ), array( 'jquery' ), null, true );

		wp_enqueue_script( $this->plugin . 'tableexport', plugins_url( '/js/tableexport.js', __FILE__ ), array( 'jquery' ), null, true );

		if ( ! wp_script_is( $this->plugin . '_fa', 'enqueued' ) ) {
			wp_enqueue_style( $this->plugin . '_fa', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
		}

		wp_enqueue_script( $this->plugin . 'adminJs', plugins_url( '/js/backend.js?v=olu', __FILE__ ), array( 'jquery', 'wp-color-picker', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-accordion' ), null, true );

		$localize = array(
			'RestRoot'       => esc_url_raw( rest_url() ),
			'plugin_url'     => plugins_url( '', __FILE__ ),
			'siteUrl'        => site_url(),
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'plugin_wrapper' => $this->plugin,
			'exportfile'     => plugins_url( '/js/tableexport.js', __FILE__ ),
		);
		wp_localize_script( $this->plugin . 'adminJs', $this->plugin, $localize );

	}

	/**
	 * init.
	 */
	public function init() {
		print "<div class='" . esc_attr( $this->plugin ) . "'>";
			$this->adminHeader();
			$this->adminSettings();
			$this->adminFooter();
		print '</div>';
	}

	/**
	 * proModal.
	 */
	public function proModal() {
		?>
		<div id="<?php print esc_html( $this->plugin ) . 'Modal'; ?>" style='display:none;'>
			<!-- Modal content -->
			<div class="modal-content">
			<div class='<?php print esc_html( $this->plugin ); ?>clearfix'><span class="close">&times;</span></div>
			<div class='<?php print esc_html( $this->plugin ); ?>clearfix'>
				<div class='<?php print esc_html( $this->plugin ); ?>columns2'>
					<center>
						<img style='width:90%' src='<?php echo esc_url( plugins_url( 'images/' . esc_html( $this->slug ) . '-pro.png', __FILE__ ) ); ?>' style='width:100%' />
					</center>
				</div>

				<div class='<?php print esc_html( $this->plugin ); ?>columns2'>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'Import Variable, Affiliate, Subscription Products with excel', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'WPML support for Product Translations Import ', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'ACF & YOAST SEO META support for Import in products', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'export Variable, Affiliate, Subscription Products to excel', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'Products custom Fields Support', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'Bulk import taxonomy terms for products with excel', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'import Product Reviews with excel', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'export Product Reviews to excel', 'store-migration-products-orders-import-export-with-excel' ); ?></strong></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'import Orders with variable products', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'import Orders & apply Coupons', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'import/export WooCommerce Subscriptions', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'create Customer during Order import', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( 'Customers custom fields Support', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p><i class='fa fa-check'></i> <?php esc_html_e( '.. and a lot more!', 'store-migration-products-orders-import-export-with-excel' ); ?></p>
					<p class='bottomToUp'><center><a target='_blank' class='proUrl' href='<?php print esc_url( $this->proUrl ); ?>'><?php esc_html_e( 'GET IT HERE', 'store-migration-products-orders-import-export-with-excel' ); ?></a></center></p>
				</div>
			</div>
			</div>
		</div>
		<?php
	}

}

$instantiate = new StoreMigrationWooCommerce();
