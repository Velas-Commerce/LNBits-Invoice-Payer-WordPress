 <?php
/*
 * Plugin Name:       LNBits Bitcoin Lightning Network Invoice Payer
 * Plugin URI:        https://www.velascommerce.com/
 * Description:       Add Bitcoin rewards to your wordpress site by programmatically paying bolt 11 Lightning invoices using a low-risk LNBits wallet and API key.
 * Version:           1.10.3
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
 'nonce' => wp_create_nonce('ajaxnonce'),
 'loader_img' => plugin_dir_url( __FILE__ ) . 'img/loader.gif' 
 ));
}

function invoice_payment() {
    $string = $_POST['string'];
    $data_array =  array( "bolt11" => $string );
    $decode_data_array =  array( "data" => $string );
    $decode_data = callAPI('POST', 'https://legend.lnbits.com/api/v1/payments/decode', json_encode($decode_data_array));
    $response = json_decode($decode_data, true);
     if(!empty($response)){
          if(isset($response['amount_msat']) && $response['amount_msat']!="" ){
          //  print_r($response);die;
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

    $get_data = callAPI('POST', 'https://legend.lnbits.com/api/v1/payments', json_encode($data_array));
    $response = json_decode($get_data, true);
    
    if(!empty($response)){
     // print_r($response);die;
      if(isset($response['payment_hash']) && $response['payment_hash']!="" ){
       //  print_r($response);die;
          echo json_encode(array("status"=>'true',"payment_hash"=>$response['payment_hash'])); 
      }elseif(isset($response['detail']) && $response['detail']!=""){
        // print_r($response);die('sudddddddddddddddddddddddddddd');
         echo json_encode(array("status"=>'false',"error"=>"Either API is down or string has been already paid."));  
     
    }else{
         echo json_encode(array("status"=>'false',"error"=>"Please contact tech team.")); 

    }
    die();
   // print_r($response);die;

}
}

add_action( 'wp_ajax_nopriv_invoice_payment', 'invoice_payment' );
add_action( 'wp_ajax_invoice_payment', 'invoice_payment' );

function callAPI($method, $url, $data){
   // print_r($data);die;
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
      'X-Api-Key: '.LNbits_API_KEY,
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
