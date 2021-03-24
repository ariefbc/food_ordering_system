<?php

class Data_process_ws extends CI_Model {
	
	function __construct() {
		parent::__construct();
		$this->load->library('app_initializer','','app_init');
	}

	function get_data($full_table_name,$data_date,$data_type){
		if ($data_type == 'core') {
			$current_conn = $this->db;
			$current_db = $current_conn->database;
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->query('use '.$applat_db);
		}

		if ($data_type == 'app') {
			$current_conn = $this->db;
			$current_db = $current_conn->database;
		}

		$this->db->select();
		
		if ($full_table_name == 'info_schema') {
			$this->db->from('information_schema.COLUMNS');
			if ($data_type == 'core') {
				$this->db->where('TABLE_SCHEMA', $applat_db);
			}
			if ($data_type == 'app') {
				$this->db->where('TABLE_SCHEMA', $current_db);
			}
		} else {
			$this->db->from($full_table_name);
			$this->db->where("(createdate > '".$data_date."' or updatedate > '".$data_date."' or deletedate > '".$data_date."')", NULL);
			$this->db->order_by($full_table_name.'.Id','ASC');
		}
		
		$tmp = $this->db->get()->result();

		if ($data_type == 'core') {
			$this->db->query('use '.$current_db);
		}

		return $tmp;
	}
}