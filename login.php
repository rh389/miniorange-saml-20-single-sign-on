<?php
/*
Plugin Name: miniOrange SSO using SAML 2.0
Plugin URI: http://miniorange.com/
Description: miniOrange SAML 2.0 SSO enables user to perform Single Sign On with any SAML 2.0 enabled Identity Provider. 
Version: 2.0
Author: miniOrange
Author URI: http://miniorange.com/
*/


include_once dirname( __FILE__ ) . '/mo_login_saml_sso_widget.php';
require('mo-saml-class-customer.php');
require('mo_saml_settings_page.php');

class saml_mo_login {
	
	function __construct() {
		add_action( 'admin_menu', array( $this, 'miniorange_sso_menu' ) );
		add_action( 'admin_init',  array( $this, 'miniorange_login_widget_saml_save_settings' ) );		
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
		register_deactivation_hook(__FILE__, array( $this, 'mo_sso_saml_deactivate'));	
		register_uninstall_hook(__FILE__, array( 'saml_mo_login', 'mo_sso_saml_uninstall'));
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
		add_action( 'plugins_loaded',  array( $this, 'mo_login_widget_text_domain' ) );		
		remove_action( 'admin_notices', array( $this, 'mo_saml_success_message') );
		remove_action( 'admin_notices', array( $this, 'mo_saml_error_message') );
		add_action('wp_authenticate', array( $this, 'mo_saml_authenticate' ) );
		add_action('login_form', array( $this, 'mo_saml_modify_login_form' ) );
	}
	
	function  mo_login_widget_saml_options () {
		global $wpdb;
		update_option( 'mo_saml_host_name', 'https://auth.miniorange.com' );
		$host_name = get_option('mo_saml_host_name');
		
		$customerRegistered = mo_saml_is_customer_registered();
		if( $customerRegistered ) {
			mo_register_saml_sso();
		} else {
			mo_register_saml_sso();
		}	

	}
	
	function mo_saml_success_message() {
		$class = "error";
		$message = get_option('mo_saml_message');
		echo "<div class='" . $class . "'> <p>" . $message . "</p></div>";
	}

	function mo_saml_error_message() {
		$class = "updated";
		$message = get_option('mo_saml_message');
		echo "<div class='" . $class . "'> <p>" . $message . "</p></div>";
	}
		
	public function mo_sso_saml_deactivate() {
		//delete all stored key-value pairs
		delete_option('mo_saml_host_name');
		delete_option('mo_saml_new_registration');
		delete_option('mo_saml_admin_phone');
		delete_option('mo_saml_admin_password');
		delete_option('mo_saml_verify_customer');
		delete_option('mo_saml_admin_customer_key');
		delete_option('mo_saml_admin_api_key');
		delete_option('mo_saml_customer_token');
		delete_option('mo_saml_message');
		delete_option('mo_saml_registration_status');
		
		
		//delete_option('saml_am_first_name');
		//delete_option('saml_am_username');
		//delete_option('saml_am_email');
		//delete_option('saml_am_last_name');
		//delete_option('saml_am_role');
		
		delete_option('mo_saml_idp_config_complete');
	}
	public function mo_sso_saml_uninstall(){
		
		delete_option('mo_saml_host_name');
		delete_option('mo_saml_new_registration');
		delete_option('mo_saml_admin_phone');
		delete_option('mo_saml_admin_email');
		delete_option('mo_saml_admin_password');
		delete_option('mo_saml_verify_customer');
		delete_option('mo_saml_admin_customer_key');
		delete_option('mo_saml_admin_api_key');
		delete_option('mo_saml_customer_token');
		delete_option('mo_saml_message');
		delete_option('mo_saml_registration_status');
		delete_option('saml_idp_config_id');
		delete_option('saml_identity_name');
		delete_option('saml_login_url');
		delete_option('saml_issuer');
		delete_option('saml_x509_certificate');
		delete_option('saml_response_signed');
		delete_option('saml_assertion_signed');
		delete_option('saml_am_first_name');
		delete_option('saml_am_username');
		delete_option('saml_am_email');
		delete_option('saml_am_last_name');
		delete_option('saml_am_default_user_role');
		delete_option('saml_am_role_mapping');
		delete_option('saml_am_group_name');
		delete_option('mo_saml_idp_config_complete');
		delete_option('mo_saml_enable_login_redirect');
		delete_option('mo_saml_allow_wp_signin');
	}
	
