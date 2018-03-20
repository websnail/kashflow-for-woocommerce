<?php
/*
Plugin Name: Kashflow for Woocommerce
Plugin URI:
Description: Kashflow addon for woocommerce
Version: 0.0.91
Maintained By: WebSnail (2018)
Original Author: DeviceSoftware
Author URI:

Text Domain: ds-kashflow
Domain Path: /languages/ 

* 
*/

/*  Copyright 2018  Snailsolutions  (email : info@snailsolutions.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA    

* 0.0.91 - 2018-03-19 RELEASE
*   Update: Fixed for use with WC 3.x
*   Feature: No longer uses order_id as Kashflow->InvoiceID, instead stored the invoice_id in post_meta
*   Feature: Allows invoice generation when used with Purchase_order addon
*   Feature: Does not create erronous Invoice totals in KF when product unit price requires in more than 2 decimal points


* 0.0.9 - 2014-06-21
*   Update: Added support for Coupons by adding a discount line to invoices
*
* 0.0.8 - 2014-05-12
*   Update: Added support for WooCommerce Sequential Order Numbers
*
* 0.0.7 - 2014-01-22
*   Update: Changed Woocommerce Settings tabs inline with version 2.1.0
*
* 0.0.6 - 2013-10-24
*   Feature: option to produce quotes or invoices.
*
* 0.0.5 - 2013-10-03
*   Feature: logging debug information.
*   Update: Product SKU conversion.
* 
* 0.0.4 - 2013-07-09
*   Feature: assign a customer source of business to woocommerce.
* 
* 0.0.3 - 2013-06-10
*   Feature: registering products within kashflow - allow stock consistancy between purchase and sales
*   Fix: VAT patch
*
* 0.0.2 - 2013-05-27
*   Fix: Username & password API error resolved
*
* 0.0.1 - 2013-05-16
*   Initial release.
*/

// definitions
define( "DS_KASHFLOW", "kashflow" );
define( "DS_KASHFLOW_SOAP_URL", "https://securedwebapp.com/api/service.asmx" );
define( "DS_KASHFLOW_ACCOUNTS", "accounts" );
define( "DS_KASHFLOW_PLUGINPATH", "/" . plugin_basename( dirname( __FILE__ ) ) );

