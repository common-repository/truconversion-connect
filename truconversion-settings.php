<?php

class TruConversion
{
    protected $option_name;
    protected $user;
    protected $domain_id;
    public function __construct() {
        $this->option_name = 'truconversion_setting';
        $this->options = get_option( $this->option_name );
        
        add_action( 'admin_menu', array($this, 'add_menu_page') );
        add_action( 'admin_init', array($this, 'init') );
    }

    public function add_menu_page() {
        add_menu_page(
            'TruConversion Connect',
            'TruConversion Connect',
            'manage_options',
            'truconversion-connect',
            array($this, 'admin_container'),
            'dashicons-tag'
        );
    }

    public function admin_container() {
        ?>
        <div class="wrap">
            <h2>TruConversion Connect</h2>
            <?php
            settings_errors();
            if( isset( $_GET['tab'] )){
                $active_tab = $_GET['tab'];
            }else{
                $active_tab = 'settings';
            }
            ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=truconversion-connect&tab=settings"  class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=truconversion-connect&tab=abount-us"  class="nav-tab <?php echo $active_tab == 'abount-us' ? 'nav-tab-active' : ''; ?>">About Us</a>
            </h2>
            <form method="post" action="options.php">
                <?php
                if( $active_tab == 'settings' ) {
                    settings_fields( 'tc-setting-options' );
                    do_settings_sections( 'tc-setting-options' );
                } else {
                    settings_fields( 'tc-about-options' );
                    do_settings_sections( 'tc-about-options' );
                }
                ?>
            </form>
        </div>
        <?php
    }

    public function init() {
        register_setting(
            'tc-setting-options',
            $this->option_name,
            array($this, 'sanitize')
        );
        
        register_setting(
            'tc-about-options',
            $this->option_name,
            array($this, 'sanitize')
        );
        
        if( !$this->options || $this->options['status'] != 'install' ) {
            $callback_section = 'tc_domain_callback';
            add_thickbox();
            add_action( 'wp_ajax_tc_signin', array($this, 'tc_signin_callback') );
            add_action( 'wp_ajax_tc_signup', array($this, 'tc_signup_callback') );
            add_action( 'wp_ajax_tc_install_code', array($this, 'tc_install_code_callback') );
        } else {
            $callback_section = 'tc_dashboard_callback';
        }
        
        add_settings_section(
            'truconversion_setting_id',
            '',
            array($this, $callback_section),
            'tc-setting-options'
        );
        
        add_settings_section(
            'truconversion_setting_id',
            '',
            array($this, 'tc_about_callback'),
            'tc-about-options'
        );
        
        add_action( 'admin_enqueue_scripts', array($this, 'add_scripts') );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if ( isset( $input['domain_id'] ))
            $new_input['domain_id'] = $input['domain_id'];
        if ( isset( $input['status'] ) )
            $new_input['status'] = $input['status'];

        return $new_input;
    }