	function mo_login_widget_text_domain(){
		load_plugin_textdomain('flw', FALSE, basename( dirname( __FILE__ ) ) .'/languages');
	}
	private function mo_saml_show_success_message() {
		remove_action( 'admin_notices', array( $this, 'mo_saml_success_message') );
		add_action( 'admin_notices', array( $this, 'mo_saml_error_message') );
	}
	function mo_saml_show_error_message() {
		remove_action( 'admin_notices', array( $this, 'mo_saml_error_message') );
		add_action( 'admin_notices', array( $this, 'mo_saml_success_message') );
	}
	function plugin_settings_style() {
		wp_enqueue_style( 'mo_saml_admin_settings_style', plugins_url( 'includes/css/style_settings.css', __FILE__ ) );
		wp_enqueue_style( 'mo_saml_admin_settings_phone_style', plugins_url( 'includes/css/phone.css', __FILE__ ) );
	}
	function plugin_settings_script() {
		wp_enqueue_script( 'mo_saml_admin_settings_script', plugins_url( 'includes/js/settings.js', __FILE__ ) );
		wp_enqueue_script( 'mo_saml_admin_settings_phone_script', plugins_url('includes/js/phone.js', __FILE__ ) );
	}
	function miniorange_login_widget_saml_save_settings(){
		/*$customerRegistered = mo_saml_is_customer_registered();
		if(!$customerRegistered) {
			update_option('mo_saml_message', 'Please <a href="' . add_query_arg( array('tab' => 'login'), $_SERVER['REQUEST_URI'] ) . '">login or register with miniOrange</a> to configure the miniOrange SAML Plugin.');
			$this->mo_saml_show_error_message();
			return;
		}*/
		//Save saml configuration
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_save_settings"){
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Identity Provider Configuration failed.');
				$this->mo_saml_show_error_message();
				return;
			}
			
			//validation and sanitization
			$saml_identity_name = '';
			$saml_login_url = '';
			$saml_issuer = '';
			$saml_x509_certificate = '';
			if( $this->mo_saml_check_empty_or_null( $_POST['saml_identity_name'] ) || $this->mo_saml_check_empty_or_null( $_POST['saml_login_url'] ) || $this->mo_saml_check_empty_or_null( $_POST['saml_issuer'] )  ) {
				update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_saml_show_error_message();
				return;
			} else if(!preg_match("/^\w*$/", $_POST['saml_identity_name'])) {
				update_option( 'mo_saml_message', 'Please match the requested format for Identity Provider Name. Only alphabets, numbers and underscore is allowed.');
				$this->mo_saml_show_error_message();
				return;
			} else{
				$saml_identity_name = sanitize_text_field( $_POST['saml_identity_name'] );
				$saml_login_url = sanitize_text_field( $_POST['saml_login_url'] );
				$saml_issuer = sanitize_text_field( $_POST['saml_issuer'] );
				$saml_x509_certificate = sanitize_text_field( $_POST['saml_x509_certificate'] );
			}
			
			update_option('saml_identity_name', $saml_identity_name);
			update_option('saml_login_url', $saml_login_url);
			update_option('saml_issuer', $saml_issuer);
			update_option('saml_x509_certificate', $_POST['saml_x509_certificate']);	
			if(isset($_POST['saml_response_signed']))
				{
				update_option('saml_response_signed' , 'checked');
				}
			else
				{
				update_option('saml_response_signed' , 'Yes');
				}
			if(isset($_POST['saml_assertion_signed']))
				{
				update_option('saml_assertion_signed' , 'checked');
				}
			else
				{
				update_option('saml_assertion_signed' , 'Yes');
				}
			
