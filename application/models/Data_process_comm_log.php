<?php

class Data_process_comm_log extends CI_Model {
	
	function __construct() {
		parent::__construct();
		$this->load->library('app_initializer','','app_init');
	}
	
	function get_reference_number($data_menu_id,$data_id) {
		
		if ($data_id != 0 || $data_id != "") {
			
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$query = array();
			$this->db->select('full_table_name');
			$this->db->from($applat_db.'.refmenu');
			$this->db->where('Id',$data_menu_id);
			
			$query = $this->db->get()->result();

			$full_table_name = $query[0]->full_table_name;

			$data_id = $this->get_data_id_from_hash_link($data_id,$full_table_name);

			$query = array();
			$this->db->select('request_reference_number');
			$this->db->from($full_table_name);
			$this->db->where('Id',$data_id);
			
			$query = $this->db->get()->result();

			if (count($query) >= 1) {
				return $query[0]->request_reference_number;
			} else {
				return "";
			}

		} else {
			return "";
		}
	}

	function get_data_id_from_hash_link($hash_link,$full_table_name) {
		$this->db->select('Id');
		$this->db->from($full_table_name);
		$this->db->where('hash_link',$hash_link);
		
		$query_check_hash_link =  $this->db->get()->result();

		if ($query_check_hash_link) {
			return $query_check_hash_link[0]->Id;
		} else {
			return 0;
		}
	}

	function load_data($data_menu_id,$data_id){
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('full_table_name');
		$this->db->from($applat_db.'.refmenu');
		$this->db->where('Id',$data_menu_id);
		
		$query = $this->db->get()->result();

		$data_id = $this->get_data_id_from_hash_link($data_id,$query[0]->full_table_name);
		
		$this->db->select('status,fullname,comm_msg,trnlogworkflow.createdate');
		$this->db->from($applat_db.'.trnlogworkflow');
		$this->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnlogworkflow.createby');
		$this->db->where('menu_id',$data_menu_id);
		$this->db->where('data_id',$data_id);
		$this->db->where('('.$applat_db.'.trnlogworkflow.isdelete is null or '.$applat_db.'.trnlogworkflow.isdelete = 0)',NULL);
		$this->db->order_by('createdate','DESC');
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}

}