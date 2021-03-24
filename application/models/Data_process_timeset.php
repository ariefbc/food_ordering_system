<?php

class Data_process_timeset extends CI_Model {
	
	private $tablename = 'reftimeset';
	
	function __construct() {
		parent::__construct();
		$this->load->library('app_initializer','','app_init');
	}
	
	function update_log($log){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$data = array(
		        'app_id' => $log['app_id'],
		        'data_trans_type' => $log['data_trans_type'],
		        'username' => $log['username'],
		        'ip_address' => $log['ip_address'],
		        'data_changes' => $log['data_changes'],
		        'createby' => 'sys',
        		'createdate' => date("Y/m/d H:i:s"),
        		'isdelete' => 0
		);

		$this->db->query('use '.$applat_db);

		$this->db->insert('trnlogdata', $data);	

		$this->db->query('use '.$current_db);
	}
	
	
	function get_data_prior_change(){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->query('use '.$applat_db);

		$this->db->select('hour_adjust');
		$tmp = $this->db->get($this->tablename)->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function get_current_datetime(){
		$hour = (count($this->get_data_prior_change()) == 0)? 0 : $this->get_data_prior_change()[0]->hour_adjust;
		$date = new DateTime(date("Y/m/d H:i:s"));
		if ($hour>0){
			$date->add(new DateInterval('PT'.$hour.'H'));
		}else{
			$hour *= -1;
			$date->sub(new DateInterval('PT'.$hour.'H'));
		}
		return $date->format("Y/m/d H:i:s");
	}
	
	function convert_short_date_format_to_mysql($date_string){
		$date_string = explode('-',$date_string);
		$dd = $date_string[0];
		$mm = $date_string[1];
		$yy = $date_string[2];
		if (strtoupper($mm) == 'JAN') {$mm = '1';}
		if (strtoupper($mm) == 'FEB') {$mm = '2';}
		if (strtoupper($mm) == 'MAR') {$mm = '3';}
		if (strtoupper($mm) == 'APR') {$mm = '4';}
		if (strtoupper($mm) == 'MAY') {$mm = '5';}
		if (strtoupper($mm) == 'JUN') {$mm = '6';}
		if (strtoupper($mm) == 'JUL') {$mm = '7';}
		if (strtoupper($mm) == 'AUG') {$mm = '8';}
		if (strtoupper($mm) == 'SEP') {$mm = '9';}
		if (strtoupper($mm) == 'OCT') {$mm = '10';}
		if (strtoupper($mm) == 'NOV') {$mm = '11';}
		if (strtoupper($mm) == 'DEC') {$mm = '12';}
		$date_string = $mm.'/'.$dd.'/'.$yy;
		
		$date = new DateTime(date($date_string));
		return $date->format("Y/m/d H:i:s");
	}
	
	function convert_mysql_date_format_to_short_string($date_string){
		if ($date_string != ''){
			$date = new DateTime(date($date_string));
			$date_string = $date->format("Y/m/d");
			$date_string = explode('/',$date_string);
			$dd = $date_string[2];
			$mm = (int) $date_string[1];
			$yy = $date_string[0];
			if ($mm == '1') {$mm = 'Jan';}
			if ($mm == '2') {$mm= 'Feb';}
			if ($mm == '3') {$mm = 'Mar';}
			if ($mm == '4') {$mm= 'Apr';}
			if ($mm == '5') {$mm = 'May';}
			if ($mm == '6') {$mm= 'Jun';}
			if ($mm == '7') {$mm = 'Jul';}
			if ($mm == '8') {$mm= 'Aug';}
			if ($mm == '9') {$mm = 'Sep';}
			if ($mm == '10') {$mm= 'Oct';}
			if ($mm == '11') {$mm = 'Nov';}
			if ($mm == '12') {$mm= 'Dec';}
			$date_string = $dd.'-'.$mm.'-'.$yy;
		}
		
		return $date_string;
	}
	
	function update_hour($user,$datapost){
		$data_before = $this->get_data_prior_change();

		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		if (count($data_before) >0)
		{
			$data_before = $data_before[0];
			$data = array(
	        'hour_adjust' => $datapost['hour_adjust'],
	        'updateby' => $user,
	        'updatedate' => date("Y/m/d H:i:s")
			);
			
			$this->db->query('use '.$applat_db);

			$this->db->update($this->tablename, $data);

			$this->db->query('use '.$current_db);
			
			$log['app_id'] = 1;
			$log['data_trans_type'] = 'DATA CHANGES';
			$log['username'] = $user;
			$log['ip_address'] = $this->input->ip_address();
			$log['data_changes'] = $this->tablename." zzz hour_adjust: ".$data_before->hour_adjust." => ".$datapost['hour_adjust'];
			$this->update_log($log);
		} else{
			$data = array(
		        'hour_adjust' => $datapost['hour_adjust'],
		        'createby' => $user,
        		'createdate' => date("Y/m/d H:i:s"),
        		'isdelete' => 0
				);
				
				$this->db->query('use '.$applat_db);

				$this->db->insert($this->tablename, $data);

				$this->db->query('use '.$current_db);
				
				$log['app_id'] = 1;
				$log['data_trans_type'] = 'DATA ENTRY';
				$log['username'] = $user;
				$log['ip_address'] = $this->input->ip_address();
				$log['data_changes'] = $this->tablename." zzz hour_adjust: ".$datapost['hour_adjust'];
				$this->update_log($log);
		}
	}
}