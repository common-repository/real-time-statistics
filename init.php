<?php

/**
 * Plugin Name: Tidio Live
 * Plugin URI: http://www.tidiolive.com
 * Description: 
 * Version: 1.0.0
 * Author: Tidio Ltd.
 * Author URI: http://www.tidiolive.com
 * License: GPL2
 */
 
class TidioLive {

    private $scriptUrl = '//www.tidiolive.com/redirect/';

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenuLink'));
        add_action('admin_footer', array($this, 'adminJS'));

        self::getPrivateKey();
        
        if(!is_admin()){
            add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        }
        
        add_action('deactivate_'.plugin_basename(__FILE__), array($this, 'uninstall'));	

        add_action('wp_ajax_tidio_live_redirect', array($this, 'ajaxTidioLiveRedirect'));	

    }
	
	// Ajax - Create New Project
	
	public function ajaxTidioLiveRedirect(){
		
		if(!empty($_GET['access_status']) && !empty($_GET['private_key']) && !empty($_GET['public_key'])){
			
			update_option('tidio-live-external-public-key', $_GET['public_key']);
			update_option('tidio-live-external-private-key', $_GET['private_key']);
			
			$view = array(
				'mode' => 'redirect',
				'redirect_url' => self::getRedirectUrl($_GET['private_key'])
			);
			
		} else {
		
			$view = array(
				'mode' => 'access_request',
				'access_url' => self::getAccessUrl()
			);
							
		}
		
		require "views/ajax-tidio-live-redirect.php";

		exit;
		
	}
	    
    // Front End Scripts
    
    public function enqueueScripts(){
    	wp_enqueue_script('tidio-live', 'https://www.tidiolive.com/uploads/redirect/' . self::getPublicKey() . '.js', array(), '1.0.0', true);
    }

    // Admin JavaScript

    public function adminJS() {
		
		$privateKey = self::getPrivateKey();
		$redirectUrl = '';
		
		if($privateKey && $privateKey!='false'){
        	$redirectUrl = self::getRedirectUrl($privateKey);
		} else {
			$redirectUrl = admin_url('admin-ajax.php?action=tidio_live_redirect');
		}
		
		echo "<script> jQuery('a[href=\"admin.php?page=tidio-live\"]').attr('href', '".$redirectUrl."').attr('target', '_blank') </script>";
		
	}

    // Menu Pages

    public function addAdminMenuLink() {

        $optionPage = add_menu_page(
			'Tidio Live', 'Tidio Live', 'manage_options', 'tidio-live', array($this, 'addAdminPage'), content_url() . '/plugins/tidio-live/media/img/icon.png'
        );
    }

    public function addAdminPage() {
        // Set class property
        $dir = plugin_dir_path(__FILE__);
        include $dir . 'options.php';
    }
    
    // Uninstall
	
    public function uninstall(){
    }

    // Get Private Key

    public static function getPrivateKey() {
		
        $privateKey = get_option('tidio-live-external-private-key');

        if ($privateKey) {
            return $privateKey;
        }

        @$data = file_get_contents(self::getAccessUrl());
        if (!$data) {
            update_option('tidio-live-external-private-key', 'false');
            return false;
        }

        @$data = json_decode($data, true);
        if (!$data || !$data['status']) {
            update_option('tidio-live-external-private-key', 'false');
            return false;
        }

        update_option('tidio-live-external-private-key', $data['value']['private_key']);
        update_option('tidio-live-external-public-key', $data['value']['public_key']);

        return $data['value']['private_key'];
    }
	
	// Get Access Url
	
	public static function getAccessUrl(){
		
		return 'http://www.tidiolive.com/access/create?url='.urlencode(site_url()).'&platform=wordpress&email='.urlencode(get_option('admin_email')).'&_ip='.$_SERVER['REMOTE_ADDR'];
		
	}
	
	public static function getRedirectUrl($privateKey){
		
		return 'http://external.tidiolive.com/access?privateKey='.$privateKey;
		
	}
	
	// Get Public Key

    public static function getPublicKey() {

        $publicKey = get_option('tidio-live-external-public-key');

        if ($publicKey) {
            return $publicKey;
        }

        self::getPrivateKey();

        return get_option('tidio-live-external-public-key');
    }
    
    

}

$tidioLive = new TidioLive();

