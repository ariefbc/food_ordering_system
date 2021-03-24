<?php

class Data_process_reset_pwd extends CI_Model {
	
	private $tablename = 'cmbr_rpt_delegatagree';
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('app_initializer','','app_init');
	}

	function verify_reset_code($reset_code){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('Id');
		$this->db->from($applat_db.'.trnresetpwd');
		$this->db->where('reset_code',$reset_code);
		$this->db->where('isdelete',0);
		$this->db->limit(1);
		
		$query = $this->db->get()->result();

		if (count($query) == 1){
			return TRUE;
		}else{
			return FALSE;
		}
	}	

	function verify_email($email,$app_id){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('v_menu.username,v_menu.fullname,email_address');
		$this->db->from($applat_db.'.refnoncoreusers');
		$this->db->join($applat_db.'.v_menu',$applat_db.'.v_menu.username = '.$applat_db.'.refnoncoreusers.username');
		$this->db->where('email_address',$email);
		$this->db->where('app_id',$app_id);
		$this->db->limit(1);
		
		$query = $this->db->get()->result();

		return $query;
	}

	function clear_request($datapost){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('requestby');
		$this->db->from($applat_db.'.trnresetpwd');
		$this->db->where('reset_code',$datapost['reset_code']);
		
		$query = $this->db->get()->result();

		$user = $query[0]->requestby;

		$data = array(
		'isdelete' =>1,
        'deleteby' => $user,
        'resetdate' => $this->datetime->get_current_datetime(),
        'deletedate' => $this->datetime->get_current_datetime()
		);
		
		$this->db->where('requestby', $user);
		$this->db->where('isdelete',0);
		$this->db->update($applat_db.'.trnresetpwd', $data);

		return $user;
	}

	function update_success_mail_job($id){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$data = array();
		$data = array(
		        'process_sent_date' => $this->datetime->get_current_datetime()
		);

		$this->db->where('Id', $id);
		$this->db->update($applat_db.'.refmailmanjobs', $data);

		return NULL;
	}

	function get_email_data($user){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('refmailmanjobs.Id,to_email_address,email_body_content,email_subject');
		$this->db->from($applat_db.'.refmailmanjobs');
		$this->db->where('isdelete',0);
		$this->db->where('username',$user);
		$this->db->where('email_subject',"Request for Reset Password");
		$this->db->where('process_sent_date is null',NULL);
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}

	function get_data_smtp(){
		
		$tmp['smtp_host'] = '';
		$tmp['smtp_port'] = '';
		$tmp['smtp_user'] = '';
		$tmp['smtp_pwd'] = '';
		$tmp['sender_address'] = '';
		$tmp['is_enable'] = 0;

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('smtp_host,smtp_port,smtp_user,smtp_pwd,sender_address,is_enable');
		$this->db->from($applat_db.'.refmailman');
		$this->db->limit(1);
		$query = $this->db->get()->result();

		if (count($query) == 1){
			$tmp['smtp_host'] = $query[0]->smtp_host;
			$tmp['smtp_port'] = $query[0]->smtp_port;
			$tmp['smtp_user'] = $query[0]->smtp_user;
			$tmp['smtp_pwd'] = $query[0]->smtp_pwd;
			$tmp['sender_address'] = $query[0]->sender_address;
			$tmp['is_enable'] = $query[0]->is_enable;
		}
		return $tmp;
	}

	function insert_reset_request($user,$fullname,$email_address,$app_code){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		
		$data = array(
		'isdelete' =>1,
        'deleteby' => $user,
        'deletedate' => $this->datetime->get_current_datetime()
		);
		
		$this->db->where('requestby', $user);
		$this->db->where('isdelete',0);
		$this->db->update($applat_db.'.trnresetpwd', $data);

		$current_date = $this->datetime->get_current_datetime();
		$hash_code = md5($current_date);

		$data = array(
		        'reset_code' => $hash_code,
				'requestby' => $user,
				'requestdate' => $current_date,
				'createby' => $user,
        		'createdate' => $current_date,
        		'isdelete' => 0
		);

		$this->db->insert($applat_db.'.trnresetpwd', $data);

		$this->db->select('app_name,app_version,email_sender_name');
		$this->db->from($applat_db.'.refapps');
		$this->db->where('app_code', $app_code);
		
		$query_app = $this->db->get()->result();

		$body_mail_content = "Dear ".$fullname.",<br><br><br>
						You have requested to reset your password for the following:
						<br><br>Application Name : ".$query_app[0]->app_name." ver.".$query_app[0]->app_version."
						<br><br>Requested Date : ".$current_date."
						<br><br>Please kindly click ".anchor(site_url()."reset_pwd/resetting/".$hash_code, "here")." to change your password. Thank you.<br><br><br>Regards,<br><br>".$query_app[0]->email_sender_name."<br><br>*)This email is generated automatically, no need to reply.<br>**)<b>If you did not request for reset password, please ignore this email and login into the application with your current password</b>";

		$data = array();
		$data = array(
		'isdelete' =>1,
        'deleteby' => $user,
        'deletedate' => $current_date
		);
		
		$this->db->where('username', $user);
		$this->db->where('isdelete',0);
		$this->db->where('email_subject',"Request for Reset Password");
		$this->db->update($applat_db.'.refmailmanjobs', $data);

		$data = array();
		$data = array(
        	'username' => $user,
	        'to_email_address' => $email_address,
	        'email_subject' => "Request for Reset Password",
	        'email_body_content' => $body_mail_content,
	        'createby' => $user,
	        'createdate' => $current_date,
	        'isdelete' => 0
		);

		$this->db->insert($applat_db.'.refmailmanjobs', $data);

		return NULL;
	}
}