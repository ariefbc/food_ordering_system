<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Chdpwd extends CI_Controller {
	private $view_name = 'Vwhome';
	private $control_name = 'chdpwd';
	private $content_name = 'content/content_chdpwd';
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_myprofile','',TRUE);
		$this->load->model('data_process_translate','',TRUE);
	}
	
	function show_interface($java_alert = array(),$msgerror = '') {
			$datasession = $this->session->userdata('logged_in');
			$language = $datasession['language'];
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];

			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data['menu'] = $this->menu->generatemenu($this->control_name,$menu);
			$data['content_header'] = $this->content->content_header($language);
			$data['breadcrumb'] = $this->content->breadcrumb($language);
			$data['content'] = $this->content->load_content($language,$this->control_name,$msgerror);

			$data['gmap_initialize'] = '';
			$data['java_alert'] = $java_alert;
			$this->load->view($this->view_name,$data);
	}
	
	function index() {
		if($this->session->userdata('logged_in')) {
			$this->show_interface();	
		}else{
			header ("Location: ".base_url());
		}
	}
	
	function process() {
		$datasession = array();
		$datasession = $this->session->userdata('logged_in');
		$user = $datasession['username'];
		$language = $datasession['language'];
		
		$oldpassword = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : NULL;
		$datapost['password'] = isset($_POST['newpassword']) ? $_POST['newpassword'] : NULL;
		$verifynewpassword = isset($_POST['verifynewpassword']) ? $_POST['verifynewpassword'] : NULL;
		
		$msgerror = '';
		$java_alert = array();

		if ($msgerror == '' && ($datapost['password'] == NULL || $datapost['password'] == '' || $verifynewpassword == NULL || $verifynewpassword == '')) {
			$msgerror = $this->data_process_translate->check_vocab($language,"new password or verification new password cannot be empty");
			$java_alert['form_control_name'] = 'newpassword';
		}
		
		if ($msgerror == '' && $datapost['password'] != $verifynewpassword) {
			$msgerror = $this->data_process_translate->check_vocab($language,"new and verification password are mismatched");
			$java_alert['form_control_name'] = 'newpassword';
		}
		
		if ($msgerror == '' && count($this->data_process_myprofile->check_oldpassword($user,$oldpassword)->result()) == 0 && $datapost['password'] == $verifynewpassword) {
			$msgerror = $this->data_process_translate->check_vocab($language,"Invalid Old Password");
			$java_alert['form_control_name'] = 'oldpassword';
		}
		
		if($msgerror == '') {
			if ($datasession['is_strong_password_active'] == 1) {
				$has8characters = (mb_strlen($datapost['password']) >= 8);
				$hasAlphaLower = preg_match('/[a-z]/', $datapost['password']);
				$hasAlphaUpper = preg_match('/[A-Z]/', $datapost['password']);
				$hasNumber = preg_match('/[0-9]/', $datapost['password']);
				$hasNonAlphaNum = preg_match('/[\!\@#$%\?&\*\(\)_\-\+=]/', $datapost['password']);
				
				if (!$has8characters || !$hasAlphaLower || !$hasAlphaUpper || !$hasNumber || !$hasNonAlphaNum) {
					$msgerror = $this->data_process_translate->check_vocab($language,"Password does not meet the requirements! \\nIt must be alphanumeric minimum 8 characters long with atleast: 1 symbol, 1 capital letter, 1 lower letter");
					
					$java_alert['form_control_name'] = 'oldpassword';
				}
			}
		}

		if ($msgerror == '') {
				$this->data_process_myprofile->update_password($user,$datapost);
				$msgerror = $this->data_process_translate->check_vocab($language,"Password has been changed, please Logout and Re-Login");
				$java_alert['form_control_name'] = 'oldpassword';
		}

		$java_alert['msg'] = $msgerror;

		$this->show_interface($java_alert,str_replace("\\n","",$msgerror));
	}
}
