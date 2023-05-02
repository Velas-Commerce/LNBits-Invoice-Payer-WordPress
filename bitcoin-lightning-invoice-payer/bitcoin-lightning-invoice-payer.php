<?php
require_once 'api_key.php'; // Include the api_key.php file
/*
 * Plugin Name:       LNBits Bitcoin Lightning Network Invoice Payer
 * Plugin URI:        https://github.com/Velas-Commerce/LNBits-Invoice-Payer-WordPress/
 * Description:       Add Bitcoin rewards to your wordpress site by programmatically paying bolt 11 Lightning invoices using a low-risk LNBits wallet and API key.
 * Version:           1.2.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            VelasCommerce
 * Author URI:        https://www.velascommerce.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       LNBits-bitcoin-lightning-invoice-payer
 * Domain Path:       /languages
 */

function ln_bitcoin_widget_enqueue_script() {   
    wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'js/custom.js' );
    wp_enqueue_style( 'custom_css', plugin_dir_url( __FILE__ ) . 'css/style.css' );

}
add_action('wp_footer', 'ln_bitcoin_widget_enqueue_script');


/**
 * Proper way to enqueue scripts and styles
 */
function wpdocs_theme_name_scripts() {
   wp_enqueue_style( 'custom-css', plugin_dir_url( __FILE__ ) . 'css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );


add_action( 'wp_enqueue_scripts', 'my_enqueue' );
function my_enqueue() {
 wp_enqueue_script('bitcoin_data', get_template_directory_uri().'/js/post-like.js', '1.0', 1 );
 wp_localize_script('bitcoin_data', 'ajax_var', array(
 'ajax_url' => admin_url('admin-ajax.php'),
 'nonce' => wp_create_nonce('invoice_payment_nonce'),
 'loader_img' => plugin_dir_url( __FILE__ ) . 'img/loader.gif' 
 ));
}

function invoice_payment() {
   if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'invoice_payment_nonce')) { // Update the nonce name here
      echo json_encode(array("status" => 'error', "error" => "Invalid nonce."));
      die();
    }

   $string = isset($_POST['string']) ? sanitize_text_field($_POST['string']) : '';

   if ($string === '') {
       echo json_encode(array("status"=>'error', "error"=>"Invoice string is empty."));
       die();
   }
   $data_array =  array( "bolt11" => $string );
   $decode_data_array =  array( "data" => $string );
    $decode_data = callAPI('POST', 'https://legend.lnbits.com/api/v1/payments/decode', json_encode($decode_data_array));
    $response = json_decode($decode_data, true);

    global $wpdb;
    $table_name = $wpdb->prefix.'payment_records';
    $get_ip = getIPAddress();  
	
	$query = "SELECT * FROM  ".$table_name." WHERE ip_addr = '".$get_ip."'";
	$results = $wpdb->get_results($query);

     if(!empty($response)){
          if(isset($response['amount_msat']) && $response['amount_msat']!="" ){
            $total_asking_amount = $response['amount_msat']/1000;
            if($total_asking_amount>100){
             echo json_encode(array("status"=>'error',"error"=>"Please do not raise invoice for more than 100 Satoshis.")); 
             die();exit;
            }
         }else{
            echo json_encode(array("status"=>'error',"error"=>"Something is wrong with your string."));
            die();exit;
         }
     }

      if(empty($results)){
             $get_data = callAPI('POST', 'https://legend.lnbits.com/api/v1/payments', json_encode($data_array));
             $response = json_decode($get_data, true);       
             if(!empty($response)){
               if(isset($response['payment_hash']) && $response['payment_hash']!="" ){
                  $wpdb->insert($table_name, array(
                      'ip_addr' => $get_ip
                  ));
                   setcookie("btc_payment_completed", 1, time()+86400);  /* expire in 24 hours */
                   echo json_encode(array("status"=>'true',"payment_hash"=>$response['payment_hash'])); 

               }elseif(isset($response['detail']) && $response['detail']!=""){
                  echo json_encode(array("status"=>'false',"error"=>"Either API is down or string has been already paid."));  
              
             }else{
                  echo json_encode(array("status"=>'false',"error"=>"Please contact tech team.")); 

             }
             die();

         }
      }else{

            if(isset($_COOKIE['btc_payment_completed'])) {
               echo json_encode(array("status"=>'error',"error"=>"Please wait for 24 hours from the time you have received the last payment.")); 
                   die();exit;
            }else {

                $get_data = callAPI('POST', 'https://legend.lnbits.com/api/v1/payments', json_encode($data_array));
                   $response = json_decode($get_data, true);       
                   if(!empty($response)){
                     if(isset($response['payment_hash']) && $response['payment_hash']!="" ){
                        $wpdb->insert($table_name, array(
                            'ip_addr' => $get_ip
                        ));
                         setcookie("btc_payment_completed", 1, time()+86400);  /* expire in 24 hours */
                         echo json_encode(array("status"=>'true',"payment_hash"=>$response['payment_hash'])); 

                     }elseif(isset($response['detail']) && $response['detail']!=""){
                        echo json_encode(array("status"=>'false',"error"=>"Either API is down or string has been already paid."));  
                    
                   }else{
                        echo json_encode(array("status"=>'false',"error"=>"Please contact tech team.")); 

                   }
                   die();

               }

            }
 
                   die();exit;
      }

}

add_action( 'wp_ajax_nopriv_invoice_payment', 'invoice_payment' );
add_action( 'wp_ajax_invoice_payment', 'invoice_payment' );

function callAPI($method, $url, $data){
   $curl = curl_init();
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                              
         break;
      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'X-Api-Key: ' . LNbits_API_KEY, // Use the constant instead of the hardcoded API key
      'Content-Type: application/json',
      'Content-length:' . strlen($data)
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}
/*create tables on plugin initialization for having records */

global $btc_db_version;
$btc_db_version = '1.0';

function btc_table_installation() {
   global $wpdb;
   global $btc_db_version;

   $table_name = $wpdb->prefix . 'payment_records';
   
   $charset_collate = $wpdb->get_charset_collate();

   $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      ip_addr varchar(100) DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
   ) $charset_collate;";

   require_once ABSPATH . 'wp-admin/includes/upgrade.php';
   dbDelta( $sql );

   add_option( 'btc_db_version', $btc_db_version );
}
register_activation_hook( __FILE__, 'btc_table_installation' );
/*Fetch real IP adress*/
 function getIPAddress() {  
    //whether ip is from the share internet  
     if(!empty($_SERVER['HTTP_CLIENT_IP'])) {  
                $ip = $_SERVER['HTTP_CLIENT_IP'];  
        }  
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
     }  
//whether ip is from the remote address  
    else{  
             $ip = $_SERVER['REMOTE_ADDR'];  
     }  
     return $ip;  
}