// check for abspath
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Ds_Kashflow' ) ) {

	class Ds_Kashflow {

		//current version
		protected $version = '0.0.9';

		protected $plugin_path;
		protected $plugin_url;
		protected $template_url;

		//public kashflow properties
		public $last_error;
		public $api;

		//debug properties
		private $debug;
		private $log;

		/**
		 * plugin url
		 *
		 */
		public function plugin_url() {
			return $this->plugin_url;
		}

		/**
		 * plugin path
		 *
		 */
		public function plugin_path() {
			return $this->plugin_path;
		}

		static private $instance = null;

		/**
		 * single instance
		 *
		 */
		static public function getInstance() {
			//late static binding
			$class = get_called_class();
			if ( ! isset( self::$instance ) ) {
				self::$instance = new $class();
			}

			return self::$instance;
		}

		/**
		 * constructor
		 *
		 */
		public function __construct() {

			global $woocommerce;

			// Define version constant
			define( 'DS_KASHFLOW_VERSION', $this->version );

			// plugin path
			$this->plugin_path = dirname( __FILE__ );

			// plugin url
			$this->plugin_url = plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );

			// Logs
			if ( in_array( get_option('ds_kashflow_debug'), array(1,'yes',true))) {
				$this->debug = true;
				$this->log   = new WC_Logger();
                //DEBUG
                //$this->log->add( DS_KASHFLOW , "Log Started - ".gmdate() );
			}

			// include files
			$this->includes();

			// create instance of the kashflow api
			$this->api = new KF_Api();

			if ( is_admin() ) {
				if ( ! defined( 'DOING_AJAX' ) ) {
					// Installation
					register_activation_hook( __FILE__, array( $this, 'install' ) );
					if ( get_option( 'ds_kashflow_db_version' ) !== $this->version ) {
						add_action( 'init', array( $this, 'install' ), 1 );
					}

					// modify settings tabs
					add_filter( 'woocommerce_get_settings_pages', array( $this, 'kashflow_settings_page' ) );
					add_action( 'woocommerce_settings_start', array( $this, 'account_settings' ) );
					add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ) );
					add_action( 'woocommerce_settings_tabs_accounts', array( $this, 'admin_settings' ) );
					add_action( 'woocommerce_update_options_accounts', array( $this, 'save_admin_settings' ) );
					add_action( 'woocommerce_admin_field_dstestapi', array( $this, 'admin_field_testapi' ) );
					add_action( 'woocommerce_admin_field_label', array( $this, 'admin_field_label' ) );


					// load admin scripts
					add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

					// add thickbox
					add_thickbox();

					add_action( 'woocommerce_order_actions_end', array( $this, 'order_actions_end' ) );
				}

				add_action( 'wp_ajax_ds_kashflow_invoice', array( $this, 'email_kashflow_invoice' ) );
				add_action( 'wp_ajax_test_api', array( $this, 'test_api_callback' ) );
			}

			add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 2 );
			add_action( 'woocommerce_payment_complete', array( $this, 'payment_complete' ) );


		} // end constructor

		private function includes() {
			include_once( 'classes/class-ds-api.php' );

			include_once( 'classes/class-ds-base.php' );
			include_once( 'classes/class-ds-bank-account.php' );
			include_once( 'classes/class-ds-customer.php' );
			include_once( 'classes/class-ds-invoice.php' );
			include_once( 'classes/class-ds-invoice-line.php' );
			include_once( 'classes/class-ds-invoice-payment.php' );
			include_once( 'classes/class-ds-quote.php' );
			include_once( 'classes/class-ds-quote-line.php' );
			include_once( 'classes/class-ds-sub-product.php' );

		}

		/**
		 * log a message when debug is enabled
		 *
		 * @param mixed $msg
		 */
		private function logit( $msg ) {
			if ( $this->debug ) {
				$this->log->add( DS_KASHFLOW, $msg );
			}
			if ($this->debug) {
				if (version_compare(WC_VERSION, '3.0', '<')) {
					if (empty($this->log)) {
						$this->log = new WC_Logger();
					}
					$this->log->add(DS_KASHFLOW, $msg);
				} else {
					if (empty($this->log)) {
						$this->log = wc_get_logger();
					}
					$this->log->log('notice', $msg, array('source' => DS_KASHFLOW));
				}
			}

		} //end logit

		/**
		 * install
		 *
		 * currently not used in this version
		 *
		 */
		public function install() {

		}

		/**
		 * Generate a Kashflow Invoice or Quote for a WooCommerce order
         * @param   $order_id -> WooCommerce Order_id (same as WP Post_id)
         * @return  $kf_invoice_id -> The Kashflow Invoice ID generated from the Order
		 */
		private function update_customer( $order_id ) {

			$kf_invoice_id = false;
			$order = new WC_Order( $order_id );
			$this->logit( 'order - ' . print_r( $order, true ) );

			// update/add customer details

			// create a KashFlow customer class instance
			$customer = new KF_Customer();
			if ( ! empty( $order->get_billing_company() ) ) {
				$customer->Name = $order->get_billing_company();
			} else {
				$customer->Name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			}
			$customer->Contact                = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$customer->ContactFirstName       = $order->get_billing_first_name();
			$customer->ContactLastName        = $order->get_billing_last_name();
			$customer->Telephone              = $order->get_billing_phone();
			$customer->Email                  = $order->get_billing_email();
			$customer->Address1               = $order->get_billing_address_1();
			$customer->Address2               = $order->get_billing_address_2();
			$customer->Address3               = $order->get_billing_city() . ' ' . $order->get_billing_state();
			$customer->Address4               = $order->get_billing_country();
			$customer->Postcode               = $order->get_billing_postcode();
			$customer->CustHasDeliveryAddress = 1;
			$customer->DeliveryAddress1       = $order->get_shipping_address_1();
			$customer->DeliveryAddress2       = $order->get_shipping_address_2();
			$customer->DeliveryAddress3       = $order->get_shipping_city() . ' ' . $order->get_shipping_state();
			$customer->DeliveryAddress4       = $order->get_shipping_country();
			$customer->DeliveryPostcode       = $order->get_shipping_postcode();

			$this->logit( 'customer - ' . print_r( $customer, true ) );

			// update KashFlow with customer information from WooCommerce Order
			$customer_id = 0;
			if ( $existing_customer = $this->api->get_customer_by_email( $order->get_billing_email() ) ) {
				// existing customer
				$customer->data = array_merge( $existing_customer->data, $customer->data );
				//TODO Check how arrays would merge the existing and new customer? Does new overwrite/replace old even when empty?

				$this->api->update_customer( $customer );
				$customer_id = $existing_customer->CustomerID;
				if ( $customer_id ) {
					$this->logit( 'Existing Customer id: ' . $customer_id );
				} else {
					$this->logit( 'Existing Customer error: ' . $this->api->get_last_error() );
				}
			} else {
				// add customer source of business
				$customer->Source = get_option( 'ds_kashflow_source_of_business' );

				// Insert New Customer
				$customer_id = $this->api->insert_customer( $customer );
				if ( $customer_id ) {
					$this->logit( 'New Customer id: ' . $customer_id );
				} else {
					$this->logit( 'New Customer error: ' . $this->api->get_last_error() );
				}
			}

			$order_method = get_option( 'ds_kashflow_order_method' );
			if ( ! empty( $customer_id ) ) {

				// get order totals
				$totals                  = $order->get_order_item_totals();
				$items                   = $order->get_items();
				$kf_order                = ( $order_method === 'quote' ) ? new KF_Quote( ! count( $items ) ) : new KF_Invoice( ! count( $items ) );
				$order_number            = $order->get_order_number() ? $order->get_order_number() : $order_id;

                /*
                 * Specifying the InvoiceNumber would overwrite anything in KashFlow that already existed for the same reference
                 * So, we disabled this immediately!
                 */
				//DISABLED $kf_order->InvoiceNumber = $order_number;

				$kf_order->Customer      = $customer->Name;
				$kf_order->CustomerID    = $customer_id;

				$order_tax      = $order->get_total_tax();
				$order_discount = $order->get_discount_total();
				$order_net      = $order->get_total() - $order_tax;

				// START Set the currency info
				$order_base = get_post_meta( $order_id, '_order_total_base_currency', true );
				//$total_currency = get_post_meta( $order_id, '_order_total', true );
				$calc_exchg_rate = $order->get_total() / $order_base;

				/**
				 * If Aelia_WC_EU_VAT_Assistant is installed we could get the VAT exchange rate data
                 * but it's usually based on a different rate provider (eg: HMRC) which is often
                 * giving a different rate to the one used by the currency selector
				 */
				//$eu_vat_data = get_post_meta( $order_id, '_eu_vat_data', false);

				$kf_order->CurrencyCode = $order->get_currency();
				$kf_order->ExchangeRate = $calc_exchg_rate;

				// END Currency Info

				$kf_order->NetAmount  = $order_net;
				$kf_order->VATAmount  = $order_tax;
				$total_amount         = $order_net + $order_tax;
				$kf_order->AmountPaid = $total_amount;

				/**
				 * Apply any payment module specific data here
				 */

				$purchase_order_ref = '';

				if($order->get_payment_method() == 'purchase_order') {
					$po_data = get_post_meta( $order_id, '_purchase_order_data', false);
					$purchase_order_ref = array_key_exists('purchase_order_number', $po_data) ? $po_data['purchase_order_number'] : '';
                }
                // Set Purchase Order reference with default Order ID if nothing else set
                $kf_order->CustomerReference = !empty($purchase_order_ref) ? $purchase_order_ref : 'Order: '.$order_id;

				$this->logit( 'sales invoice - ' . print_r( $kf_order, true ) );
				// get sale of goods productID
				$default_sale_of_goods = get_option( 'ds_kashflow_sale_goods_type' );

				$lines = array();
				$cnt   = 0;
				foreach ( $items as $item ) {
					// get product
					$product = get_product( $item['product_id'] );
					$line    = ( $order_method === 'quote' ) ? new KF_Quote_Line( false ) : new KF_Invoice_Line( false );

					// HACK 0 -o swicks :Match the pennies / totals
					$line_sub_total = $item['line_subtotal'];

					/*
					 * If the line rate is within 2 decimal places we input line as experience with line quantity and unit pricing
					 * If line will not round to 2 decimal places we use description to record the actual quantity and unit cost data
					 *
					 * This is necessary because KashFloow does not allow greater precision than 2 decimal points so small/low value
					 * items can banjax the line totals
					 */
					$line_adjust = false;
					if ( round( $line_sub_total / $item['qty'], 2 ) == $line_sub_total / $item['qty'] ) {
						$qty       = $item['qty'];
						$unit_rate = round( $line_sub_total / $qty, 2 );
					} else {
						$line_adjust  = true;    // Indicates we have a 2dp+ adjusted item
						$qty          = 1;
						$unit_rate    = $line_sub_total;
						$item['name'] .= " <br>\r\n(Quantity:{$item['qty']} @ " . round(($line_sub_total / $item['qty']),4) . " (each)";
						//TODO Check if use \n or <BR /> markup to newline the additional information
					}

					$line->Description = $item['name'];
					$line->Quantity    = $qty;
					$line_rate         = $unit_rate * $qty;
					$line_total        = $line_sub_total + $item['line_tax'];
					$line->Rate        = $unit_rate;
					$line->VatAmount   = $line_total - $line_rate;
					$line->ChargeType  = isset( $item['charge_type'] ) ? (int) $item['charge_type'] : $default_sale_of_goods;
					$line->LineID      = $cnt ++;
					$vat_rate          = $this->calc_item_tax_rate( $product );
					$line->VatRate     = $vat_rate;

					$sku = $product->get_sku();
					if ( ! empty( $sku ) ) {
						if ( $sub_product = $this->api->get_sub_product_by_code( $sku ) ) {
							$line->ProductID = $sub_product['id'];
						} else {
							// Don't add products for any 2dp+ adjusted items
							if ( $line_adjust !== true ) {
								$sub_product               = new KF_Sub_Product();
								$sub_product->ParentID     = $default_sale_of_goods;
								$sub_product->Name         = $item['name'];
								$sub_product->Description  = $item['name'];
								$sub_product->Code         = $sku;
								$sub_product->VatRate      = $vat_rate;
								$sub_product->Price        = $unit_rate;
								$sub_product->Managed      = 1;
								$sub_product->QtyInStock   = 0;
								$sub_product->StockWarnQty = 0;
								$res                       = $this->api->update_sub_product_by_code( $sub_product );
								$line->ProductID           = $res;
							}
						}
					}


					$this->logit( 'sales quote/invoice item - ' . print_r( $line, true ) );
					$lines[] = $line;
				}

				//discount coupons
				if ( $order_discount > 0 ) {
					$line              = ( $order_method === 'quote' ) ? new KF_Quote_Line( false ) : new KF_Invoice_Line( false );
					$line->Description = __( 'Discount', 'ds-kashflow' );
					$line->Quantity    = 1;
					$line->Rate        = 0 - $order_discount;
					$line->VatAmount   = 0;
					$line->ChargeType  = isset( $item['charge_type'] ) ? (int) $item['charge_type'] : $default_sale_of_goods;
					$line->LineID      = $cnt ++;
					$line->VatRate     = 0;

					$this->logit( 'sales quote/invoice discount item - ' . print_r( $line, true ) );

					$lines[] = $line;
				}


				// add shipping item
				// TODO 4 -o swicks : always displays as order_shipping returns "0.00" if empty
				if ( $order->get_shipping_total() ) {
					$line              = ( $order_method === 'quote' ) ? new KF_Quote_Line( false ) : new KF_Invoice_Line( false );
					$line->Description = __( 'Shipping cost', 'ds-kashflow' );
					$line->Quantity    = 1;
					$line->Rate        = $order->get_shipping_total();
					$line->VatAmount   = $order->get_shipping_tax();
					$line->ChargeType  = get_option( 'ds_kashflow_shipping_type' ) ? get_option( 'ds_kashflow_shipping_type' ) : $default_sale_of_goods;
					$line->LineID      = $cnt ++;
					$line->VatRate     = $this->calc_shipping_tax_rate();

					$this->logit( 'sales quote/invoice shipping item - ' . print_r( $line, true ) );

					$lines[] = $line;
				}
				$kf_invoice_id = ( $order_method === 'quote' ) ? $this->api->insert_quote( $kf_order, $lines ) : $this->api->insert_invoice( $kf_order, $lines );
				if ( $kf_invoice_id ) {
				    // Save the Kashflow Invoice Ref to the order_meta
					update_post_meta($order_id, 'kashflow_invoice_id', $kf_invoice_id);

					$sales_type = $order_method === 'quote' ? 'Quote' : 'Invoice';
					$this->logit( 'Sales ' . $sales_type . ' ID: ' . $kf_invoice_id );
				} else {
					$this->logit( 'Sales quote/invoice error: ' . $this->api->get_last_error() );
				}
			} else {
				$this->logit( 'No customer id' );
			}
			return $kf_invoice_id;
		}

		/**
		 * checkout_order_processed
		 *
		 * this action runs once the order has been processed
		 *
		 * @param int $order_id
		 * @param array $posted
		 */
		public function checkout_order_processed( $order_id, $posted ) {
			$this->logit( 'checkout_order_processed - called' );

			$order_method        = get_option( 'ds_kashflow_order_method' );
			//$invoice_on_complete = get_option( 'ds_kashflow_invoice_on_complete' );
			$invoice_on_complete = in_array( get_option('ds_kashflow_invoice_on_complete'), array(1,'yes',true)) ? true : false;

			// We need order instance so we can test payment_method, etc...
			$order        = new WC_Order( $order_id );

			if ( $order_method == 'invoice' && $invoice_on_complete && ($order->get_payment_method() != 'purchase_order')) {
                // Nothing to do. Order has been processed but not yet PAID and it's not a purchase order
			} else {
			    // Quote, Purchase Order or payment is not required to create the KF Invoice
				$order_number = $order->get_order_number() ? $order->get_order_number() : $order_id;
				$kf_invoice_id = $this->update_customer( $order_number );
			}
		}

		/**
		 * payment complete
		 *
		 * Apply a payment record against the Kashflow Invoice
         * Updated to use the $kf_invoice_id returned by update_customer
         * ..or the kashflow_invoice_id stored in post meta
		 *
		 * @param int $order_id
		 */
		public function payment_complete( $order_id ) {
			//check for order method
			$order_method = get_option( 'ds_kashflow_order_method' );
			if ( $order_method == 'quote' ) {
				$this->logit( 'quote method used: no payment is taken.' );
			} else {
				$order = new WC_Order( $order_id );

				// only update invoice with customer details on payment complete
				// $invoice_on_complete = get_option( 'ds_kashflow_invoice_on_complete' );
				$invoice_on_complete = in_array( get_option('ds_kashflow_invoice_on_complete'), array(1,'yes',true)) ? true : false;
				$kf_invoice_id = false;
				if ( $invoice_on_complete ) {
					$kf_invoice_id = $this->update_customer( $order_id );
				}
				if(!$kf_invoice_id) {
				    $kf_invoice_id = get_post_meta($order_id, 'kashflow_invoice_id', true);
                }
                /**
                 * You need the Kashflow Order (Invoice) ID to apply the payment to
                 * You can't use the WC order ID as Kashflow won't always mirror your WooCommerce Order references to KF Invoice ID's
                 */
                if($kf_invoice_id !== false && $kf_invoice_id > 0) {

	                $total_amount                = $order->get_total();
	                $invoice_payment             = new KF_Invoice_Payment();
	                $invoice_payment->PayInvoice = $kf_invoice_id;
	                $invoice_payment->PayAmount  = $total_amount;
	                if ( $pay_method = get_option( 'ds_kashflow_gw_' . $order->get_payment_method() ) ) {
		                $invoice_payment->PayMethod = $pay_method;
		                $this->logit( 'sales invoice payment method - ' . $pay_method );
	                }
	                if ( $bank_account = get_option( 'ds_kashflow_gw_' . $order->get_payment_method() . '_bank_account' ) ) {
		                $invoice_payment->PayAccount = $bank_account;
	                }

	                $invoice_response = $this->api->insert_invoice_payment( $invoice_payment );
	                $this->logit( 'sales invoice payment - ' . print_r( $invoice_response, true ) );
                }
                else {
	                $this->logit( 'sales invoice payment - UNABLE TO COMPLETE - No Kashflow Invoice ID to apply payment to' );
                }
			}
		}

		private function calc_shipping_tax_rate() {
			$tax   = new WC_Tax();
			$rates = $tax->get_rates();

			$shipping_tax_rate = 0;

			foreach ( $rates as $key => $rate ) {
				if ( $rate['shipping'] == 'yes' ) {
					$shipping_tax_rate = $shipping_tax_rate + $rate['rate'];
				}
			}
			$shipping_tax_rate = 1 + ( $shipping_tax_rate / 100 );

			return ( $shipping_tax_rate - 1 ) * 100;
		}

		private function calc_item_tax_rate( $product ) {
			$tax = new WC_Tax();

			$rates = $tax->get_rates( $product->get_tax_class() );

			$regular_tax_rates = $compound_tax_rates = 0;

			foreach ( $rates as $key => $rate ) {
				if ( $rate['compound'] == 'yes' ) {
					$compound_tax_rates = $compound_tax_rates + $rate['rate'];
				} else {
					$regular_tax_rates = $regular_tax_rates + $rate['rate'];
				}
			}
			$regular_tax_rate  = 1 + ( $regular_tax_rates / 100 );
			$compound_tax_rate = 1 + ( $compound_tax_rates / 100 );

			return ( ( $regular_tax_rate * $compound_tax_rate ) - 1 ) * 100;

		}

		public function load_admin_scripts( $hook ) {
			if ( 'post.php' != $hook ) {
				return;
			}
			wp_enqueue_script( 'ds_kashflow', $this->plugin_url() . '/assets/js/ds_kashflow.js', 'jquery' );
			wp_localize_script( 'ds_kashflow', 'dsKashflowVars', array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php', 'relative' ),
					'ajaxLoaderGif' => $this->plugin_url() . '/assets/images/ajax-loader.gif'
				)
			);

		}

		public function email_kashflow_invoice() {
			$result = array( 'success' => false );
			// check validity of ajax call
			if ( isset( $_POST['invNonce'] ) && wp_verify_nonce( $_POST['invNonce'], 'ds_kashflow_inv_nonce' ) ) {

			    // Get KF Invoice_id
				$kf_invoice_id = get_post_meta($_POST['invOrderNumber'], 'kashflow_invoice_id', true);

				if(!$kf_invoice_id || $kf_invoice_id <= 0) {
					$result['error'] = 'Kashflow Invoice reference not found - Unable to send';
                }
			    elseif ( isset( $_POST['invTo'] ) ) {
					$result['success'] = true;
					// loop through email addresses
					$emails = explode( ',', $_POST['invTo'] );
					foreach ( $emails as $email ) {
						// email invoice
						$res = $this->api->email_invoice( $kf_invoice_id, $_POST['invSenderEmail'], $_POST['invSenderName'], $_POST['invSubject'], $_POST['invBody'], trim( $email ) );
						if ( $res === false ) {
							$result['success'] = false;
							$result['error']   = $this->api->get_last_error();
						}
					}
				}
			} else {
				$result['error'] = 'Invalid response';
			}
			echo json_encode( $result );
			die();
		}

		public function order_actions_end( $post_id ) {
			$order           = new WC_Order( $post_id );
			$company_details = $this->api->get_company_details();
			$order_number    = $order->get_order_number();
			?>

            <div id="ds_kashflow_invoice" style="display:none;">
                <div id="ds_kashflow_error" class="error-message"></div>
                <p class="form-field form-field-wide">
                    <label for="ds_kashflow_inv_to"><?php _e( 'To:', 'ds-kashflow' ); ?></label><br/>
                    <input id="ds_kashflow_inv_to" type="text" value="<?php echo $order->get_billing_email() ?>"/></p>
                <p class="form-field">
                    <label for="ds_kashflow_inv_subject"><?php _e( 'Subject:', 'ds-kashflow' ); ?></label><br/>
                    <input id="ds_kashflow_inv_subject" type="text"
                           value="<?php echo __( 'Invoice for Order ', 'ds-kashflow' ) . $order_number . __( ' from ', 'ds-kashflow' ) . $company_details['CompanyName']; ?>"/>
                </p>
                <p class="form-field">
                    <label for="ds_kashflow_inv_body"><?php _e( 'Message:', 'ds-kashflow' ); ?></label><br/>
                    <textarea id="ds_kashflow_inv_body"
                              rows="8"><?php _e( "Please see attached invoice.\n\nRegards,\nAccounts Dept.", 'ds-kashflow' ); ?></textarea>
                </p>
                <input id="ds_kashflow_inv_nonce" type="hidden"
                       value="<?php echo wp_create_nonce( 'ds_kashflow_inv_nonce' ); ?>"/>
                <input id="ds_kashflow_inv_sender_email" type="hidden"
                       value="<?php echo $company_details['PrimaryEmail']; ?>"/>
                <input id="ds_kashflow_inv_sender_name" type="hidden"
                       value="<?php echo $company_details['CompanyName']; ?>"/>
                <input id="ds_kashflow_inv_order_number" type="hidden"
                       value="<?php echo trim( $order_number, '#' ); ?>"/>
                trim($order->get_order_number(),'#')
                <p><input id="ds_kashflow_inv_submit" type="button"
                          value="<?php _e( 'Send Email', 'ds-kashflow' ); ?>"/></p>
            </div>


            <div class="wide" style="padding: 8px 12px;">
                <a href="#TB_inline?width=600&height=450&inlineId=ds_kashflow_invoice" title="Email KashFlow Invoice"
                   class="thickbox">Email KashFlow Invoice</a>
            </div>
			<?php
		}

		public function order_actions( $actions ) {
			$actions['kashflow_invoice'] = "Email KashFlow Invoice";

			return $actions;
		}


		/**
		 * splits an array and inserts a key/value
		 *
		 * @param array $array
		 * @param string $key
		 * @param array $insert
		 */
		private function array_insert( $array, $key, $insert ) {
			$res = array();
			foreach ( $array as $k => $v ) {
				$res[ $k ] = $v;
				if ( $k == $key ) {
					$res = array_merge_recursive( $res, $insert );
				}
			}

			return $res;
		}

		/**
		 * add kasahflow settings page
		 *
		 * @param array $settings
		 */
		public function kashflow_settings_page( $settings ) {

			$settings[] = include( 'settings/class-ds-settings-kashflow.php' );

			return $settings;
		}


		/**
		 * add settings tab to WooCommerce Settings
		 *
		 * @param array $tabs
		 */
		public function add_settings_tab( $tabs ) {
			return $this->array_insert( $tabs, 'tax', array( DS_KASHFLOW_ACCOUNTS => "Accounts" ) );
		}

		/**
		 * account_settings
		 *
		 * array of options for the accounts tab
		 *
		 */
		public function account_settings() {
			global $woocommerce_settings, $woocommerce;

			//sales types
			$sales_types_list = array();
			$sales_types      = $this->api->get_sales_types();
			foreach ( $sales_types as $sales_type ) {
				$sales_types_list[ $sales_type['ID'] ] = $sales_type['Name'] . ' [' . $sales_type['Code'] . ']';
			}
			$gateway_list = array();
			$gateways     = $woocommerce->payment_gateways();
			foreach ( $gateways->payment_gateways as $gateway ) {
				if ( $gateway->settings['enabled'] == 'yes' ) {
					$gateway_list[ $gateway->id ] = $gateway->title;
				}
			}

			//sources
			$sources_of_business      = array();
			$sources_of_business_list = array();
			$sources_of_business      = $this->api->get_customer_sources();
			if ( is_array( $sources_of_business ) ) {
				foreach ( $sources_of_business as $source_of_business ) {
					$sources_of_business_list[ (int) $source_of_business['ID'] ] = (string) $source_of_business['Name'];
				}
			}

			$inv_pay_list    = array();
			$inv_pay_methods = $this->api->get_inv_pay_methods();
			if ( is_array( $inv_pay_methods ) ) {
				foreach ( $inv_pay_methods['PaymentMethod'] as $inv_pay_method ) {
					$inv_pay_list[ (string) $inv_pay_method->MethodID ] = (string) $inv_pay_method->MethodName;
				}
			}
			$bank_accounts     = $this->api->get_bank_accounts();
			$bank_account_list = array();
			if ( is_array( $bank_accounts ) ) {
				foreach ( $bank_accounts as $bank_account ) {
					$bank_account_list[ (int) $bank_account['ID'] ] = (string) $bank_account['Name'];
				}
			}


			$woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ] = array(
				array(
					'title' => __( 'Account Settings', 'ds-kashflow' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'ds_kashflow_settings'
				), //section start
				array(
					'title' => __( 'UserName', 'ds-kashflow' ),
					'type'  => 'text',
					'desc'  => '',
					'id'    => 'ds_kashflow_username',
					'css'   => 'min-width:300px;'
				),
				array(
					'title' => __( 'API Password', 'ds-kashflow' ),
					'type'  => 'password',
					'desc'  => '',
					'id'    => 'ds_kashflow_api_password',
					'css'   => 'min-width:300px;'
				),
				array( 'type' => 'sectionend', 'id' => 'ds_kashflow_settings' ), //section end
				array(
					'title' => __( 'Customer', 'ds-kashflow' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'ds_kashflow_customer'
				), //section start
				array(
					'title'   => __( 'Source of Business', 'ds-kashflow' ),
					'type'    => 'select',
					'options' => $sources_of_business_list,
					'desc'    => __( 'Select New customers business source.', 'ds-kashflow' ),
					'id'      => 'ds_kashflow_source_of_business'
				),
				array( 'type' => 'sectionend', 'id' => 'ds_kashflow_settings' ), //section end
				array(
					'title' => __( 'Invoicing', 'ds-kashflow' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'ds_kashflow_invoicing'
				), //section start
				array(
					'title'   => __( 'Order method', 'ds-kashflow' ),
					'id'      => 'ds_kashflow_order_method',
					'default' => 'invoice',
					'type'    => 'radio',
					// 'desc_tip'    =>  __( 'this is a new woocommerce tip.', 'kashflow' ),
					'options' => array(
						'invoice' => __( 'Invoice - KashFlow generates an Invoice and payment can be taken automatically.', 'ds-kashflow' ),
						'quote'   => __( 'Quote - KashFlow generates a Quote allowing you to process and generate an Invoice via KashFlow.', 'ds-kashflow' )
					),
				),
				array( 'type' => 'sectionend', 'id' => 'ds_kashflow_invoicing' ), //section end
				array(
					'title' => __( 'Nominal Codes', 'ds-kashflow' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'ds_kashflow_nominal'
				), //section start
				array(
					'title'   => __( 'Sale of Goods', 'ds-kashflow' ),
					'type'    => 'select',
					'options' => $sales_types_list,
					'desc'    => __( 'Select \'Sales Type\' for the products you are selling.', 'ds-kashflow' ),
					'id'      => 'ds_kashflow_sale_goods_type'
				),
				array(
					'title'   => __( 'Shipping Type', 'ds-kashflow' ),
					'type'    => 'select',
					'options' => $sales_types_list,
					'desc'    => __( 'Select \'Sales Type\' for Shipping.', 'ds-kashflow' ),
					'id'      => 'ds_kashflow_shipping_type'
				),
				array( 'type' => 'sectionend', 'id' => 'ds_kashflow_nominal' ) //section end
			);

			$gws   = array();
			$gws[] = array(
				'title' => __( 'Active Payment Gateways', 'ds-kashflow' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'ds_wcgateways_settings'
			); //section start
			foreach ( $gateway_list as $k => $v ) {
				/*
				$gws[] = array(
				'title' => $v,
				'type' => 'select',
				'options' => $bank_account_list,
				'desc' => __( 'KashFlow Bank Account.', 'ds-kashflow' ),
				'id'         => 'ds_kashflow_gw_' . $k . '_bank_account'
				); */
				$gws[] = array(
					'title'   => $v,
					'type'    => 'select',
					'options' => $inv_pay_list,
					'desc'    => __( 'KashFlow Sales Type.', 'ds-kashflow' ),
					'id'      => 'ds_kashflow_gw_' . $k
				);
			}
			$gws[]                                        = array(
				'type' => 'sectionend',
				'id'   => 'ds_wcgateways_settings'
			); //section end
			$woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ] = array_merge_recursive( $woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ], $gws );

			$api                                          = array();
			$api[]                                        = array(
				'title' => __( 'Debugging', 'ds-kashflow' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'ds_kashflow_debugging'
			); //section start
			$api[]                                        = array(
				'title' => __( 'Test API', 'ds-kashflow' ),
				'type'  => 'dstestapi',
				'label' => __( 'Test', 'ds-kashflow' ),
				'desc'  => __( 'unknown', 'ds-kashflow' ),
				'id'    => 'ds_kashflow_test_api'
			);
			$kashflow_filename                            = sanitize_file_name( wp_hash( 'kashflow' ) . '.txt' );
			$api[]                                        = array(
				'title'   => __( 'Logging', 'ds-kashflow' ),
				'type'    => 'checkbox',
				'desc'    => sprintf( __( 'Log Kashflow API calls inside <code>plugins/woocommerce/logs/kashflow-%s.txt</code>', 'ds-kashflow' ), sanitize_file_name( wp_hash( 'kashflow' ) ) ),
				'default' => 'no',
				'id'      => 'ds_kashflow_debug'
			);
			$api[]                                        = array(
				'type' => 'sectionend',
				'id'   => 'ds_kashflow_debugging'
			); //section end
			$woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ] = array_merge_recursive( $woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ], $api );

			// setup test api
			$nonce = wp_create_nonce( 'test_api' );
			?>
            <script type='text/javascript'>
                <!--
                jQuery(window).load(function () {
                    jQuery("#ds_kashflow_test_api").click(function () {
                        jQuery.ajax({
                            type: "post",
                            url: "admin-ajax.php",
                            data: {action: 'test_api', _ajax_nonce: '<?php echo $nonce; ?>'},
                            success: function (html) {
                                jQuery("#testApiResult").html(html);
                            }
                        });
                    });
                });
                -->
            </script>
			<?php
		}

		/**
		 * admin_field_label
		 *
		 * custom admin field  - label
		 *
		 * @param array $value
		 */
		public function admin_field_label( $value ) {
			?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    <label
                            name="<?php echo esc_attr( $value['id'] ); ?>"
                            id="<?php echo esc_attr( $value['id'] ); ?>"
                            style="<?php echo esc_attr( $value['css'] ); ?>"
                            class="<?php echo esc_attr( $value['class'] ); ?>"
                    ><?php echo esc_attr( $value['label'] ); ?></label><?php echo $value['desc']; ?>
                </td>
            </tr>
			<?php
    	}

		/**
		 * admin_field_testapi
		 *
		 * custom admin field  - testapi (button & description)
		 *
		 * @param array $value
		 */
		public function admin_field_testapi( $value ) {
			global $woocommerce;
			$payment_methods = $this->api->get_inv_pay_methods();
			$gateways        = $woocommerce->payment_gateways();

			$description = '<span id="testApiResult" class="description">' . $value['desc'] . '</span>';
			?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    <input
                            name="<?php echo esc_attr( $value['id'] ); ?>"
                            id="<?php echo esc_attr( $value['id'] ); ?>"
                            type="button"
                            style="<?php echo esc_attr( $value['css'] ); ?>"
                            value="<?php echo esc_attr( $value['label'] ); ?>"
                            class="<?php echo esc_attr( $value['class'] ); ?>"
                    /> <?php echo $description; ?>
                </td>
            </tr>
			<?php
		}

		/**
		 * ajax callback for Test API
		 *
		 */
		public function test_api_callback() {
			check_ajax_referer( 'test_api' );
			$res = $this->api->get_company_details();

			if ( ! $res ) {
				echo __( 'Failed to connect to API: ', 'ds-kashflow' ) . $this->api->get_last_error();
			} else {
				echo __( 'Connected to API successfully', 'ds-kashflow' );
			}
			die();
		}

		/**
		 * admin_settings
		 *
		 * displays the admin fields for KashFlow accounts
		 *
		 */
		public function admin_settings() {
			global $woocommerce_settings;
			woocommerce_admin_fields( $woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ] );
		}

		/**
		 * save_admin_settings
		 *
		 * saves admin settings values in db
		 *
		 */
		public function save_admin_settings() {
			global $woocommerce_settings;
			woocommerce_update_options( $woocommerce_settings[ DS_KASHFLOW_ACCOUNTS ] );
		}

	} // end class

	function DSKF() {
		return Ds_Kashflow::getInstance();
	}
} // end exist

function ds_kashflow_init() {
	//global $ds_kashflow;
	//$ds_kashflow = DSKF();
	DSKF();
}

// check woocommerce is an active plugin before initializing
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	add_action( 'plugins_loaded', 'ds_kashflow_init', 0 );

	// localization
	load_plugin_textdomain( 'ds-kashflow', false, DS_KASHFLOW_PLUGINPATH . '/languages' );
}

?>
