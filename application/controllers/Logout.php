<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 
 */
class Logout extends CI_Controller {
	
	function __construct() {
		parent::__construct();
		$this->load->model('Data_process_log','',TRUE);
	}
	
	function index(){
		$datasession = $this->session->userdata('logged_in');
		$user = $datasession['username'];
		$this->Data_process_log->update_log_login($user,'Logout');
		$this->session->unset_userdata('logged_in');
		$this->session->unset_userdata('main_id');
	   $this->session->sess_destroy();
	   //redirect('login', 'refresh');
	   header ("Location: ".base_url());
	}
}


