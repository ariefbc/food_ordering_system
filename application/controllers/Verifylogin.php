<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Verifylogin extends CI_Controller {

	private $app_code;
	
 function __construct()
 {
   parent::__construct();
   $this->load->model('user','',TRUE);
   $this->load->model('Data_process_log','',TRUE);
   $this->load->model('data_process_timeset','',TRUE);
   $this->load->model('data_process_appreg','app_info',TRUE);
   $this->load->library('app_initializer');
   $this->load->model('data_process_init','',TRUE);
   
   $app_init = $this->app_initializer->app_init();
   $this->app_code = $app_init['app_code'];

 }

 function index()
 {
 	if ($this->session->userdata('session_expired')) {
   		$this->session->unset_userdata('session_expired');
   	}
   	
   $username = $this->security->xss_clean($this->input->post('username'));
   $password = $this->security->xss_clean($this->input->post('password'));

   $result = $this->user->login($username, $password);
	
	$allow = $this->user->check_allow_login($username);

	if ($allow == '' && $result) {
		foreach ($result as $row) {
			if (strtolower($this->app_code) != strtolower($row->app_code)) {
				$allow = '<font color="red"><strong>YOU ARE NOT AUTHORIZED TO USE THIS APPLICATION<br>CONTACT YOUR APPLICATION ADMINISTRATOR</font></strong>';
			} else {
				$allow = '';
				break;
			}
		}
	}

	if ($allow != '') {
		$data['title'] = $allow;
		$this->load->view('Vwlogin', $data);
		
		//echo $this->input->ip_address()."</br>";
		
		echo $this->data_process_timeset->get_current_datetime();
		return;
	}
	
	$app_info = $this->app_info->get_data_app($this->app_code);
	$tmp['app_id'] = $app_info['id'];
	$this->session->set_userdata('logged_in', $tmp);
	
	if ($result) {
	$sess_array = array();
   	$row = $result[0];

   	$sess_array = array(
		'id' => $row->id,
		'username' => $row->username,
		'fullname' => $row->fullname,
		'language' => $row->language_id,
		'usergroup_name' => array(),
		'app_title' => $app_info['app_title'].' '.$app_info['app_version'],
		'app_id' => $app_info['id'],
		'is_strong_password_active' => $app_info['is_strong_password_active']	
	);

	foreach($result as $row) {
       array_push($sess_array['usergroup_name'], $row->usergroup_name);
     }

	$this->session->set_userdata('logged_in', $sess_array);

	$this->Data_process_log->update_log_login($user = $username,$attemp_status = 'Login Success');
		 $this->user->clear_fail_log($username);
		 
		 if ((int) $row->is_password_reset != 1) {
		 	#if login is from email link then redirect to approval form page
		 	if ($this->session->userdata('landingpage')) {
		 		$landingpage_data_array = $this->session->userdata('landingpage');
				$this->session->unset_userdata('landingpage');

				redirect("eform/edit/".$landingpage_data_array["approval_page"]."/".$landingpage_data_array["request_id"]);
		 	} else {
		 		redirect('home');	
		 	}
		 	#
		 } else {
		 	#if login is from email link and password is reset then redirect to change password form
		 	if ($app_info['is_strong_password_active'] == 1) {
				$this->session->set_userdata('password_is_default', TRUE);
			}

			$this->session->set_userdata('is_password_reset', TRUE);
			
	 		redirect('chdpwd');
		 	#
		}
	} else {
   		if ($this->user->check_user($username)) {
   			$this->Data_process_log->update_log_login_fail($username);
			if ($this->user->count_login_fail_attempt($username,$this->app_code) >= $this->user->get_allow_login_attempt()) {
				$this->user->update_set_allow_login($username);
				$this->user->clear_fail_log($username);		
			}
		}
		
		$allow = $this->user->check_allow_login($username);
		if ($allow != '') {
			$data['title'] = $allow;
		} else {
			$data['title'] = 'Invalid Password or Username';	
		}
   		
		$this->Data_process_log->update_log_login($username,'Login Fail');
		$this->load->view('Vwlogin', $data);
		
		//echo $this->input->ip_address()."</br>";
		
		echo $this->data_process_timeset->get_current_datetime();
   }
 }
}
?>