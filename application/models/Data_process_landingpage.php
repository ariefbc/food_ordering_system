<?php
class Data_process_landingpage extends CI_Model {
	function __construct() {
		parent::__construct();
	}

	function get_request_data_id($approval_id) {
		$this->db->select("hash_link,approval_page");
		$this->db->where("isdelete", 0);
		$this->db->where("approval_id", $approval_id);
		$this->db->from("epmap_req_material_data");
		
		return $this->db->get()->result();
	}
}