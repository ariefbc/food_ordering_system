<?php

class Data_process_rpt_req_list extends CI_Model {
	
	private $tablename = 'cmbr_rpt_delegatagree';
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
	}

	function get_data_pending_approvers() {
		$tmp = array();

		$this->db->select();
		$this->db->from('v_pending_approvers');
		$this->db->order_by('pending_approver_fullname','asc');
		$query = $this->db->get()->result();
		
		if ($query) {
			foreach ($query as $row) {
				if (!array_key_exists($row->Id, $tmp)) {
					$tmp[$row->Id] = array($row->pending_approver_fullname);
				} else {
					array_push($tmp[$row->Id],$row->pending_approver_fullname);
				}
			}
		}

		return $tmp;
	}
	
	function get_data_request($create_date_from = NULL, $create_date_until = NULL) {

		$data_session = $this->session->userdata('logged_in');
		
		$create_date_from = $this->datetime->convert_short_date_format_to_mysql($create_date_from);
		$create_date_until = $this->datetime->convert_short_date_format_to_mysql($create_date_until);

		$this->db->select('v_rpt_cashadvance_request_list.*');
		$this->db->from('v_rpt_cashadvance_request_list');
		$this->db->order_by('createdate','asc');
		$this->db->where("createdate between '".$create_date_from."' and '".$create_date_until."'",NULL);

		$query = $this->db->get()->result();

		return $query;
	}
}