    public function tc_domain_callback() {
        $this->user = wp_get_current_user();
        $result = $this->get_user_domain();
        $html = $signinHTML = $signupHTML = '';
        
        $signinHTML = '<div class="tc-panel">'
                .'<div class="notice notice-warning"><p>Please make sure TruConverion tracking code should not be installed by other means '
                . 'i.e manual insertion into header file, Google Tag Manager or using any other plugin.</p></div>'
                . '<div class="tc-panel-img"><img src="' . plugins_url( '/images/logo.png', __FILE__ ) . '"/></div>'
                . '<div class="tc-panel-content">'
                . '<div class="tc-panel-head">TruConversion Sign In</div>'
                . '<div class="tc-panel-paragraph">Please signin for existing account</div>'
                . '<div class="tc-panel-btn"><a href="#TB_inline?width=600&height=350&inlineId=tc-signin-form" class="button button-primary thickbox ">Sign In</a></div>'
                . '</div>'
                . '</div>'
                . '<div id="tc-signin-form" style="display:none;">
                    <div class="tc-logo-header"><img src="' . plugins_url( '/images/logo.png', __FILE__ ) . '" alt="TruConversion Logo"/></div>
                    <h2>Sign In To Your TruConversion Account</h2>
                    <div class="wrap">
                        <form method="post" name="signin" id="TCsignin">
                            <table class="TcSignForm">
                                <tbody>
                                    <tr><td><div class="error-msg"></div></td></tr>
                                    <tr>
                                        <td>Email</td>
                                        <td><input type="text" name="tc_email" id="tc_email" class="form-control input-lg" placeholder="name@company.com" value="" autofocus /></td>
                                    </tr>
                                    <tr>
                                        <td>Password</td>
                                        <td><input type="password" id="tc_password" class="form-control input-lg" name="tc_password" placeholder="enter your password" value="" /></td>
                                    </tr>
                                    <tr>
                                       <td colspan="2"><br />
                                        <input id="tcSignin" name="Senden" type="submit" value="Next" class="button button-primary"></td>
                                    </tr>
                                    <tr style="display:none;">
                                       <td colspan="2"><p class="below-cta">Dont have an account? <a href="javascript:;" class="openSignup">Sign Up</a></p></td>
                                    </tr>
                                </tbody>
                            </table>
                             <table class="TcWebsiteForm" style="display:none;">
                                <tbody>
                                    <tr>
                                    <td>Select Website</td>
                                      <td><select name="tc_websites" id="tc_websites" class="form-control input-lg"></select></td>
                                    </tr>
                                    <tr>
                                       <td colspan="2"><p class="above-cta"><span>Note: </span>TruConversion fully support cross domain tracking.You can select any
                                       site you wish to track with and same tracking code will be installed on this site.</p></td>
                                    </tr>
                                    <tr>
                                     <td colspan="2"><br />
                                      <input id="tcDomain" name="Senden" type="submit" value="Finish" class="button button-primary"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>';
        
        $signupHTML = '<div class="tc-panel">'
                . '<div class="tc-panel-img"><img src="' . plugins_url( '/images/logo.png', __FILE__ ) . '"/></div>'
                . '<div class="tc-panel-content">'
                . '<div class="tc-panel-head">TruConversion Sign Up</div>'
                . '<div class="tc-panel-paragraph">Please signup for new account</div>'
                . '<div class="tc-panel-btn"><a href="#TB_inline?width=600&height=565&inlineId=tc-signup-form" class="button button-primary thickbox signupCaller">Sign Up</a></div>'
                . '</div>'
                . '</div>'
                . '<div id="tc-signup-form" style="display:none;">
                    <div class="tc-logo-header signup"><img src="' . plugins_url( '/images/logo.png', __FILE__ ) . '" alt="TruConversion Logo"/></div>
                    <h2>Sign Up For TruConversion Account</h2>
                    <div class="wrap">
                        <form method="post" name="signin" id="TCsignup">
                            <table>
                                <tbody>
                                    <tr><td><div class="error-msg"></div></td></tr>
                                    <tr>
                                      <td>Full Name</td>
                                      <td><input type="text" name="tc_full_name" id="tc_full_name" class="form-control input-lg" placeholder="First & Last Name" value="' . $this->user->display_name . '" autofocus /></td>
                                    </tr>
                                    <tr>
                                    <td>Email</td>
                                      <td><input type="text" name="tc_signup_email" id="tc_signup_email" class="form-control input-lg" placeholder="name@company.com" value="' . $this->user->user_email . '" /></td>
                                    </tr>
                                    <tr>
                                    <td>Password</td>
                                      <td><input type="password" id="tc_signup_password" class="form-control input-lg" name="tc_signup_password" placeholder="Choose your password" value="" /></td>
                                    </tr>
                                    <tr>
                                      <td>Account</td>
                                      <td><input type="text" name="tc_company_name" id="tc_company_name" class="form-control input-lg" placeholder="Enter your account name" value="' . get_option('blogname') . '" /></td>
                                    </tr>
                                    <tr>
                                      <td>Website</td>
                                      <td><input type="text" name="tc_domain" placeholder="www.example.com" class="form-control input-sm parsley-validated" id="tc_domain" value="' . home_url() . '"></td>
                                    </tr>
                                    <tr class="tc-terms">
                                      <td><input type="checkbox" id="tc_acceptterms" name="tc_acceptterms" value="1"></td>
                                      <td><span>I accept the <a href="https://www.truconversion.com/terms-of-use.html" target="_blank">Terms &amp; Conditions of use</a></span></td>
                                    </tr>
                                    <tr>
                                     <td colspan="2"><br />
                                        <input id="tcSignUp" name="Senden" type="submit" value="Sign Up" class="button button-primary"></td>
                                    </tr>
                                    <tr style="display:none;">
                                       <td colspan="2"><p class="below-cta">Have an account? <a href="#">Sign In</a></p></td>
                                    </tr>
                                  </tbody>
                            </table>
                        </form>
                    </div>
                </div>';
        
        if( $result->data->message == 'signup' ) {
            $html = $signupHTML . $signinHTML;
        } else {
            $html = $signinHTML . $signupHTML;
        }
        
        echo $html;
    }
    
