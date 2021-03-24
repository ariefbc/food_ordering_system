<?php

class Data_process_myprofile extends CI_Model {
	
	private $tablename = 'refnoncoreusers';
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('app_initializer','','app_init');
	}
	
	function update_log($log){
		$current_datetime = $this->datetime->get_current_datetime();
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$data = array(
		        'app_id' => $log['app_id'],
		        'data_trans_type' => $log['data_trans_type'],
		        'username' => $log['username'],
		        'ip_address' => $log['ip_address'],
		        'data_changes' => $log['data_changes'],
		        'createby' => 'sys',
        		'createdate' => $current_datetime,
        		'isdelete' => 0
		);

		$this->db->insert('trnlogdata', $data);	

		$this->db->query('use '.$current_db);
	}
	
	function check_oldpassword($user,$oldpassword){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('username');
		$this->db->where('username', $user);
		$this->db->where('password', md5($oldpassword));

		$tmp = $this->db->get($this->tablename);

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function get_data_prior_change($user){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('language_id,password');
		$this->db->where('username', $user);

		$tmp = $this->db->get($this->tablename)->result()[0];

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function update_language($user,$datapost){
		$data_before = $this->get_data_prior_change($user);
		
		$data = array(
        'language_id' => $datapost['language_id'],
        'updateby' => $user,
        'updatedate' => $this->datetime->get_current_datetime()
		);
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->where('username', $user);
		$this->db->update($this->tablename, $data);

		$this->db->query('use '.$current_db);
		
		$log['app_id'] = 1;
		$log['data_trans_type'] = 'DATA CHANGES';
		$log['username'] = $user;
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $this->tablename." zzz language_id: ".$data_before->language_id." => ".$datapost['language_id'];
		$this->update_log($log);
	}

	function get_language_country() {
		$datasession = array();
		$datasession = $this->session->userdata('logged_in');
		$user = $datasession['username'];

		$current_language_id = $this->get_data_prior_change($user)->language_id;

		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('Id,lang_country');
		$this->db->from('reflanguagecountry');
		$this->db->where('isdelete',0);
		$this->db->order_by('lang_country','asc');

		$tmp['language_list'] = $this->db->get()->result();
		$tmp['selected'] = $current_language_id;

		$this->db->query('use '.$current_db);

		return $tmp;	
	}

	function update_password($user,$datapost) {
		$data_before = $this->get_data_prior_change($user);
		
		$data = array(
        'password' => md5($datapost['password']),
        'is_password_reset' => 0,
        'updateby' => $user,
        'updatedate' => $this->datetime->get_current_datetime()
		);
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->where('username', $user);
		$this->db->update($applat_db.".".$this->tablename, $data);

		$log['app_id'] = 1;
		$log['data_trans_type'] = 'DATA CHANGES';
		$log['username'] = $user;
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $this->tablename." zzz password: ".$data_before->password." => ".md5($datapost['password']);
		$this->update_log($log);
	}
}