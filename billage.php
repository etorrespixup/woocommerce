<?php
/*
Plugin Name: Billage
Plugin URI:  https://www.getbillage.com/es/integraciones/woocommerce
Description: Sincroniza tus facturas en Billage automáticamente.
Version:     1.4
Author:      Billage
Author URI:  https://www.getbillage.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker=Puc_v4_Factory::buildUpdateChecker(
  'https://github.com/etorrespixup/woocommerce/',
  __FILE__,
  'billage'
);
$myUpdateChecker->setBranch('main');
$myUpdateChecker->setAuthentication('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');
ini_set('precision', 15);

$plugins_url = plugins_url();

class Billage
{

  private $options;

  public function __construct()
  {
    add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );
  }

    //Add options page


  public function add_plugin_page()
  {
      // This page will be under "Settings"
    add_options_page(
      'Billage', 
      'Billage', 
      'manage_options', 
      'billage', 
      array( $this, 'create_admin_page' )
    );
  }

  public function create_admin_page()
  {

    $this->options = get_option( 'billage' );
    ?>
    <div class="wrap">
      <h1>Billage</h1>
      <form method="post" action="options.php">
        <?php

        settings_fields( 'billage-group' );
        do_settings_sections( 'billage' );
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  public function page_init()
  {        
    register_setting(
            'billage-group', // Option group
            'billage', // Option name
            array( $this, 'sanitize' ) // Sanitize
          );

    add_settings_section(
            'setting_section_id', // ID
            'Sincroniza tus facturas con Billage.', // Title
            array( $this, 'print_section_info' ), // Callback
            'billage' // Page
          );  

    add_settings_field(
            'api_key', // ID
            'API KEY', // Title 
            array( $this, 'api_key_callback' ), // Callback
            'billage', // Page
            'setting_section_id' // Section           
          );       

    add_settings_field(
            'serie_select', // ID
            'Serie', // Title 
            array( $this, 'serie_select_callback' ), // Callback
            'billage', // Page
            'setting_section_id' // Section           
          );        

    add_settings_field(
            'nif_not_required', // ID
            'NIF Opcional', // Title 
            array( $this, 'nif_not_required_callback' ), // Callback
            'billage', // Page
            'setting_section_id' // Section           
          ); 
    add_settings_field(
          'logs_error', // ID
          'Visualizar archivo de logs', // Title 
          array( $this, 'logs_error_callback' ), // Callback
          'billage', // Page
          'setting_section_id' // Section           
    );   
  }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */

    public function sanitize( $input )
    {
      $new_input = array();
      if( isset( $input['api_key'] ) )
        $new_input['api_key'] = sanitize_text_field( $input['api_key'] );

      if ( isset( $input['serie_select'] ) ) {
        $new_input['serie_select'] = sanitize_text_field( $input['serie_select'] );
      }

      if ( isset( $input['nif_not_required'] ) ) {
        $new_input['nif_not_required'] = sanitize_text_field( $input['nif_not_required'] );
      }
      if ( isset( $input['logs_error'] ) ) {
        $new_input['logs_error'] = sanitize_text_field( $input['logs_error'] );
      }

      return $new_input;
    }

    //Print the Section text

    public function print_section_info()
    {
      print 'Introduce tus ajustes a continuación:';
    }

    //Get the settings option array and print one of its values

    public function api_key_callback()
    {
      printf(
        '<input class="api-key" type="password" id="api_key" name="billage[api_key]" value="%s" />',
        isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
      );
    }

    public function serie_select_callback()
    {

      if (isset( $this->options['api_key'] ) && $this->options['api_key'] != '') {
        $getSerieHTTP = "https://app.getbillage.com/api/v1/invoices/serie?api_key=" . $this->options['api_key'] . '&q=100';
        $getSerie = wp_remote_get($getSerieHTTP, array('sslverify' => FALSE));
        $allSeries = json_decode($getSerie["body"]);

        if (isset($this->options['serie_select'])) {
          $currentSerie = $this->options['serie_select'];
        } else {
          $currentSerie = "";
        }

        printf('<select name="billage[serie_select]">');

        foreach ($allSeries->page as $loopSerie => $aliasSerie) {
          printf('<option value="'. $aliasSerie->alias .'"'.(($aliasSerie->alias==$currentSerie)?'selected="selected"':"").'>'. $aliasSerie->alias .'</option>');
        }

        printf('</select>');

      } else {

        printf('Introduce tu API Key para poder escoger la serie dónde se guarden las facturas y actualiza la página.');

      }

    }

    public function nif_not_required_callback() {

      if (isset( $this->options['nif_not_required'] ) && $this->options['nif_not_required'] != '') {
        $html = '<input type="checkbox" id="billage[nif_not_required]" name="billage[nif_not_required]" value="1" ' . checked(1, $this->options["nif_not_required"], false) . ' />';
        $html .= '<label for="billage[nif_not_required]">Establece el NIF como campo opcional marcando esta casilla</label>';

        echo $html;
      } else {
        $html = '<input type="checkbox" id="billage[nif_not_required]" name="billage[nif_not_required]" value="1" />';
        $html .= '<label for="billage[nif_not_required]">Establece el NIF como campo opcional marcando esta casilla</label>';

        echo $html;
      }
    }
    public function logs_error_callback()
    {
      echo "<a class='button button-primary' target='_blank' href='../wp-content/plugins/billage/error_log.txt'>Visualizar logs</a>";
    }
  }

  if( is_admin() )
    $my_settings_page = new Billage();

  function writeInvoice($order_id) {
    if(!isset($_COOKIE['order']) || $_COOKIE['order']!=$order_id){
        setcookie('order',$order_id,time()+(1296000));
        $errorLogPath = plugin_dir_path( __FILE__ ) . "error_log.txt";

        $billage = get_option('billage');
        $api_key = $billage['api_key'];


        if (isset($billage['serie_select'])) {
          $selectedSerie = $billage['serie_select'];
        } else {
          $selectedSerie = "WooCommerce";
        }

        $order = wc_get_order($order_id);
        //echo "first order id: ".$order;
        $order_data = $order->get_data();

        $invoiceDate      = $order_data['date_created']->date('Y-m-d');
        $code             = $order_data['id'];
        $internal_notes   = 'Pedido generado a través de WooCommerce. Número de pedido #' . $code;

        switch ($order_data['status']) {
          case 'pending':
          $state = 'pending';
          break;

          case 'processing':
          $state = 'charged';
          break;

          case 'on-hold':
          $state = 'pending';
          break;

          case 'completed':
          $state = 'charged';
          break;

          case 'cancelled':
          $state = 'pending';
          break;

          case 'refunded':
          $state = 'pending';
          break;

          case 'failed':
          $state = 'pending';
          break;
        }

        $currency         = $order_data['currency'];
        $email            = $order_data['billing']['email'];
        $phone            = $order_data['billing']['phone'];

        $dni              = get_post_meta( $order->id, 'NIF', true);

        if (empty($order_data['billing']['last_name'])) {
          $customer       = $order_data['billing']['first_name'];
        } else {
          $customer       = $order_data['billing']['first_name'] . " " . $order_data['billing']['last_name'];
        }

        if ($order_data['billing']['company'] == '') {
          $company = $customer;
        } else {
          $company = $order_data['billing']['company'];
        }

        $payment_method   = $order_data['payment_method'];
        $payment_document = $order_data['payment_method_title'];
        $billing_address  = $order_data['billing']['address_1'] . " " . $order_data['billing']['address_2'];
        $city 			  = $order_data['billing']['city'];
        $zip_code 		  = $order_data['billing']['postcode'];
        $country 		  = $order_data['billing']['country'];
        //Iterating through each WC_Order_Item_Product objects

        $position = 1;

        foreach ($order->get_items() as $item_key => $item_values):

          //Fill concepts with item data
          $item_data = $item_values->get_data();
          $itemTax = array_values($item_data['taxes']['total']);
          $iva = (float)$itemTax[0] * 100 / (float)$item_data['total'];

          switch (round($iva)) {
            case 0:
            $ivaString = 'ES_IVA_EXENTO_0.00_0.00';
            break;

            case 3:
            $ivaString = 'ES_IGIC_3.00_0.00';
            break;

            case 4:
            $ivaString = 'ES_IVA_4.00_0.50';
            break;

            case 6.5:
            $ivaString = 'ES_IGIC_6.50_0.00';
            break;

            case 7:
            $ivaString = 'ES_IVA_7.00_0.00';
            break;

            case 10:
            $ivaString = 'ES_IVA_10.00_1.40';
            break;

            case 18:
            $ivaString = 'ES_IVA_18.00_4.00';
            break;

            case 21:
            $ivaString = 'ES_IVA_21.00_0.00';
          break;

            default:
            $ivaString = 'ES_IVA_21.00_0.00';
            break;
          }

          $concepts[] = array(
            'id'          => null,
            'type'        => 'product',
            'description' => $item_data['name'],
            'discount'    => 0.0,
            'notes'       => '',
            'position'    => $position++,
            'price'       => ($item_data['total'] / $item_data['quantity']),
            'quantity'    => $item_data['quantity'],
            'tax'         => $ivaString,
            'total'       => ($item_data['total'] / $item_data['quantity']),
            'retention'   => null,
            'unit'        => null,
            'rounding'    => false,                            
          );

        endforeach;

        if (!empty($order_data['shipping_lines'])) {
          $shipping_linesSetToZero = array_values($order_data['shipping_lines']);
          $shippingMethod = $shipping_linesSetToZero[0]->get_taxes();
          $shippingMethodTax = array_values($shippingMethod['total']);
          if ($order_data['shipping_total'] == 0) {
            $iva = 0;
          } else {
            $iva = (float)$shippingMethodTax[0] * 100 / (float)$order_data['shipping_total'];
          }

          switch (round($iva)) {
              case 0:
              $ivaString = 'ES_IVA_EXENTO_0.00_0.00';
              break;

              case 3:
              $ivaString = 'ES_IGIC_3.00_0.00';
              break;

              case 4:
              $ivaString = 'ES_IVA_4.00_0.50';
              break;

              case 6.5:
              $ivaString = 'ES_IGIC_6.50_0.00';
              break;

              case 7:
              $ivaString = 'ES_IVA_7.00_0.00';
              break;

              case 10:
              $ivaString = 'ES_IVA_10.00_1.40';
              break;

              case 18:
              $ivaString = 'ES_IVA_18.00_4.00';
              break;

              case 21:
              $ivaString = 'ES_IVA_21.00_0.00';
            break;

              default:
              $ivaString = 'ES_IVA_21.00_0.00';
              break;
          }

          $concepts[] = array(
            'id'          => null,
            'type'        => 'product',
            'description' => 'Tarifa de envio',
            'discount'    => 0.0,
            'notes'       => '',
            'position'    => $position,
            'price'       => $order_data['shipping_total'],
            'quantity'    => 1,
            'tax'         => $ivaString,
            'total'       => $order_data['shipping_total'],
            'retention'   => null,
            'unit'        => null,
            'rounding'    => false,                            
          );
        }

        $getSerieURL = "https://app.getbillage.com/api/v1/invoices/serie?api_key=" . $api_key . "&name=WooCommerce";

        $getSerie = wp_remote_get($getSerieURL, array('sslverify' => FALSE));
        $getSerieResult = json_decode($getSerie["body"]);

        if ($getSerieResult->status == 404) {

        $postSerie = array(
          'alias'         => "Woocommerce",
          'by_default'    => false,
          'prefix'      => 'WC',
          'sales_document_type' => array(
            'id' => 2,
          ),
        );

        $postSerieJSON = json_encode($postSerie);
        $postSerieURL = 'https://app.getbillage.com/api/v1/invoices/serie';
        $postSerie = wp_remote_post( $postSerieURL, array(
          'method'      => 'POST',
          'sslverify'   => false,
          'timeout'     => 45,
          'headers'     => array(                                                                          
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
          ),
          'body'        => $postSerieJSON,
        )
      );
      // $postSerieResults = json_decode($postSerie['body']);
      }

      //Check if customer already exists

      $noSpaceCustomer = str_replace(' ', '%20', $customer);
      //var_dump($noSpaceCustomer);
    

      $getCustomerURL = "https://app.getbillage.com/api/v1/accounts?api_key=" . $api_key . "&q=" . $noSpaceCustomer . "&start=1&elements=20";

      $getCustomer = wp_remote_get($getCustomerURL, array('sslverify' => FALSE));
      $getCustomerResult = json_decode($getCustomer["body"]);

      $getCustomerLength = $getCustomerResult->page_elements;

      $emailExists = false;

      for ($i=0; $i < $getCustomerLength; $i++) { 
        if ($email == $getCustomerResult->page[$i]->email) {
            $emailExists = true; 
        }
      }

      // Init filling Invoice values

      $postInvoice = array(
        'api_key'               => $api_key,
        'id'                    => null,
        'date'                  => $invoiceDate,
        'expirationDate'        => $invoiceDate,
        'type'                  => 'normal',
        'state'                 => $state,
        'internal_notes'        => $internal_notes,
        'currency'              => $currency,
        'serie'                 => $selectedSerie,
        'category'              => null,
        'customer'              => $customer,
        'language'              => 'Castellano',
        'surcharge_equivalence'  => false,
        'concepts'              => $concepts,
        'payment_method'        => 'CASH',
        'payment_document'      => 'CASH',
        'delivery_address'      => null,
        'company_direction'     => null,
      );
      //echo "comanda: ".$_GET["order-received"];
      //End filling Invoice values
      if ($getCustomerLength == 0) {

        $postCustomer = array(
          'api_key'         => $api_key,
          'addresses'       => array([
                      'account'   =>   array(
                        'alias' => $customer, 
                      ),
        'address'     => $billing_address,
        'city'        => $city,
        'country'     => $country,
        'email'       => $email,
        'zip_code'    => $zip_code,
        ]),
          'alias'           => $customer,
          'business_name'   => $company,
          'email'           => $email,
          'phones'          => array(
            $phone,
          ),
          'vat'             => $dni,
        );
        //Petición para comprobar si el cliente existe, y si no existe crearlo
        $postCustomerJSON = json_encode($postCustomer);
        $postCustomerCurl = 'https://app.getbillage.com/api/v1/accounts';
        $postCustomer = wp_remote_post( $postCustomerCurl, array(
          'method'      => 'POST',
          'sslverify'   => false,
          'timeout'     => 45,
          'headers'     => array(                                                                          
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
          ),
          'body'        => $postCustomerJSON,
        )
      );

          $postCustomerResults = json_decode($postCustomer['body']);
          $postCustomerStatus = $postCustomerResults->status;
          $postCustomerMessage = $postCustomerResults->error_message;
          $postCustomerResponse = $postCustomerResults->response;

          if ($postCustomerStatus >= 400) {
            $postCustomerError = fopen($errorLogPath, 'a');
            $errorLogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Error " . $postCustomerStatus . ": " . $postCustomerResponse . " " . $postCustomerMessage . "}" . PHP_EOL;
            fwrite($postCustomerError, $errorLogMessage);
            fclose($postCustomerError);
          }else{
            /*Code PixUP */
            if(isset($postCustomerResults->business_name)){
              $postCustomerLog = fopen($errorLogPath, 'a');
              $LogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Code 200: Se ha creado el cliente ".$postCustomerResults->business_name . PHP_EOL;
              fwrite($postCustomerLog, $LogMessage);
              fclose($postCustomerLog);
            }
          }

        $postInvoiceJSON = json_encode($postInvoice);
        //Petición para insertar factura en la app de billage
        $postInvoiceURL = 'https://app.getbillage.com/api/v1/invoices';
        $postInvoice = wp_remote_post( $postInvoiceURL, array(
          'method'      => 'POST',
          'sslverify'   => false,
          'timeout'     => 45,
          'headers'     => array(                                                                          
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json;charset=UTF-8',
          ),
          'body'        => $postInvoiceJSON,
        )
      );


          $postInvoiceResults = json_decode($postInvoice['body']);

          $postInvoiceStatus = $postInvoiceResults->status;
          $postInvoiceErrorMessage = $postInvoiceResults->error_message;
          $postInvoiceResponse = $postInvoiceResults->response;

        if ($postInvoiceStatus >= 400) {
          $postInvoiceError = fopen($errorLogPath, 'a');
          $errorLogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Error " . $postInvoiceStatus . ": " . $postInvoiceResponse . " " . $postInvoiceErrorMessage . "}" . PHP_EOL;
          fwrite($postInvoiceError, $errorLogMessage);
          fclose($postInvoiceError);
        }else{
          /*Code PixUP*/
          if(isset($postInvoiceResults->internal_notes)){
            $postInvoiceLogs = fopen($errorLogPath, 'a');
            $LogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Code 200: " . $postInvoiceResults->internal_notes . PHP_EOL;
            fwrite($postInvoiceLogs, $LogMessage);
            fclose($postInvoiceLogs);

          }

        }

      } elseif ($getCustomerLength >= 1) {

        if($emailExists) {

          $postInvoiceJSON = json_encode($postInvoice);
          
          $postInvoiceURL = 'https://app.getbillage.com/api/v1/invoices';
          $postInvoice = wp_remote_post( $postInvoiceURL, array(
            'method'      => 'POST',
            'sslverify'   => false,
            'timeout'     => 45,
            'headers'     => array(                                                                          
              'Authorization' => $api_key,
              'Content-Type'  => 'application/json;charset=UTF-8',
            ),
            'body'        => $postInvoiceJSON,
          )
        );

          $postInvoiceResults = json_decode($postInvoice['body']);

          $postInvoiceStatus = $postInvoiceResults->status;
          $postInvoiceErrorMessage = $postInvoiceResults->error_message;
          $postInvoiceResponse = $postInvoiceResults->response;

          if ($postInvoiceStatus >= 400) {
            $postInvoiceError = fopen($errorLogPath, 'a');
            $errorLogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Error " . $postInvoiceStatus . ": " . $postInvoiceResponse . " " . $postInvoiceErrorMessage . "}" . PHP_EOL;
            fwrite($postInvoiceError, $errorLogMessage);
            fclose($postInvoiceError);
          }else{
            /*Code PixUP*/
            if(isset($postInvoiceResults->internal_notes)){
              $postInvoiceLogs = fopen($errorLogPath, 'a');
              $LogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Code 200: " . $postInvoiceResults->internal_notes . PHP_EOL;
              fwrite($postInvoiceLogs, $LogMessage);
              fclose($postInvoiceLogs);
      
            }
      
          }
        } else {

          $postCustomer = array(
            'api_key'         => $api_key,
            'addresses'       => array([
                        'account'   =>   array(
                          'alias' => $customer, 
                        ),
          'address'     => $billing_address,
          'city'        => $city,
          'country'     => $country,
          'email'       => $email,
          'zip_code'    => $zip_code,
          ]),
            'alias'           => $customer,
            'business_name'   => $company,
            'email'           => $email,
            'phones'          => array(
              $phone,
            ),
            'vat'             => $dni,
          );

          $postCustomerJSON = json_encode($postCustomer);
          $postCustomerCurl = 'https://app.getbillage.com/api/v1/accounts';
          $postCustomer = wp_remote_post( $postCustomerCurl, array(
            'method'      => 'POST',
            'sslverify'   => false,
            'timeout'     => 45,
            'headers'     => array(                                                                          
              'Authorization' => $api_key,
              'Content-Type'  => 'application/json',
            ),
            'body'        => $postCustomerJSON,
          )
        );

          $postCustomerResults = json_decode($postCustomer['body']);
          $postCustomerStatus = $postCustomerResults->status;
          $postCustomerMessage = $postCustomerResults->error_message;
          $postCustomerResponse = $postCustomerResults->response;

          if ($postCustomerStatus >= 400) {
            $postCustomerError = fopen($errorLogPath, 'a');
            $errorLogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Error " . $postCustomerStatus . ": " . $postCustomerResponse . " " . $postCustomerMessage . "}" . PHP_EOL;
            fwrite($postCustomerError, $errorLogMessage);
            fclose($postCustomerError);
          }else{
            /*Code PixUP */
            if(isset($postCustomerResults->business_name)){
              $postCustomerLog = fopen($errorLogPath, 'a');
              $LogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Code 200: Se ha creado el cliente ".$postCustomerResults->business_name . PHP_EOL;
              fwrite($postCustomerLog, $LogMessage);
              fclose($postCustomerLog);
            }
          }


          $postInvoiceJSON = json_encode($postInvoice);

          $postInvoiceURL = 'https://app.getbillage.com/api/v1/invoices';
          $postInvoice = wp_remote_post( $postInvoiceURL, array(
            'method'      => 'POST',
            'sslverify'   => false,
            'timeout'     => 45,
            'headers'     => array(                                                                          
              'Authorization' => $api_key,
              'Content-Type'  => 'application/json;charset=UTF-8',
            ),
            'body'        => $postInvoiceJSON,
          )
        );

            $postInvoiceResults = json_decode($postInvoice['body']);

            $postInvoiceStatus = $postInvoiceResults->status;
            $postInvoiceErrorMessage = $postInvoiceResults->error_message;
            $postInvoiceResponse = $postInvoiceResults->response;

          if ($postInvoiceStatus >= 400) {
            $postInvoiceError = fopen($errorLogPath, 'a');
            $errorLogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Error " . $postInvoiceStatus . ": " . $postInvoiceResponse . " " . $postInvoiceErrorMessage . "}" . PHP_EOL;
            fwrite($postInvoiceError, $errorLogMessage);
            fclose($postInvoiceError);
          }else{
            /*Code PixUP*/
            if(isset($postInvoiceResults->internal_notes)){
              $postInvoiceLogs = fopen($errorLogPath, 'a');
              $LogMessage = date("Y-m-d H:i:s") . " - Factura número " . $code . ". Code 200: " . $postInvoiceResults->internal_notes . PHP_EOL;
              fwrite($postInvoiceLogs, $LogMessage);
              fclose($postInvoiceLogs);
      
            }
      
          }
        }
      }
  }
}

