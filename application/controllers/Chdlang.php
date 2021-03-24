<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Chdlang extends CI_Controller {
	private $view_name = 'Vwhome';
	private $control_name = 'chdlang';
	private $content_name = 'content/content_chdlang';
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_myprofile','',TRUE);
		$this->load->model('data_process_translate','',TRUE);
	}
	
	function show_interface($java_alert = array(),$msgerror = ''){
			$datasession = $this->session->userdata('logged_in');
			$language = $datasession['language'];
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];

			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data['menu'] = $this->menu->generatemenu($this->control_name,$menu);
			$data['content_header'] = $this->content->content_header($language);
			$data['breadcrumb'] = $this->content->breadcrumb($language);

			$language_query = $this->data_process_myprofile->get_language_country();
			$language_options = array();
			if ($language_query['language_list']) {
				foreach ($language_query['language_list'] as $row) {
					$language_options['language_list'][$row->Id] = $row->lang_country;
				}
				$language_options['selected'] = $language_query['selected'];
			}

			$data['content'] = $this->content->load_content($language,$this->control_name,$language_options,$msgerror);

			$data['gmap_initialize'] = '';
			$data['java_alert'] = $java_alert;
			$this->load->view($this->view_name,$data);
	}
	
	function index(){
		if($this->session->userdata('logged_in')) {
			$this->show_interface();	
		}else{
			header ("Location: ".base_url());
		}
	}
	
	function process(){
		$datasession = array();
		$datasession = $this->session->userdata('logged_in');
		$user = $datasession['username'];
		$language = $datasession['language'];
		
		$datapost['language_id'] = isset($_POST['language']) ? $_POST['language'] : NULL;
		
		$msgerror = '';
		$java_alert = array();

		if ($msgerror == ''){
				$this->data_process_myprofile->update_language($user,$datapost);
				$msgerror = $this->data_process_translate->check_vocab($language,"Language Setting has been changed. Please logout and login again to apply changes.");
				$java_alert['form_control_name'] = 'chdlang';
		}

		$java_alert['msg'] = $msgerror;

		$this->show_interface($java_alert,$msgerror);
	}
}
