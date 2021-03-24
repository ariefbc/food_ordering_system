<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reset_pwd extends CI_Controller {

	private $view_name = 'Vw_reset_pwd';
	private $control_name = 'reset_pwd';
	private $content_name = 'content/content_reset_pwd';
	private $app_code;

	 function __construct()
	 {
	   parent::__construct();
	   $this->load->model('data_process_timeset','',TRUE);
	   $this->load->library($this->content_name,'','content');
	   $this->load->model('data_process_appreg','app_info',TRUE);
	   $this->load->model('data_process_reset_pwd','dp_reset_pwd',TRUE);
   	   $this->load->library('app_initializer');

   	   $app_init = $this->app_initializer->app_init();
   	   $this->app_code = $app_init['app_code'];
	 }
 
	 function verify_email_to_app($datapost){
	 	$app_info = $this->app_info->get_data_app($this->app_code);
	 	$app_id = $app_info['id'];

	 	$query = $this->dp_reset_pwd->verify_email($datapost['email'],$app_id);

	 	if (count($query) == 1){
	 		return TRUE;
	 	}else{
	 		return FALSE;
	 	}
	 }

	 function process_new_pwd($datapost){
	 	$user = $this->dp_reset_pwd->clear_request($datapost);

	 	$this->load->model('data_process_myprofile','',TRUE);
	 	$this->data_process_myprofile->update_password($user,$datapost);
	 	
	 	return NULL;
	 }

	function resetting($reset_code = NULL){
		if ($reset_code == NULL){
			redirect(site_url().$this->control_name);
		}else{
			$data['title'] = $this->content->content_title();
			if ($this->dp_reset_pwd->verify_reset_code($reset_code)){
				$datapost['process_save'] = isset($_POST['savepwd']) ? TRUE : FALSE;

				if (!$datapost['process_save']){
					$data['content'] = $this->content->content_resetting($this->control_name,$reset_code);
				}else{
					$datapost['pwd1'] = isset($_POST['pwd1']) ? $_POST['pwd1'] : NULL;
					$datapost['pwd2'] = isset($_POST['pwd2']) ? $_POST['pwd2'] : NULL;

					$msg_error = NULL;
					$msg_error = ($msg_error == NULL && ($datapost['pwd1'] == NULL || $datapost['pwd2'] == NULL)) ? 'Kata sandi tidak boleh kosong / Password cannot be empty' : $msg_error;
					$msg_error = ($msg_error == NULL && $datapost['pwd1'] != $datapost['pwd2']) ? 'Kedua kata sandi tidak sama / Both passwords are not the same' : $msg_error;

					if ($msg_error == NULL) {
						$app_info = $this->app_info->get_data_app($this->app_code);
						if ($app_info['is_strong_password_active'] == 1) {
							$has8characters = (mb_strlen($datapost['pwd1']) >= 8);
							$hasAlphaLower = preg_match('/[a-z]/', $datapost['pwd1']);
							$hasAlphaUpper = preg_match('/[A-Z]/', $datapost['pwd1']);
							$hasNumber = preg_match('/[0-9]/', $datapost['pwd1']);
							$hasNonAlphaNum = preg_match('/[\!\@#$%\?&\*\(\)_\-\+=]/', $datapost['pwd1']);

							if (!$has8characters || !$hasAlphaLower || !$hasAlphaUpper || !$hasNumber || !$hasNonAlphaNum) {
								$msg_error = "Kata Sandi baru tidak memenuhi ketentuan keamanan.<br>Panjang kata sandi harus minimal 8 karakter dengan minimal: 1 karakter simbol, 1 huruf kapital, 1 huruf kecil / Password does not meet the requirements! It must be alphanumeric minimum 8 characters long with atleast: 1 symbol, 1 capital letter, 1 lower letter";
							}
						}
					}

					if ($msg_error != NULL){
						$data['content'] = $this->content->content_resetting($this->control_name,$reset_code,$msg_error);
					}else{
						$datapost['reset_code'] = isset($_POST['passcode']) ? $_POST['passcode'] : NULL;
						$datapost['password'] = $datapost['pwd1'];
						$this->process_new_pwd($datapost);
						
						$data['content'] = "Your new password has been saved can be used to login to the application.<br><br>Your new password has been saved can be used to login to the application.<br>".anchor(site_url(),'Login',NULL);
					}
				}
			}else{
				$data['content'] = "Permintaan untuk reset password telah melewati batas waktu. Silakan membuat permintaan reset password baru.<br><br>This reset password request is expired. Please submit another reset password request.<br>".anchor(site_url().'reset_pwd','Masukkan ulang alamat email untuk reset / Re-entry email to reset',NULL);
			}
			$this->load->view($this->view_name, $data);
		}
	}

	function process_reset_request($datapost){
		$app_info = $this->app_info->get_data_app($this->app_code);
	 	$app_id = $app_info['id'];
	 	$email_sender_name = $app_info['email_sender_name'];

	 	$query = $this->dp_reset_pwd->verify_email($datapost['email'],$app_id);
	 	$user = $query[0]->username;
	 	$fullname = $query[0]->fullname;

	 	$this->dp_reset_pwd->insert_reset_request($user,$fullname,$datapost['email'],$this->app_code);

	 	$smtp_config = $this->dp_reset_pwd->get_data_smtp();
	 	$config = Array(
				        'protocol' => 'smtp',
				        'smtp_host' => $smtp_config['smtp_host'],
				        'smtp_port' => $smtp_config['smtp_port'],
				        'smtp_user' => $smtp_config['smtp_user'],
				        'smtp_pass' => $smtp_config['smtp_pwd'],
				        'mailtype' => 'html',
				        'charset'   => 'iso-8859-1'
				    	);

	 	$this->load->library('email', $config);
		$this->email->set_newline("\r\n");
		 
		$mail = $this->email;

		$query = $this->dp_reset_pwd->get_email_data($user);
		foreach ($query as $row) {
			sleep(2);
			$mail->from($smtp_config['sender_address'],$email_sender_name);
			$mail->to($row->to_email_address); 
			//$mail->cc('another@another-example.com'); 
			//$mail->bcc('them@their-example.com'); 
			
			$mail->subject($row->email_subject);
			$mail->message($row->email_body_content);	
			
			$mail_success = $mail->send();

			if ($mail_success){
				$this->dp_reset_pwd->update_success_mail_job($row->Id);
			}else{
				$mail->print_debugger();
			}
		}
		return NULL;
	}

	function index()
	{
		$datapost['process_reset'] = isset($_POST['reset']) ? TRUE : FALSE;
		$datapost['email'] = isset($_POST['email']) ? $this->security->xss_clean($_POST['email']) : NULL;

		$data['title'] = $this->content->content_title();

		if ($datapost['process_reset'] && $datapost['email'] != NULL){
			if ($this->verify_email_to_app($datapost)){
				$this->process_reset_request($datapost);
				$data['content'] = "Instruksi untuk mengatur ulang password telah dikirim ke email anda.<br>Silakan anda cek email anda ikutin langkah-langkah dalam instruksi tersebut.<br><br>The instructions to reset your password has been sent to your email.<br>Please check your inbox and follow the instructions.<br>".anchor(site_url(),'Login',NULL);
			}else{
				$data['content'] = "Email anda, <strong>".$datapost['email']."</strong>, tidak terdaftar dalam aplikasi ini.<br>Masukkan alamat email yang sesuai.<br><br>Your email, <strong>".$datapost['email']."</strong>, is not associated with this application.<br>Please input a valid email.<br>".anchor(site_url().'reset_pwd','Masukkan ulang alamat email untuk reset / Re-entry email to reset',NULL);
			}
		}else{
			$data['content'] = $this->content->load_content($this->control_name);	
		}
		
		$this->load->view($this->view_name, $data);
	}
}
