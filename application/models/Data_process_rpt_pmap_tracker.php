<?php

class Data_process_rpt_pmap_tracker extends CI_Model {
	
	private $tablename = 'cmbr_rpt_delegatagree';
	
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
	}

	/*function get_data_pending_approvers() {
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
	}*/
	
	function get_last_submission_date($data_id) {
		$tmp = array();

		$this->db->select('v_pmap_log_last_submit.*');
		$this->db->from('v_pmap_log_last_submit');
		$this->db->where("data_id in (".$data_id.")",NULL);

		$query = $this->db->get()->result();

		foreach ($query as $row) {
			$tmp[$row->data_id] = $row->last_submit_date;
		}

		return $tmp;
	}

	function get_last_request_for_revision_date($data_id) {
		$tmp = array();

		$this->db->select('v_pmap_log_last_request_revision.*');
		$this->db->from('v_pmap_log_last_request_revision');
		$this->db->where("data_id in (".$data_id.")",NULL);

		$query = $this->db->get()->result();

		foreach ($query as $row) {
			$tmp[$row->data_id] = $row->last_request_for_revision_date;
		}

		return $tmp;
	}

	function get_processed_request_date($data_id) {
		$tmp = array();

		$this->db->select();
		$this->db->from("v_pmap_log_approval");
		$this->db->where("status in ('Approve','Reject')",NULL);
		$this->db->where("data_id in (".$data_id.")",NULL);
		$this->db->order_by("data_id","asc");
		$this->db->order_by("createdate","asc");
		
		$query = $this->db->get()->result();

		foreach ($query as $row) {
			if (!array_key_exists($row->data_id, $tmp)) {
				$tmp[$row->data_id] = array();
			}
			array_push($tmp[$row->data_id], array("createdate" => $row->createdate, "usergroups" => $row->usergroups));
		}

		return $tmp;
	}

	function get_data_request($submit_date_from = NULL, $submit_date_until = NULL) {

		$data_session = $this->session->userdata('logged_in');
		
		$submit_date_from = $this->datetime->convert_short_date_format_to_mysql($submit_date_from);
		$submit_date_until = $this->datetime->convert_short_date_format_to_mysql($submit_date_until);

		$this->db->select('v_rpt_pmap_tracking.*');
		$this->db->from('v_rpt_pmap_tracking');
		$this->db->order_by('submit_date','asc');
		$this->db->where("submit_date between '".$submit_date_from."' and '".$submit_date_until."'",NULL);

		$query = $this->db->get()->result();

		return $query;
	}
}