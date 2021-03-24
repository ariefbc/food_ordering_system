<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	 function __construct()
 {
   parent::__construct();
   $this->load->model('data_process_timeset','',TRUE);
 }
 
	 function index()
	{
		$data['session_expired_msg'] = "";
		$data['title'] = 'Please Login';

		if ($this->session->userdata('session_expired')) {
			//$data['session_expired_msg'] = "alert('Durasi aplikasi tidak aktif telah tercapai (10 Menit).\\nSilakan login kembali.\\nApplication idle time has been reached (10 minutes).\\nPlease re-login.');";
			//$data['session_expired_msg'] = "alert('Application idle time has been reached (10 minutes).\\nPlease re-login.');";
			$data['session_expired_msg'] = "alert('Your Access Session is expired.\\nPlease re-login.');";
		}

		$this->load->view('Vwlogin', $data);
		//echo $this->input->ip_address()."</br>";
		
		echo $this->data_process_timeset->get_current_datetime();
	}
}