add_action('woocommerce_thankyou', 'writeInvoice');


$billageOptions = get_option('billage');

function woo_custom_field_checkout($checkout) {

  $billageOptions = get_option('billage');
  if (isset($billageOptions['nif_not_required']) && $billageOptions['nif_not_required'] == '1') {
    echo '<div id="additional_checkout_field">';
    woocommerce_form_field( 'nif', array(
      'type'          => 'text',
      'class'         => array('my-field-class form-row-wide'),
      'required'      => false,       
      'label'       => __('NIF / CIF'), 
      'placeholder'   => __('Ej: 12345678X'), 
    ), $checkout->get_value( 'nif' ));    
    echo '</div>'; 
  } else {
    echo '<div id="additional_checkout_field">';
    woocommerce_form_field( 'nif', array(
      'type'          => 'text',
      'class'         => array('my-field-class form-row-wide'),
      'required'      => true,       
      'label'       => __('NIF / CIF'), 
      'placeholder'   => __('Ej: 12345678X'), 
    ), $checkout->get_value( 'nif' ));    
    echo '</div>'; 
  }
}
add_action( 'woocommerce_after_checkout_billing_form', 'woo_custom_field_checkout' );

function woo_custom_field_checkout_update_order($order_id) {
  if ( ! empty( $_POST['nif'] ) ) {
    update_post_meta( $order_id, 'NIF', sanitize_text_field( $_POST['nif'] ) );
  }
}

function woo_custom_field_checkout_edit_order($order){
  echo '<p><strong>'.__('NIF').':</strong> ' . get_post_meta( $order->id, 'NIF', true ) . '</p>';
}

function woo_custom_field_checkout_email($keys) {
  $keys[] = 'NIF';
  return $keys;
}

if (isset($billageOptions['nif_not_required']) && $billageOptions['nif_not_required'] == '1') {
  //NIF opcional, no se informa.
} else {
  add_action('woocommerce_admin_order_data_after_billing_address', 'woo_custom_field_checkout_edit_order', 10, 1 );
  add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');
  add_filter('woocommerce_email_order_meta_keys', 'woo_custom_field_checkout_email');
  add_action('woocommerce_checkout_update_order_meta', 'woo_custom_field_checkout_update_order' );
}

function my_custom_checkout_field_process() {
    if ( ! $_POST['nif'] )
        wc_add_notice( __( 'Por favor rellena tu NIF/CIF.' ), 'error' );
}