<?php
Class User extends CI_Model
{
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
	}
		
 function login($username, $password)
 {
 	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this -> db -> select($applat_db.'.refnoncoreusers.id, username, fullname,language_id,usergroup_name, is_password_reset, app_code');
	$this -> db -> from($applat_db.'.refnoncoreusers');
	$this -> db -> join($applat_db.'.refnoncoreusergroups_users',$applat_db.'.refnoncoreusergroups_users.user_id = '.$applat_db.'.refnoncoreusers.Id');
	$this -> db -> join($applat_db.'.refnoncoreusergroups',$applat_db.'.refnoncoreusergroups_users.group_id = '.$applat_db.'.refnoncoreusergroups.Id');
	$this -> db -> join($applat_db.'.refnoncoreusergroups_modules',$applat_db.'.refnoncoreusergroups_modules.group_id = '.$applat_db.'.refnoncoreusergroups.Id');
	$this -> db -> join($applat_db.'.refmodules',$applat_db.'.refnoncoreusergroups_modules.module_id = '.$applat_db.'.refmodules.Id');
	$this -> db -> join($applat_db.'.refapps',$applat_db.'.refmodules.app_id = '.$applat_db.'.refapps.Id');
	$this -> db -> where('username', $username);
	$this -> db -> where('password', md5($password));
	$this -> db -> where($applat_db.'.refnoncoreusers.isdelete', 0);
	$this -> db -> where($applat_db.'.refnoncoreusergroups_users.isdelete', 0);
	$this -> db -> where($applat_db.'.refnoncoreusergroups_modules.isdelete', 0);
	$this -> db -> where($applat_db.'.refmodules.isdelete', 0);
	//$this -> db -> limit(1);
 
   $query = $this->db->get()->result();
   //echo $this -> db -> last_query();exit;
   if ($query) {
     return $query;
   }
   else
   {
     return FALSE;
   }
 }
 
 function check_allow_login($username) {
 	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this -> db -> select('allow_login_after');
   	$this -> db -> from('refnoncoreusers');
   	$this -> db -> where('username', $username);
	$this -> db -> where('isdelete', 0);
   	$this -> db -> limit(1);
 
   $query = $this -> db -> get();

   $this->db->query('use '.$current_db);

   if($query -> num_rows() == 1) {
   		$row = $query->result()[0];
	   $allow = new DateTime($row->allow_login_after);
	   return ($allow == '' ||$allow < new DateTime($this->datetime->get_current_datetime())) ? '' : "Your account is locked.</br>Please Login after ".$allow->format("Y/m/d H:i:s");	
   }
 }
 
 function get_allow_login_attempt() {
 	$attempt = 0;
	
	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this -> db -> select('trial_attempt');
   	$this -> db -> from('refapps');
   	$this -> db -> where('id', 1);
	$this -> db -> where('is_locklogin_active', 1);
   	
    $query = $this -> db -> get();

    $this->db->query('use '.$current_db);

	if($query -> num_rows() == 1) {
		$row = $query->result()[0];
		$attempt = ($row->trial_attempt == '')? 0 : $row->trial_attempt;	
	}
	
	return $attempt;
 }
 
 function get_time_allow_login_interval() {
 	$minute_interval = 0;
	
	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this -> db -> select('wait_next_login_minute');
   	$this -> db -> from('refapps');
   	$this -> db -> where('id', 1);
	$this -> db -> where('is_locklogin_active', 1);
   	
    $query = $this -> db -> get();

    $this->db->query('use '.$current_db);

	if($query -> num_rows() == 1) {
		$row = $query->result()[0];
		$minute_interval = ($row->wait_next_login_minute == '')? 0 : $row->wait_next_login_minute;	
	}
	
	return $minute_interval;
}
 
 function update_set_allow_login($username) {
 	$date = new DateTime($this->datetime->get_current_datetime());
	$date->add(new DateInterval('PT0H'.$this->get_time_allow_login_interval().'M0S'));
	
	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$data = array(
	'allow_login_after' =>$date->format("Y/m/d H:i:s"));
	
	$this->db->where('username', $username);
	$this->db->update('refnoncoreusers', $data);

	$this->db->query('use '.$current_db);
 }
 
 function get_app_id($app_code) {
	$tmp = '';
	
	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

	   $this -> db -> select('Id');
	   $this -> db -> from('refapps');
	   $this -> db -> where('app_code', $app_code);
	   
	   $query = $this->db->get()->result();

	$this->db->query('use '.$current_db);
	   
	   $tmp = $query[0]->Id;
   
	return $tmp;	
}

 function count_login_fail_attempt($username,$app_code) {
 	$app_id = $this->get_app_id($app_code);
 	
 	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this -> db -> select('username');
   	$this -> db -> from('trnloginfail');
   	$this -> db -> where('username', $username);
	$this -> db -> where('app_id', $app_id);
   	
    $query = $this -> db -> get();

    $this->db->query('use '.$current_db);
	
	return $query->num_rows();
 }
 
 function clear_fail_log($username) {
 	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this->db->where('username', $username);
	$this->db->delete('trnloginfail');

	$data = array(
	'isdelete' =>1,
    'deleteby' => $username,
    'deletedate' => $this->datetime->get_current_datetime()
	);
	
	$this->db->query('use '.$applat_db);
		
	$this->db->where('requestby', $username);
	$this->db->where('isdelete',0);
	$this->db->update('trnresetpwd', $data);

	$this->db->query('use '.$current_db);
 }
 
 function check_user($username) {
 	$current_conn = $this->db;
	$current_db = $current_conn->database;
	$app_init = $this->app_init->app_init();
	$applat_db = $app_init['applat_db_name'];
	
	$this->db->query('use '.$applat_db);

 	$this -> db -> select('id, username');
   	$this -> db -> from('refnoncoreusers');
   	$this -> db -> where('username', $username);
   	$this -> db -> where('isdelete', 0);
   	$this -> db -> limit(1);
 
   $query = $this -> db -> get();

   $this->db->query('use '.$current_db);

 	if($query -> num_rows() == 1)
   {
     return TRUE;
   }
   else
   {
     return FALSE;
   }
 }
 
	function check_allow_access_page($datasession,$menu_name) {
	 	$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		
	   	$this -> db -> select('username');
	   	$this -> db -> from($applat_db.'.v_menu');
	   	$this -> db -> where('username', $datasession['username']);
	   	$this -> db -> where('app_id', $datasession['app_id']);
	   	$this -> db -> where('url', $menu_name);
	   	$this -> db -> limit(1);
	 
	   	$query = $this -> db -> get();

		if($query) {
		  	return TRUE;
	  	} else {
	    	return FALSE;
	  	}
	}
}