    public function tc_dashboard_callback() {
        $html = '<div class="tc-panel">'
                .'<div class="notice notice-warning"><p>Please make sure TruConverion tracking code should not be installed by other means '
                . 'i.e manual insertion into header file, Google Tag Manager or using any other plugin.</p></div>'
                . '<div class="tc-connection"><img src="' . plugins_url( '/images/connection.png', __FILE__ ) . '"/></div>'
                . '<div class="websiteIdentifier">Website ID: ' . $this->options['domain_id'] . '</div>'
                . '<div class="tc-panel-content">'
                . '<div class="tc-conn-heading">Welcome to TruConversion Connect</div>'
                . '<div class="tc-conn-paragrahph">This enables you to track visitor behaviour on your site. This will help you improve on user engagement and conversion. '
                . 'This is an all in one analytical application to help identify and fix conversion pain points by finding out the WHY behind visitor/users behavior.</div>'
                . '<div class="tc-panel-btn"><a href="' . APP_URL . '" class="button button-primary" target="_blank">View TruConversion Dashboard</a></div>'
                . '</div>'
                . '</div>';
        echo $html;
    }
    
    public function tc_about_callback() {
        $html = '<div class="tc-panel">'
                . '<div class="tc-about-logo"><img src="' . plugins_url( '/images/wp-tc-logo.png', __FILE__ ) . '"/></div>'
                . '<div class="tc-panel-content">'
                . '<div class="tc-conn-heading">Welcome to TruConversion Connect</div>'
                . '<div class="tc-conn-paragrahph">This enables you to track visitor behaviour on your site. This will help you improve on user engagement and conversion. '
                . 'This is an all in one analytical application to help identify and fix conversion pain points by finding out the WHY behind visitor/users behavior.</div>'
                . '</div>'
                . '</div>';
        echo $html;
    }
    
    public function add_scripts( $hook ) {
        if( 'toplevel_page_truconversion-connect' != $hook ) return;
        wp_enqueue_style( 'tcwp-styles', plugins_url( '/css/tcwp-styles.css', __FILE__ ), array(), EDD_VERSION );
        wp_enqueue_script( 'tcwp-scripts', plugins_url( '/js/tcwp-scripts.js', __FILE__ ), array('jquery'), EDD_VERSION );
        $connect_nonce = wp_create_nonce( 'tc_connect' );
        $install_nonce = wp_create_nonce( 'install_nonce' );
        $signup_nonce = wp_create_nonce( 'signup_nonce' );
        wp_localize_script( 'tcwp-scripts', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => $connect_nonce, 'install_nonce' => $install_nonce, 'signup_nonce' => $signup_nonce) );
    }
    
    public function tc_signin_callback() {
        check_ajax_referer('tc_connect');
        if( isset( $_POST ) ) {
            $args = array(
                        'body' => array(
                            'email' => $_POST['email'],
                            'password' => $_POST['password'],
                            'domain' => home_url()
                            ),
                        'sslverify' => false,
                        'timeout' => '60',
                        'redirection' => '5',
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'cookies' => array()
                );
            $response = wp_remote_retrieve_body( wp_remote_post( APP_URL . 'api/v1/rest/getDomainIdByCredentials', $args ) );
            if(!$response){
                $response = json_encode(array('data' => array(), 'error' => array('statusCode' => 500, 'message' => 'Server Error; Please contact TruConversion support')));
            }
            echo $response;
        }
        wp_die();
    }
    
    public function tc_install_code_callback() {
        check_ajax_referer('install_nonce');
        if( isset( $_POST['d'] ) ){
            $this->domain_id = $_POST['d'];
            $this->update_install_status( 'install', $this->domain_id );
            echo 'OK';
        }
        wp_die();
    }
    
    public function tc_signup_callback() {
        check_ajax_referer( 'signup_nonce' );
        if( isset( $_POST ) ){
            $args = array(
                        'body' => array(
                            'email' => $_POST['email'],
                            'password' => $_POST['password'],
                            'full_name' => $_POST['fullname'],
                            'company_name' => $_POST['company'],
                            'domain' => $_POST['url'],
                            'partner' => 'wordpress'
                            ),
                        'sslverify' => false,
                        'timeout' => '45',
                        'redirection' => '5',
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'cookies' => array()
                );
            $response = wp_remote_retrieve_body( wp_remote_post( APP_URL . 'api/v1/rest/signup', $args ));
            if(!$response){
                $response = json_encode(array('data' => array(), 'error' => array('statusCode' => 500, 'message' => 'Server Error; Please contact TruConversion support')));
            }
            echo $response;
        }
        wp_die();
    }
    
    public function get_user_domain() {
        $args = array(
                    'body' => array(
                        'email' => $this->user->user_email,
                        'domain' => home_url()
                        ),
                    'timeout' => '10',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'cookies' => array()
            );
        $response = wp_remote_retrieve_body( wp_remote_post( APP_URL . 'api/v1/rest/getUserDomain', $args ) );
        return json_decode( $response );
    }
    
    public function update_install_status( $status, $domain_id ) {
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
            update_option( $this->option_name, array( 'domain_id' => $domain_id, 'status' => $status), 'no' );
    }
}

if ( is_admin() )
    $my_settings_page = new TruConversion();