			$saveSaml = new Customersaml();
			$outputSaml = json_decode( $saveSaml->save_external_idp_config(), true );

			if(isset($outputSaml['customerId'])) {
				update_option('saml_x509_certificate', $outputSaml['samlX509Certificate']);
				update_option('mo_saml_message', 'Identity Provider details saved successfully');
				$this->mo_saml_show_success_message();
			}
			else {
				update_option('mo_saml_message', 'Identity Provider details could not be saved. Please try again.');
				$this->mo_saml_show_error_message();
			}
			
			//Call to saveConfiguration.
			
			
			/*update_option( 'entity_id', $_POST['entity_id'] );
			update_option( 'sso_url', $_POST['sso_url'] );
			update_option( 'cert_fp', $_POST['cert_fp']);
			
			*/
		}
		//Save Attribute Mapping
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_attribute_mapping"){
			
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Attribute Mapping failed.');
				$this->mo_saml_show_error_message();
				return;
			}
		
			update_option('saml_am_username', $_POST['saml_am_username']);
			update_option('saml_am_email', $_POST['saml_am_email']);
			update_option('saml_am_first_name', $_POST['saml_am_first_name']);
			update_option('saml_am_last_name', $_POST['saml_am_last_name']);
			update_option('saml_am_group_name', $_POST['saml_am_group_name']);
			update_option('saml_am_account_matcher', $_POST['saml_am_account_matcher']);
			
			update_option('mo_saml_message', 'Attribute Mapping details saved successfully');
			$this->mo_saml_show_success_message();
		
		}
		//Save Role Mapping
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_role_mapping"){
			
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Role Mapping failed.');
				$this->mo_saml_show_error_message();
				return;
			}
			update_option('saml_am_default_user_role', $_POST['saml_am_default_user_role']);
	
			$wp_roles = new WP_Roles();
			$roles = $wp_roles->get_names();
			$role_mapping;
			foreach ($roles as $role_value => $role_name) {
				$attr = 'saml_am_group_attr_values_' . $role_value;
				$role_mapping[$role_value] = $_POST[$attr];
			}
			update_option('saml_am_role_mapping', $role_mapping);
			update_option('mo_saml_message', 'Role Mapping details saved successfully.');
			$this->mo_saml_show_success_message();
		}
		//Save Wordpress SSO to another site settings
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_cross_domain_save_settings"){
			
			//Validation and sanitization
			$cd_destination_site_url = '';
			$cd_shared_key = '';
			if( $this->mo_saml_check_empty_or_null( $_POST['cd_destination_site_url'] )  ) {
				update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_saml_show_error_message();
				return;
			}else{
				$cd_destination_site_url = sanitize_text_field( $_POST['cd_destination_site_url'] );
				$cd_shared_key = sanitize_text_field( $_POST['cd_shared_key'] );
			}
			update_option( 'cd_destination_site_url', $cd_destination_site_url );
			update_option( 'cd_shared_key', $cd_shared_key );
		}
				
		if( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_register_customer" ) {	//register the admin to miniOrange
		
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Registration failed.');
				$this->mo_saml_show_error_message();
				return;
			}
			
			//validation and sanitization
			$email = '';
			$phone = '';
			$password = '';
			$confirmPassword = '';
			if( $this->mo_saml_check_empty_or_null( $_POST['email'] ) || $this->mo_saml_check_empty_or_null( $_POST['phone'] ) || $this->mo_saml_check_empty_or_null( $_POST['password'] ) || $this->mo_saml_check_empty_or_null( $_POST['confirmPassword'] ) ) {
				update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_saml_show_error_message();
				return;
			} else if( strlen( $_POST['password'] ) < 8 || strlen( $_POST['confirmPassword'] ) < 8){
				update_option( 'mo_saml_message', 'Choose a password with minimum length 8.');
				$this->mo_saml_show_error_message();
				return;
			} else{
				$email = sanitize_email( $_POST['email'] );
				$phone = sanitize_text_field( $_POST['phone'] );
				$password = sanitize_text_field( $_POST['password'] );
				$confirmPassword = sanitize_text_field( $_POST['confirmPassword'] );
			}
			
			update_option( 'mo_saml_admin_email', $email );
			update_option( 'mo_saml_admin_phone', $phone );
			if( strcmp( $password, $confirmPassword) == 0 ) {
				update_option( 'mo_saml_admin_password', $password );
				
				$customer = new CustomerSaml();
				$content = json_decode($customer->check_customer(), true);
				if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){
					$content = json_decode($customer->send_otp_token(), true);
										if(strcasecmp($content['status'], 'SUCCESS') == 0) {
											update_option( 'mo_saml_message', ' A one time passcode is sent to ' . get_option('mo_saml_admin_email') . '. Please enter the otp here to verify your email.');
											update_option('mo_saml_transactionId',$content['txId']);
											update_option('mo_saml_registration_status','MO_OTP_DELIVERED_SUCCESS');

											$this->mo_saml_show_success_message();
										}else{
											update_option('mo_saml_message','There was an error in sending email. Please click on Resend OTP to try again.');
											update_option('mo_saml_registration_status','MO_OTP_DELIVERED_FAILURE');
											$this->mo_saml_show_error_message();
										}
				}else{
						$this->get_current_customer();
				}
				
			} else {
				update_option( 'mo_saml_message', 'Passwords do not match.');
				delete_option('mo_saml_verify_customer');
				$this->mo_saml_show_error_message();
			}
	
		}
		if(isset($_POST['option']) and $_POST['option'] == "mo_saml_validate_otp"){
			
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Validate OTP failed.');
				$this->mo_saml_show_error_message();
				return;
			}

			//validation and sanitization
			$otp_token = '';
			if( $this->mo_saml_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo_saml_message', 'Please enter a value in otp field.');
				update_option('mo_saml_registration_status','MO_OTP_VALIDATION_FAILURE');
				$this->mo_saml_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}

			$customer = new CustomerSaml();
			$content = json_decode($customer->validate_otp_token(get_option('mo_saml_transactionId'), $otp_token ),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {

					$this->create_customer();
			}else{
				update_option( 'mo_saml_message','Invalid one time passcode. Please enter a valid otp.');
				update_option('mo_saml_registration_status','MO_OTP_VALIDATION_FAILURE');
				$this->mo_saml_show_error_message();
			}
		}
		if( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_verify_customer" ) {	//register the admin to miniOrange
		
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Login failed.');
				$this->mo_saml_show_error_message();
				return;
			}
			
			//validation and sanitization
			$email = '';
			$password = '';
			if( $this->mo_saml_check_empty_or_null( $_POST['email'] ) || $this->mo_saml_check_empty_or_null( $_POST['password'] ) ) {
				update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_saml_show_error_message();
				return;
			} else{
				$email = sanitize_email( $_POST['email'] );
				$password = sanitize_text_field( $_POST['password'] );
			}
		
			update_option( 'mo_saml_admin_email', $email );
			update_option( 'mo_saml_admin_password', $password );
			$customer = new Customersaml();
			$content = $customer->get_customer_key();
			$customerKey = json_decode( $content, true );
			if( json_last_error() == JSON_ERROR_NONE ) {
				update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
				update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
				update_option( 'mo_saml_customer_token', $customerKey['token'] );
				update_option( 'mo_saml_admin_phone', $customerKey['phone'] );
				update_option('mo_saml_admin_password', '');
				update_option( 'mo_saml_message', 'Customer retrieved successfully');
				update_option('mo_saml_registration_status' , 'Existing User');
				delete_option('mo_saml_verify_customer');
				$this->mo_saml_show_success_message(); 
			} else {
				update_option( 'mo_saml_message', 'Invalid username or password. Please try again.');
				$this->mo_saml_show_error_message();		
			}
			update_option('mo_saml_admin_password', '');
		}else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_contact_us_query_option" ) {
			
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.');
				$this->mo_saml_show_error_message();
				return;
			}
			
			// Contact Us query
			$email = $_POST['mo_saml_contact_us_email'];
			$phone = $_POST['mo_saml_contact_us_phone'];
			$query = $_POST['mo_saml_contact_us_query'];
			$customer = new CustomerSaml();
			if ( $this->mo_saml_check_empty_or_null( $email ) || $this->mo_saml_check_empty_or_null( $query ) ) {
				update_option('mo_saml_message', 'Please fill up Email and Query fields to submit your query.');
				$this->mo_saml_show_error_message();
			} else {
				$submited = $customer->submit_contact_us( $email, $phone, $query );
				if ( $submited == false ) {
					update_option('mo_saml_message', 'Your query could not be submitted. Please try again.');
					$this->mo_saml_show_error_message();
				} else {
					update_option('mo_saml_message', 'Thanks for getting in touch! We shall get back to you shortly.');
					$this->mo_saml_show_success_message();
				}
			}
		}
		else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_resend_otp" ) {
			
			if(!_is_curl_installed()) {
				update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Resend OTP failed.');
				$this->mo_saml_show_error_message();
				return;
			}

					    $customer = new CustomerSaml();
						$content = json_decode($customer->send_otp_token(), true);
									if(strcasecmp($content['status'], 'SUCCESS') == 0) {
											update_option( 'mo_saml_message', ' A one time passcode is sent to ' . get_option('mo_saml_admin_email') . ' again. Please check if you got the otp and enter it here.');
											update_option('mo_saml_transactionId',$content['txId']);
											update_option('mo_saml_registration_status','MO_OTP_DELIVERED_SUCCESS');
											$this->mo_saml_show_success_message();
									}else{
											update_option('mo_saml_message','There was an error in sending email. Please click on Resend OTP to try again.');
											update_option('mo_saml_registration_status','MO_OTP_DELIVERED_FAILURE');
											$this->mo_saml_show_error_message();
									}

		}else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_go_back" ){
				update_option('mo_saml_registration_status','');
				delete_option('mo_saml_new_registration');
				delete_option('mo_saml_admin_email');

		} /*else if( isset( $_POST['option']) and $_POST['option'] == "mo_saml_idp_config") {
				update_option( 'mo_saml_idp_config_complete', isset($_POST['mo_saml_idp_config_complete']) ? $_POST['mo_saml_idp_config_complete'] : 0);
				if(get_option('mo_saml_idp_config_complete')) {
					update_option( 'mo_saml_message', 'Please proceed to <a href="' . add_query_arg( array('tab' => 'save'), $_SERVER['REQUEST_URI'] ) . '">Configure Service Provider</a> and enter the required information.');
					$this->mo_saml_show_success_message();
				} else {
					update_option( 'mo_saml_message', 'You need to complete your Identity Provider configuration before you can enter the values for the fields given in Configure SAML Plugin.');
					$this->mo_saml_show_error_message();
				}

		}*/ else if( isset( $_POST['option']) and $_POST['option'] == "mo_saml_enable_login_redirect_option") {
			if(mo_saml_is_sp_configured()) {
				if(array_key_exists('mo_saml_enable_login_redirect', $_POST)) {
					$enable_redirect = $_POST['mo_saml_enable_login_redirect'];
				} else {
					$enable_redirect = 'false';
				}				
				if($enable_redirect == 'true') {
					update_option('mo_saml_enable_login_redirect', 'true');
					update_option('mo_saml_allow_wp_signin', 'true');
				} else {
					update_option('mo_saml_enable_login_redirect', '');
					update_option('mo_saml_allow_wp_signin', '');
				}
				update_option( 'mo_saml_message', 'General settings updated.');
				$this->mo_saml_show_success_message();
			} else {
				update_option( 'mo_saml_message', 'Please complete <a href="' . add_query_arg( array('tab' => 'save'), $_SERVER['REQUEST_URI'] ) . '" />Service Provider</a> configuration first.');
				$this->mo_saml_show_error_message();
			}
		} else if( isset( $_POST['option']) and $_POST['option'] == "mo_saml_allow_wp_signin_option") {
			if(array_key_exists('mo_saml_allow_wp_signin', $_POST)) {
				$allow_wp_signin = $_POST['mo_saml_allow_wp_signin'];
			} else {
				$allow_wp_signin = 'false';
			}
			if($allow_wp_signin == 'true') {
				update_option('mo_saml_allow_wp_signin', 'true');
			} else {
				update_option('mo_saml_allow_wp_signin', '');
			}
			update_option( 'mo_saml_message', 'General settings updated.');
			$this->mo_saml_show_success_message();
		}
		
	}
	
	function create_customer(){
			$customer = new CustomerSaml();
			$customerKey = json_decode( $customer->create_customer(), true );
			if( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0 ) {
						$this->get_current_customer();
			} else if( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
											update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
											update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
											update_option( 'mo_saml_customer_token', $customerKey['token'] );
											update_option('mo_saml_admin_password', '');
											update_option( 'mo_saml_message', 'Thank you for registering with miniorange.');
											update_option('mo_saml_registration_status','MO_OPENID_REGISTRATION_COMPLETE');
											delete_option('mo_saml_verify_customer');
											delete_option('mo_saml_new_registration');
											$this->mo_saml_show_success_message();
			}
			update_option('mo_saml_admin_password', '');
	}

	function get_current_customer(){
			$customer = new CustomerSaml();
			$content = $customer->get_customer_key();
			$customerKey = json_decode( $content, true );
						if( json_last_error() == JSON_ERROR_NONE ) {
								
								update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
								update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
								update_option( 'mo_saml_customer_token', $customerKey['token'] );
								update_option('mo_saml_admin_password', '' );
								update_option( 'mo_saml_message', 'Your account has been retrieved successfully.' );
								delete_option('mo_saml_verify_customer');
								delete_option('mo_saml_new_registration');
								$this->mo_saml_show_success_message();

					} else {
								update_option( 'mo_saml_message', 'You already have an account with miniOrange. Please enter a valid password.');
								update_option('mo_saml_verify_customer', 'true');
								delete_option('mo_saml_new_registration');
								$this->mo_saml_show_error_message();

					}

	}

	public function mo_saml_check_empty_or_null( $value ) {
	if( ! isset( $value ) || empty( $value ) ) {
		return true;
	}
	return false;
	}
	
	function miniorange_sso_menu() {
		
		//Add miniOrange SAML SSO
		$page = add_menu_page( 'MO SAML Settings ' . __( 'Configure SAML Identity Provider for SSO', 'mo_saml_settings' ), 'miniOrange SAML 2.0 SSO', 'administrator', 'mo_saml_settings', array( $this, 'mo_login_widget_saml_options' ), plugin_dir_url(__FILE__) . 'images/miniorange.png' );

		
	}
	
	function mo_saml_redirect_for_authentication() {
		$mo_redirect_url = get_option('mo_saml_host_name') . "/moas/rest/saml/request?id=" . get_option('mo_saml_admin_customer_key') . "&returnurl=" . urlencode( site_url() . "/?option=readsamllogin" );
		header('Location: ' . $mo_redirect_url);
		exit();
	}
	
	function mo_saml_authenticate() {
		if( get_option('mo_saml_enable_login_redirect') == 'true' ) {
			if( isset($_GET['loggedout']) && $_GET['loggedout'] == 'true' ) {
				header('Location: ' . site_url());
				exit();
			} elseif ( get_option('mo_saml_allow_wp_signin') == 'true' ) {
				if( ( isset($_GET['saml_sso']) && $_GET['saml_sso'] == 'false' ) || ( isset($_POST['saml_sso']) && $_POST['saml_sso'] == 'false' ) ) {
					return;
				} elseif ( isset( $_REQUEST['redirect_to']) ) {
					$redirect_to = $_REQUEST['redirect_to'];
					if( strpos( $redirect_to, 'wp-admin') !== false && strpos( $redirect_to, 'saml_sso=false') !== false) {
						return;
					} 
				}
			}
			$this->mo_saml_redirect_for_authentication();
		}
	}
	
	function mo_saml_modify_login_form() {
		echo '<input type="hidden" name="saml_sso" value="false">'."\n";
	}
}
new saml_mo_login;