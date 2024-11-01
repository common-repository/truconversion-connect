<?php
/*
Plugin Name: TruConversion Connect
Plugin URI: https://wordpress.org/plugins/truconversion-connect/
Description: Enables <a href="http://truconversion.com/">TruConversion tracking code</a> on all pages.
Version: 1.2.8
Author: TruConversion
Author URI: https://www.truconversion.com/
*/

if ( ! defined('APP_URL') )
      define( 'APP_URL', 'http://app.truconversion.com/' );
if ( ! defined('APP_JS_URL') )
      define( 'APP_JS_URL', '//app.truconversion.com/' );
if ( ! defined('EDD_VERSION') )
      define( 'EDD_VERSION', '1.2.8' );
if ( ! defined('EDD_VERSION_DM') )
      define( 'EDD_VERSION_DM', '128' );

register_activation_hook( __FILE__, 'activate_truconversion_connect' );
register_deactivation_hook( __FILE__, 'deactive_truconversion_connect' );



$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'truconversion_settings_link' );
        
require_once plugin_dir_path(__FILE__) . "truconversion-settings.php";

function truconversion_settings_link( $links ) { 
  $settings_link = '<a href="admin.php?page=truconversion-connect">Settings</a>';
  array_unshift( $links, $settings_link );
  return $links;
}

function install_code () {
    $options = get_option( 'truconversion_setting' );
    if( $options && isset( $options['domain_id'] ) ){
        if( $options['status'] == 'install'){
            $domain_id = $options['domain_id'];
    ?>
            <script type="text/javascript">
                var _tip = _tip || [];
                (function(d,s,id){
                    var js, tjs = d.getElementsByTagName(s)[0];
                    if(d.getElementById(id)) { return; }
                    js = d.createElement(s); js.id = id;
                    js.async = true;
                    js.src = d.location.protocol + '<?php echo APP_JS_URL . 'ti-js/' . $domain_id . '/wpv' . EDD_VERSION_DM . '.js'?>';
                    tjs.parentNode.insertBefore(js, tjs);
                }(document, 'script', 'ti-js'));
            </script>
        <?php
            update_install_status( 'install', $domain_id );
        }
    }
}

function activate_truconversion_connect() {
    add_action( 'wp_head', 'install_code' );
}

function deactive_truconversion_connect() {
    $options = get_option( 'truconversion_setting' );
    if ( $options ) {
        update_install_status( 'uninstall', $options['domain_id'] );
    }
}

function update_install_status( $status, $domain_id ) {
        $args = array(
                    'body' => array(
                        'status' => $status, 
                        'domain' => home_url(), 
                        'domain_id' => $domain_id
                        ),
                    'timeout' => '10',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'cookies' => array()
            );
        
        $response = wp_remote_retrieve_body( wp_remote_post( APP_URL . 'api/v1/rest/updateDomainStatus', $args ) );
        update_option( 'truconversion_setting', array( 'domain_id' => $domain_id,'status' => $status ), 'no' );
}

if ( ! is_admin() ) {
    add_action( 'wp_head', 'install_code' );
}