<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Test_map extends CI_Controller {
	private $view_name = 'Vwtest_map';
	private $control_name = 'home';
	private $content_name = 'content/content_home';
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_comm_log','dp_comm_log',TRUE);
		$this->load->model('data_process_translate','',TRUE);
	}
	
	
	function index(){
		if($this->session->userdata('logged_in')) {
			//$header = 'Approval Log';
			$header = 'Riwayat Status Pengajuan';

			$datasession = $this->session->userdata('logged_in');
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];
			
			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data['menu'] = NULL;
			$data['content_header'] = "Test Google Map";
			$data['breadcrumb'] = NULL;
			$data['content'] = "";
		   $this->load->view($this->view_name,$data);	
		}else{
			header ("Location: ".base_url());
		}
	}
}
