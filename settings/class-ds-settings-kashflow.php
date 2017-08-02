<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'KF_Settings_KashFlow' ) ) :

/**
* KF_Settings_KashFlow
*/
class KF_Settings_KashFlow extends WC_Settings_Page {

/**
* Constructor.
*/
public function __construct() {
    $this->id    = 'kashflow';
    $this->label = __( 'KashFlow', 'ds-kashflow' );

    add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
    add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
    add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
    add_action( 'woocommerce_admin_field_radio_checkbox', array( $this, 'add_radio_checkbox' ) );
    add_action( 'woocommerce_update_option_radio_checkbox', array( $this, 'update_radio_checkbox' ) );
}

/**
* Get a setting from the settings API.
*
* @param mixed $option
* @return string
*/
public static function get_option( $option_name, $default = '' ) {
    // Array value
    if ( strstr( $option_name, '[' ) ) {

        parse_str( $option_name, $option_array );

        // Option name is first key
        $option_name = current( array_keys( $option_array ) );

        // Get value
        $option_values = get_option( $option_name, '' );

        $key = key( $option_array[ $option_name ] );

        if ( isset( $option_values[ $key ] ) )
            $option_value = $option_values[ $key ];
        else
            $option_value = null;

        // Single value
    } else {
        $option_value = get_option( $option_name, null );
    }

    if ( is_array( $option_value ) )
        $option_value = array_map( 'stripslashes', $option_value );
    elseif ( ! is_null( $option_value ) )
        $option_value = stripslashes( $option_value );

    return $option_value === null ? $default : $option_value;
}        

public function add_radio_checkbox( $value ){
    $custom_attributes = array();

    $option_value     = self::get_option( $value['id'], $value['default'] );

    ?><tr valign="top">
        <th scope="row" class="titledesc">
            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
            <?php echo $tip; ?>
        </th>
        <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
            <fieldset>
                <?php echo $description; ?>
                <ul>
                    <?php
                    foreach ( $value['options'] as $key => $val ) {
                        ?>
                        <li>
                            <label><input
                                    name="<?php echo esc_attr( $value['id'] ); ?>"
                                    value="<?php echo $key; ?>"
                                    type="radio"
                                    style="<?php echo esc_attr( $value['css'] ); ?>"
                                    class="<?php echo esc_attr( $value['class'] ); ?>"
                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                    <?php checked( $key, $option_value ); ?>
                                /> <?php echo $val['title'] ?></label>
                            <?php
                            if( isset($val['checkboxes']) ){
                                ?>
                                <ul>
                                    <?php
                                    foreach( $val['checkboxes'] as $checkbox ){
                                        $checkbox_value = self::get_option( $checkbox['id'], $checkbox['default'] );
                                        ?>
                                        <li>
                                            <label style="padding-left: 20px;" for="<?php echo $checkbox['id'] ?>">
                                                <input
                                                    name="<?php echo esc_attr( $checkbox['id'] ); ?>"
                                                    id="<?php echo esc_attr( $checkbox['id'] ); ?>"
                                                    type="checkbox"
                                                    value="1"
                                                    <?php checked( $checkbox_value, 'yes'); ?>
                                                    <?php echo implode( ' ', $custom_attributes ); ?>
                                                    /> <?php echo $checkbox['desc'] ?>
                                        </label> <?php echo $checkbox['tip']; ?>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                                }
                                ?>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </fieldset>
            </td>
        </tr><?php            

    }

public function update_radio_checkbox( $value ){

    if ( isset( $_POST[$value['id']] ) ) {
        $option_value = wc_clean( stripslashes( $_POST[ $value['id'] ] ) );
        update_option( $value['id'], $option_value );
    } else {
        update_option( $checkbox['id'], '' );
    }
    foreach( $value['options'] as $options){
        if( isset( $options['checkboxes'] ) ){
            foreach( $options['checkboxes'] as $checkbox ){
                if ( isset( $_POST[$checkbox['id']] ) ) {
                    update_option( $checkbox['id'], 'yes' );
                } else {
                    update_option( $checkbox['id'], 'no' );
                }                
            }
        }
    }
}
    /**
    * Get settings array
    *
    * @return array
    */
    public function get_settings() {
        global $woocommerce;

        $api_access = array(
            array( 'title' => __( 'KashFlow Settings', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_kashflow_settings' ), //section start
            array(
                'title' => __( 'UserName', 'ds-kashflow' ),
                'type'         => 'text',
                'desc'         => __( 'This is normally the same as your login name', 'ds-kashflow' ),
                'id'         => 'ds_kashflow_username',                
                'css'         => 'min-width:300px;'
            ),
            array(
                'title' => __( 'API Password', 'ds-kashflow' ),
                'type'         => 'password',
                'desc'         => __( 'Tip: Use different password to normal login (change in KashFlow Settings-API-Password Settings) ', 'ds-kashflow' ),
                'id'         => 'ds_kashflow_api_password',                
                'css'         => 'min-width:300px;'
            ),
            array( 'type' => 'sectionend', 'id' => 'ds_kashflow_settings' ) //section end
        );

        $connected = DSKF()->api->get_company_details();
        if( $connected ){
            //sales types
            $sales_types_list = array();
            $sales_types = DSKF()->api->get_sales_types();
            foreach($sales_types as $sales_type)
            {
                $sales_types_list[$sales_type['ID']] = $sales_type['Name'] . ' [' . $sales_type['Code'] . ']';
            }
            $gateway_list = array();
            $gateways = $woocommerce->payment_gateways();
            foreach( $gateways->payment_gateways as $gateway )
            {
                if( $gateway->settings['enabled'] == 'yes' )
                {
                    $gateway_list[$gateway->id] = $gateway->title;
                }            
            }

            //sources
            $sources_of_business = array();
            $sources_of_business_list = array();
            $sources_of_business = DSKF()->api->get_customer_sources();
            if(is_array( $sources_of_business ) ) 
            {
                foreach( $sources_of_business as $source_of_business )
                {
                    $sources_of_business_list[(int)$source_of_business['ID']] = (string)$source_of_business['Name'];
                }            
            }

            $inv_pay_list = array();
            $inv_pay_methods = DSKF()->api->get_inv_pay_methods();
            if(is_array( $inv_pay_methods ) )
            {
                foreach( $inv_pay_methods['PaymentMethod'] as $inv_pay_method )
                {
                    $inv_pay_list[(string)$inv_pay_method->MethodID] = (string)$inv_pay_method->MethodName;
                }            
            }        
            $bank_accounts = DSKF()->api->get_bank_accounts();
            $bank_account_list = array();
            if( is_array( $bank_accounts ) )
            {
                foreach( $bank_accounts as $bank_account )
                {
                    $bank_account_list[(int)$bank_account['ID']] = (string)$bank_account['Name'];
                }
            }

            //fields

            $body = array(

                array( 'title' => __( 'Customer', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_kashflow_customer' ), //section start
                array(
                    'title' => __( 'Source of Business', 'ds-kashflow' ),
                    'type' => 'select',
                    'options' => $sources_of_business_list,
                    'desc' => __( 'Select New customers business source.', 'ds-kashflow' ),
                    'id'         => 'ds_kashflow_source_of_business'
                ),
                array( 'type' => 'sectionend', 'id' => 'ds_kashflow_settings' ), //section end
                array( 'title' => __( 'Invoicing', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_kashflow_invoicing' ), //section start
                array(
                    'title' => __( 'Order method', 'ds-kashflow' ),
                    'id'         => 'ds_kashflow_order_method',
                    'default'    => 'invoice',
                    //'type'         => 'radio',
                    'type'         => 'radio_checkbox',
                    // 'desc_tip'    =>  __( 'this is a new woocommerce tip.', 'kashflow' ),
                    'options'    => array( 
                            'invoice' => array( 'title' => __( 'Invoice - KashFlow generates an Invoice and payment can be taken automatically.', 'ds-kashflow' ),            
                                'checkboxes' => array( array(
                                    'type'         => 'checkbox',
                                    'desc' =>   __( 'Only send completed orders to KashFlow for invoicing.', 'ds-kashflow' ),
                                    'default'         => 'no',
                                    'id'         => 'ds_kashflow_invoice_on_complete'
                                ) )
                            ), 
                            'quote' => array( 'title' => __( 'Quote - KashFlow generates a Quote allowing you to process and generate an Invoice via KashFlow.', 'ds-kashflow' )
                            )
                    ),
                ),
                array( 'type' => 'sectionend', 'id' => 'ds_kashflow_invoicing' ), //section end
                array( 'title' => __( 'Nominal Codes', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_kashflow_nominal' ), //section start
                array(
                    'title' => __('Sale of Goods', 'ds-kashflow'),
                    'type' => 'select',
                    'options' => $sales_types_list,
                    'desc' => __( 'Select KashFlow\'s Sales Type for the products you are selling.', 'ds-kashflow' ),
                    'id'         => 'ds_kashflow_sale_goods_type'
                ),
                array(
                    'title' => __('Shipping Type', 'ds-kashflow'),
                    'type' => 'select',
                    'options' => $sales_types_list,
                    'desc' => __( 'Select KashFlow\'s Sales Type for Shipping.', 'ds-kashflow' ),
                    'id'         => 'ds_kashflow_shipping_type'
                ),
                array( 'type' => 'sectionend', 'id' => 'ds_kashflow_nominal' ) //section end
            );
            $kashflow_filename = sanitize_file_name( wp_hash( 'kashflow' ) . '.txt');


            $gateway_list = array();
            $gateways = WC()->payment_gateways();
            foreach( $gateways->payment_gateways as $gateway )
            {
                if( $gateway->settings['enabled'] == 'yes' )
                {
                    $gateway_list[$gateway->id] = $gateway->title;
                }            
            }

            $gws = array();
            $gws[] = array( 'title' => __( 'Active Payment Gateways', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_wcgateways_settings' ); //section start
            foreach($gateway_list as $k => $v)
            {   
                $gws[] = array(
                    'title' => $v,
                    'type' => 'select',
                    'options' => $inv_pay_list,
                    'desc' => __( 'Select KashFlow\'s Sales Type.', 'ds-kashflow' ),
                    'id'         => 'ds_kashflow_gw_' . $k
                );                
            }  
            $gws[] = array( 'type' => 'sectionend', 'id' => 'ds_wcgateways_settings' ); //section end
            $body = array_merge_recursive($body, $gws);
        }
        $debug = array(
            array( 'title' => __( 'Debugging', 'ds-kashflow' ), 'type' => 'title', 'desc' => '', 'id' => 'ds_kashflow_debugging' ), //section start
            array(
                'title' => __( 'Test API', 'ds-kashflow' ),
                'type'         => 'dstestapi',
                'label' => __('Test', 'ds-kashflow' ),
                'desc' => __('unknown', 'ds-kashflow' ),
                'id'         => 'ds_kashflow_test_api'
            ),
            array(
                'title' => __( 'Logging', 'ds-kashflow' ),
                'type'         => 'checkbox',
                'desc' =>   sprintf( __( 'Log Kashflow API calls inside <code>plugins/woocommerce/logs/kashflow-%s.txt</code>', 'ds-kashflow' ), sanitize_file_name( wp_hash( 'kashflow' ) ) ),
                'default'         => 'no',
                'id'         => 'ds_kashflow_debug'
            ),
            array( 'type' => 'sectionend', 'id' => 'ds_kashflow_debugging' ) //section end            
        );

        if( $connected ){
            $content = array_merge( $api_access, $body, $debug );
        } else {
            $content = array_merge( $api_access, $debug );
        }


        return apply_filters( 'woocommerce_' . $this->id . '_settings', $content);
    }
}

endif;

return new KF_Settings_KashFlow();
