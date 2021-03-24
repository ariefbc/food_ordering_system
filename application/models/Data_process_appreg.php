<?php

class Data_process_appreg extends CI_Model {
	
	private $tablename = 'refapps';
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
	}
	
	function count_rows(){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('refapps.app_title,trnlogdata.data_trans_type,refusers.fullname,trnlogdata.ip_address,trnlogdata.data_changes,trnlogdata.createdate');
		$this->db->from($applat_db.'.trnlogdata');
		$this->db->join($applat_db.'.refapps', $applat_db.'.trnlogdata.app_id = '.$applat_db.'.refapps.Id');
		$this->db->join($applat_db.'.refusers', $applat_db.'.trnlogdata.username = '.$applat_db.'.refusers.username');
		$this->db->order_by('trnlogdata.createdate','DESC');

		$tmp = count($this->db->get()->result_array());

		return $tmp;
	}
	
	function get_users(){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('fullname');
		$this->db->from($applat_db.'.refusers');
		$this->db->order_by('fullname','ASC');

		$tmp = $this->db->get()->result();

		return $tmp;
	}
	
	function get_app_title(){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('app_title');
		$this->db->from($applat_db.'.refapps');
		$this->db->order_by('app_title','ASC');

		$tmp = $this->db->get()->result();

		return $tmp;
	}
	
	function get_log_type(){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->distinct();
		$this->db->select('trnlogdata.data_trans_type');
		$this->db->from($applat_db.'.trnlogdata');
		$this->db->join($applat_db.'.refapps', $applat_db.'.trnlogdata.app_id = '.$applat_db.'.refapps.Id');
		$this->db->order_by($applat_db.'.trnlogdata.data_trans_type','ASC');

		$tmp = $this->db->get()->result();

		return $tmp;
	}

	function load_data($limit,$offset){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('Id,app_code,app_name,app_title,app_version,table_prefix,is_locklogin_active');
		$this->db->from($applat_db.'.refapps');
		$this->db->where('isdelete',0);
		$this->db->order_by('app_title','ASC');

		$tmp = $this->db->get()->result();

		return $tmp;
	}

	function update_log($log){
		$data = array(
		        'app_id' => $log['app_id'],
		        'data_trans_type' => $log['data_trans_type'],
		        'username' => $log['username'],
		        'ip_address' => $log['ip_address'],
		        'data_changes' => $log['data_changes'],
		        'createby' => 'sys',
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->insert($applat_db.'.trnlogdata', $data);	

	}
	
	function check_is_appcode_exist($app_code){
			$exist = FALSE;
			
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('app_code');
			$this->db->from($applat_db.".".$this->tablename);
			$this->db->where('isdelete',0);
			$this->db->where('app_code',$app_code);
			$query = $this->db->get();

			if($query -> num_rows() >= 1) $exist = TRUE;
		
			return $exist;
	}

	function get_data_app($id){
		$tmp['id'] = $id;
		$tmp['app_code'] = '';
		$tmp['app_name'] = '';
		$tmp['app_title'] = '';
		$tmp['app_version'] = '';
		$tmp['table_prefix'] = '';
		$tmp['is_locklogin_active'] = '';
		$tmp['trial_attempt'] = '';
		$tmp['wait_next_login_minute'] = '';
		$tmp['email_sender_name'] = '';
		$tmp['is_strong_password_active'] = 0;
			
		if ($id != ''){
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('Id,app_code,app_name,app_title,app_version,table_prefix,is_locklogin_active,trial_attempt,wait_next_login_minute,email_sender_name,is_strong_password_active');
			$this->db->from($applat_db.".".$this->tablename);
			$this->db->where('app_code',$id);
			$query = $this->db->get()->result();	

			$tmp['id'] = $query[0]->Id;
			$tmp['app_code'] = $query[0]->app_code;
			$tmp['app_name'] = $query[0]->app_name;
			$tmp['app_title'] = $query[0]->app_title;
			$tmp['app_version'] = $query[0]->app_version;
			$tmp['table_prefix'] = $query[0]->table_prefix;
			$tmp['is_locklogin_active'] = $query[0]->is_locklogin_active;
			$tmp['trial_attempt'] = $query[0]->trial_attempt;
			$tmp['wait_next_login_minute'] = $query[0]->wait_next_login_minute;
			$tmp['email_sender_name'] = $query[0]->email_sender_name;
			$tmp['is_strong_password_active'] = ($query[0]->is_strong_password_active != "") ? $query[0]->is_strong_password_active : 0;
		}
		
		return $tmp;
	}
	
	function get_data_prior_change($id){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('app_code,app_name,app_title,app_version,table_prefix,is_locklogin_active,trial_attempt,wait_next_login_minute');
		$this->db->where('id', $id);

		$tmp = $this->db->get($applat_db.".".$this->tablename)->result()[0];

		return $tmp;
	}

	function delete_app($user,$id){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$data = array(
		'isdelete' =>1,
        'deleteby' => $user,
        'deletedate' => $this->datetime->get_current_datetime()
		);
		
		$this->db->where('Id', $id);
		$this->db->update($applat_db.'.'.$this->tablename, $data);

		$log['app_id'] = 1;
		$log['data_trans_type'] = 'DATA REMOVE';
		$log['username'] = $user;
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $this->tablename." zzz Id: ".$id;
		$this->update_log($log);	
	}
	
	function update_edit_app($user,$datapost,$id){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$data_before = $this->get_data_prior_change($id);
		
		$data = array(
        'app_code' => $datapost['app_code'],
		'app_name' => $datapost['app_name'],
		'app_title' => $datapost['app_title'],
		'app_version' => $datapost['app_version'],
		'table_prefix' => $datapost['table_prefix'],
		'is_locklogin_active' => $datapost['is_locklogin_active'],
		'trial_attempt' => $datapost['trial_attempt'],
		'wait_next_login_minute' => $datapost['wait_next_login_minute'],
        'updateby' => $user,
        'updatedate' => $this->datetime->get_current_datetime()
		);
		
		$this->db->where('Id', $id);
		$this->db->update($applat_db.'.'.$this->tablename, $data);

		$log['app_id'] = 1;
		$log['data_trans_type'] = 'DATA CHANGES';
		$log['username'] = $user;
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $this->tablename." zzz app_code: ".$data_before->app_code." => ".$datapost['app_code']." zzz app_name: ".$data_before->app_name." => ".$datapost['app_name'];
		$log['data_changes'].= " zzz app_title: ".$data_before->app_title." => ".$datapost['app_title']." zzz app_version: ".$data_before->app_version." => ".$datapost['app_version']." zzz table_prefix: ".$data_before->table_prefix." => ".$datapost['table_prefix'];
		$log['data_changes'].= " zzz is_locklogin_active: ".$data_before->is_locklogin_active." => ".$datapost['is_locklogin_active']." zzz trial_attempt: ".$data_before->trial_attempt." => ".$datapost['trial_attempt']." zzz wait_next_login_minute: ".$data_before->wait_next_login_minute." => ".$datapost['wait_next_login_minute'];
		
		$this->update_log($log);
	}	
	
	function new_app($user,$datapost){
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$data = array(
		        'app_code' => $datapost['app_code'],
				'app_name' => $datapost['app_name'],
				'app_title' => $datapost['app_title'],
				'app_version' => $datapost['app_version'],
				'table_prefix' => $datapost['table_prefix'],
				'is_locklogin_active' => $datapost['is_locklogin_active'],
				'trial_attempt' => $datapost['trial_attempt'],
				'wait_next_login_minute' => $datapost['wait_next_login_minute'],
		        'createby' => $user,
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);

		$this->db->insert($applat_db.'.'.$this->tablename, $data);

		$log['app_id'] = 1;
		$log['data_trans_type'] = 'DATA ENTRY';
		$log['username'] = $user;
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $this->tablename." zzz app_code: ".$datapost['app_code']." zzz app_name: ".$datapost['app_name'];
		$log['data_changes'].= " zzz app_title: ".$datapost['app_title']." zzz app_version: ".$datapost['app_version']." zzz table_prefix: ".$datapost['table_prefix'];
		$log['data_changes'].= " zzz is_locklogin_active: ".$datapost['is_locklogin_active']." zzz trial_attempt: ".$datapost['trial_attempt']." zzz wait_next_login_minute: ".$datapost['wait_next_login_minute'];
		
		$this->update_log($log);
	}
}
