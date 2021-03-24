<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Landingpage extends CI_Controller {
	function __construct($approval_id = 0) {
		parent::__construct();
		$this->load->model('data_process_landingpage','dp_landingpage',TRUE);
	}

	function index() {
		if(!$this->session->userdata('logged_in')) {
			redirect("login");
		} else {
			if ($this->session->userdata('landingpage')) {
				$landingpage_data_array = $this->session->userdata('landingpage');
				$this->session->unset_userdata('landingpage');

				redirect("eform/edit/".$landingpage_data_array["approval_page"]."/".$landingpage_data_array["request_id"]);
			} else {
				redirect("home");
			}
		}
	}

	function emailhandling($approval_id = "") {
		if ($approval_id == "") {
			redirect("landingpage");
		} else {
			$query = $this->dp_landingpage->get_request_data_id($approval_id);

			if ($query) {
				$row = $query[0];
				$landingpage_data_array = array('approval_page' => $row->approval_page
					,'request_id' => $row->hash_link);
				$this->session->set_userdata('landingpage', $landingpage_data_array);
			} 

			redirect("landingpage");
		}
	}
}