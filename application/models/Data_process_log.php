<?php

class Data_process_log extends CI_Model {
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('app_initializer','','app_init');
	}
	
	function count_rows(){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('refapps.app_title,trnlogdata.data_trans_type,refusers.fullname,trnlogdata.ip_address,trnlogdata.data_changes,trnlogdata.createdate');
		$this->db->from('trnlogdata');
		$this->db->join('refapps', 'trnlogdata.app_id = refapps.Id');
		$this->db->join('refusers', 'trnlogdata.username = refusers.username');
		$this->db->order_by('trnlogdata.createdate','DESC');

		$tmp = count($this->db->get()->result_array());

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function get_users(){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('fullname');
		$this->db->from('refusers');
		$this->db->order_by('fullname','ASC');

		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function get_app_title(){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('app_title');
		$this->db->from('refapps');
		$this->db->order_by('app_title','ASC');

		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function get_log_type(){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->distinct();
		$this->db->select('trnlogdata.data_trans_type');
		$this->db->from('trnlogdata');
		$this->db->join('refapps', 'trnlogdata.app_id = refapps.Id');
		$this->db->order_by('trnlogdata.data_trans_type','ASC');

		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function load_data($datapost,$limit,$offset){

		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('refapps.app_title,trnlogdata.data_trans_type,refusers.fullname,trnlogdata.ip_address,trnlogdata.data_changes,trnlogdata.createdate');
		$this->db->from('trnlogdata');
		$this->db->join('refapps', 'trnlogdata.app_id = refapps.Id');
		$this->db->join('refusers', 'trnlogdata.username = refusers.username');
		$this->db->order_by('trnlogdata.createdate','DESC');
		$this->db->limit($limit,$offset);
		if ($datapost['type'] != '' && $datapost['type'] != '0') $this->db->where('trnlogdata.data_trans_type',$datapost['type']);
		if ($datapost['application'] != '' && $datapost['application'] != '0') $this->db->where('refapps.app_title',$datapost['application']);
		if ($datapost['user'] != '' && $datapost['user'] != '0') $this->db->where('refusers.fullname',$datapost['user']);
		if ($datapost['ip'] != '') $this->db->where('trnlogdata.ip_address',$datapost['ip']);
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function update_log_login($user='',$attemp_status){
		$datasession = $this->session->userdata('logged_in');
		$app_id = $datasession['app_id'];
		
		$data = array(
		        'app_id' => $app_id,
		        'data_trans_type' => 'LOGIN ATTEMPT',
		        'username' => $user,
		        'ip_address' => $this->input->ip_address(),
		        'data_changes' => $attemp_status,
		        'createby' => 'sys',
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);

		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->insert('trnlogdata', $data);

		$this->db->query('use '.$current_db);
	}
	
	function update_log_login_fail($user){
		$datasession = $this->session->userdata('logged_in');
		$app_id = $datasession['app_id'];
		
		$data = array(
		        'app_id' => $app_id,
		        'username' => $user,
		        'ip_address' => $this->input->ip_address(),
		        'last_login_fail' => date("Y/m/d h:i:s a"),
		        'createby' => 'sys',
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);

		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->insert('trnloginfail', $data);

		$this->db->query('use '.$current_db);
	}
}