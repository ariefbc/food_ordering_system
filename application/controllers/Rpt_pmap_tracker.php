<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Rpt_pmap_tracker extends CI_Controller {
	private $view_name = 'Vw_rpt_pmap_tracker';
	private $control_name = 'rpt_pmap_tracker';
	private $content_name = 'content/content_rpt_pmap_tracker';

	private $data_append_rows = "";
	private $no = 0;

	private $pending_approvers = array();

	private $data_id = "";
	private $last_request_for_revision_date = array();
	private $last_submission_date = array();
	private $get_processed_request_date = array();

	private $get_first_approval_date = array();
	private $get_marketing_approval_date = array();


	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_rpt_pmap_tracker','',TRUE);
		$this->load->model('data_process_translate','',TRUE);
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('app_initializer','','app_init');
	}
	
	function get_tracking_code($request_reference_number) {
		$request_reference_number_array = explode("/", $request_reference_number);
		$tracking_code_array = explode("-", $request_reference_number_array[1]);
		$tracking_code = $tracking_code_array[1];

		return $tracking_code;
	}

	function get_submission_code($request_reference_number) {
		$request_reference_number_array = explode("/", $request_reference_number);
		$submission_code = $request_reference_number_array[0]."/".$request_reference_number_array[1]."/".$request_reference_number_array[2]."/".$request_reference_number_array[3];
		
		return $submission_code;
	}

	function get_requisition_type_product_info($activity_type, $with_product_info) {
		$get_requisition_type_product_info['opioid'] = array("activity_type" => "Opioid", "with_product_info" => "N/A");
		$get_requisition_type_product_info['corporate'] = array("activity_type" => "Corporate", "with_product_info" => $with_product_info);
		$get_requisition_type_product_info['training_internal'] = array("activity_type" => "Internal Training", "with_product_info" => $with_product_info);
		$get_requisition_type_product_info['product_name'] = array("activity_type" => "Product Name Only", "with_product_info" => "N/A");
		$get_requisition_type_product_info['key_promo_aid'] = array("activity_type" => "Key Promotional Aid", "with_product_info" => "N/A");
		$get_requisition_type_product_info['speaker_brief'] = array("activity_type" => "Speaker Brief", "with_product_info" => "N/A");
		$get_requisition_type_product_info['storemedia_pos'] = array("activity_type" => "In Store Media/POS", "with_product_info" => "N/A");
		$get_requisition_type_product_info['social_media'] = array("activity_type" => "Social Media", "with_product_info" => "N/A");
		$get_requisition_type_product_info['ecommerce'] = array("activity_type" => "e-Commerce", "with_product_info" => "N/A");
		$get_requisition_type_product_info['gimmicks'] = array("activity_type" => "Gimmicks", "with_product_info" => "N/A");
		$get_requisition_type_product_info['other'] = array("activity_type" => "Other", "with_product_info" => $with_product_info);

		return $get_requisition_type_product_info[$activity_type];
	}

	function append_data_rows($xls,$data_row) {

		$datasession = $this->session->userdata('logged_in');
		$language = $datasession['language'];

		$this->no++;
		/*
			<th rowspan='2'>No.</th>
			<th rowspan='2'>Tracking Code</th>
			<th rowspan='2'>Submission Code</th>
			<th rowspan='2'>Brand</th>
			<th rowspan='2'>Material Name</th>
			<th rowspan='2'>Material Type</th>
			<th rowspan='2'>Requisition Type</th>
			<th rowspan='2'>Wih Product-related<br>Information</th>
			<th rowspan='2'>Material Proponent</th>
			<th rowspan='2'>Submission Date</th>
			<th rowspan='2'>Approval Date</th>
			<th rowspan='2'>Total Review<br>Days</th>
			<th rowspan='2'>Approval Code</th>
			<th rowspan='2'>Status</th>
			<th rowspan='2'>Remarks CAMBER</th>
			<th colspan='2'>MSL First Review</th>
			<th rowspan='2'>MSL First Review<br>Days</th>
			<th colspan='2'>Marketing</th>
			<th rowspan='2'>Marketing Review<br>Days</th>
			<th colspan='2'>Regulatory Manager</th>
			<th rowspan='2'>Regulatory Manager<br>Review Days</th>
			<th colspan='2'>Regulatory Head</th>
			<th rowspan='2'>Regulatory Head<br>Review Days</th>
			<th colspan='2'>Legal</th>
			<th rowspan='2'>Legal Review<br>Days</th>
			<th rowspan='2'>Last Date<br>for Request Revision</th>
			<th rowspan='2'>Last Submission Date*</th>
			<th colspan='2'>MSL Final Review</th>
			<th rowspan='2'>MSL Final<br>Review Days</th>
			<th colspan='2'>Medical Affairs Head</th>
			<th rowspan='2'>Medical Affairs Head<br>Review Days</th>
			<th colspan='2'>GM</th>
			<th rowspan='2'>GM Review<br>Days</th>
		*/
		
		$col[$this->no-1][0] = $this->no; 
		$col[$this->no-1][1] = $this->get_tracking_code($data_row->request_reference_number);
		$col[$this->no-1][2] = $this->get_submission_code($data_row->request_reference_number);
		//$col[$this->no-1][2] = ($data_row->createdate != "") ? $this->datetime->convert_mysql_date_format_to_short_string($data_row->createdate) : "";
		$col[$this->no-1][3] = $data_row->product_name;
		$col[$this->no-1][4] = $data_row->material_name;
		$col[$this->no-1][5] = $data_row->material_type;

		$get_requisition_type_product_info = $this->get_requisition_type_product_info($data_row->activity_type,$data_row->contain_product_claim_related_message);
		$col[$this->no-1][6] = $get_requisition_type_product_info['activity_type'];
		$col[$this->no-1][7] = $get_requisition_type_product_info['with_product_info'];

		$col[$this->no-1][8] = $data_row->material_proponent;
		$col[$this->no-1][9] = $this->datetime->convert_mysql_date_format_to_short_string($data_row->submit_date);
		$col[$this->no-1][10] = $this->datetime->convert_mysql_date_format_to_short_string($data_row->full_approve_date);

		$total_review_days = "";
		$date1 = date_create($this->datetime->convert_mysql_date_format_to_short_string($data_row->submit_date));
		$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($data_row->full_approve_date));
		$diff = date_diff($date1,$date2);
		$total_review_days = (int) $diff->format("%R%a");
		$col[$this->no-1][11] = $total_review_days;

		$col[$this->no-1][12] = $data_row->request_reference_number;
		$col[$this->no-1][13] = $data_row->status;
		$col[$this->no-1][14] = ""; #Remarks CAMBER

		#initialize N/A for each reviwer
		$last_approval_date = "";

		$in_msl_first_review = "N/A";
		$out_msl_first_review = "N/A";
		$duration_msl_first_review = "N/A";

		$in_marketing_review = "N/A";
		$out_marketing_review = "N/A";
		$duration_marketing_review = "N/A";

		$in_regulatory_manager_review = "N/A";
		$out_regulatory_manager_review = "N/A";
		$duration_regulatory_manager_review = "N/A";

		$in_regulatory_head_review = "N/A";
		$out_regulatory_head_review = "N/A";
		$duration_regulatory_head_review = "N/A";

		$in_legal_review = "N/A";
		$out_legal_review = "N/A";
		$duration_legal_review = "N/A";

		$in_msl_final_review = "N/A";
		$out_msl_final_review = "N/A";
		$duration_msl_final_review = "N/A";

		$in_med_affairs_head_review = "N/A";
		$out_med_affairs_head_review = "N/A";
		$duration_med_affairs_head_review = "N/A";

		$in_gm_review = "N/A";
		$out_gm_review = "N/A";
		$duration_gm_review = "N/A";

		$in_bpom = "N/A";
		$out_bpom = "N/A";
		$duration_bpom = "N/A";
		$status_bpom = "N/A";

		$in_communication_review = "N/A";
		$out_communication_review = "N/A";
		$duration_communication_review = "N/A";
		$status_regional = "N/A";
		
		if (array_key_exists($data_row->Id, $this->get_processed_request_date)) {
			$msl_first_review = TRUE;
			$last_approval_date = "";
			//print_r($this->get_processed_request_date[$data_row->Id]);exit;
			foreach ($this->get_processed_request_date[$data_row->Id] as $row) {
				switch ($row['usergroups']) {
						case 'Medical Scientific Liaison user group':
							if ($data_row->activity_type == 'speaker_brief') {
								$msl_first_review = FALSE;
							}

							if ($msl_first_review) {
								$msl_first_review = FALSE;

								$in_msl_first_review = $this->datetime->convert_mysql_date_format_to_short_string($data_row->submit_date);
								$out_msl_first_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
								$last_approval_date = $out_msl_first_review;

								$date1 = date_create($this->datetime->convert_mysql_date_format_to_short_string($data_row->submit_date));
								$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
								$diff = date_diff($date1,$date2);
								$duration_msl_first_review = (int) $diff->format("%R%a");
							} else {
								if ($data_row->activity_type == 'speaker_brief') {
									$in_msl_final_review = ($data_row->submit_date == $this->last_submission_date[$data_row->Id]) ? $this->datetime->convert_mysql_date_format_to_short_string($data_row->submit_date) : $this->datetime->convert_mysql_date_format_to_short_string($this->last_submission_date[$data_row->Id]);
								} else {
									$in_msl_final_review = ($data_row->submit_date == $this->last_submission_date[$data_row->Id]) ? $last_approval_date : $this->datetime->convert_mysql_date_format_to_short_string($this->last_submission_date[$data_row->Id]);
								}
								
								$out_msl_final_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
								$last_approval_date = $out_msl_final_review;

								$date1 = date_create($in_msl_final_review);
								$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
								$diff = date_diff($date1,$date2);
								$duration_msl_final_review = (int) $diff->format("%R%a");
							}
							break;
						case 'epmap - Marketing Reviewer':
							$in_marketing_review = $last_approval_date;
							$out_marketing_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_marketing_review;

							$date1 = date_create($in_marketing_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_marketing_review = (int) $diff->format("%R%a");
							break;
						case 'epmap - Regulatory Affairs Manager':
							$in_regulatory_manager_review = $last_approval_date;
							$out_regulatory_manager_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_regulatory_manager_review;

							$date1 = date_create($in_regulatory_manager_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_regulatory_manager_review = (int) $diff->format("%R%a");
							break;
						case 'epmap - Regulatory Affairs Head':
							$in_regulatory_head_review = $last_approval_date;
							$out_regulatory_head_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_regulatory_head_review;

							$date1 = date_create($in_regulatory_head_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_regulatory_head_review = (int) $diff->format("%R%a");
							break;
						case 'Compliance Manager user group':
							$in_legal_review = $last_approval_date;
							$out_legal_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_legal_review;

							$date1 = date_create($in_legal_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_legal_review = (int) $diff->format("%R%a");
							break;
						case 'epmap - Communication Reviewer':
							$in_communication_review = $last_approval_date;
							$out_communication_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_communication_review;

							$date1 = date_create($in_communication_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_communication_review = (int) $diff->format("%R%a");
							break;
						case 'Medical Affairs Manager user group':
							$in_med_affairs_head_review = $last_approval_date;
							$out_med_affairs_head_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_med_affairs_head_review;

							$date1 = date_create($in_med_affairs_head_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_med_affairs_head_review = (int) $diff->format("%R%a");
							break;
						case 'General Manager user group':
							$in_gm_review = $last_approval_date;
							$out_gm_review = $this->datetime->convert_mysql_date_format_to_short_string($row['createdate']);
							$last_approval_date = $out_gm_review;

							$date1 = date_create($in_gm_review);
							$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($row['createdate']));
							$diff = date_diff($date1,$date2);
							$duration_gm_review = (int) $diff->format("%R%a");
							break;
						default:
							# code...
							break;
					}	
			}
		}

		#msl first review in
		$col[$this->no-1][15] = $in_msl_first_review;
		#msl first review out
		$col[$this->no-1][16] = $out_msl_first_review;
		#msl first review days
		$col[$this->no-1][17] = $duration_msl_first_review;

		#marketing review in
		$col[$this->no-1][18] = $in_marketing_review;
		#marketing review out
		$col[$this->no-1][19] = $out_marketing_review;
		#marketing review days
		$col[$this->no-1][20] = $duration_marketing_review;

		#regulatory manager review in
		$col[$this->no-1][21] = $in_regulatory_manager_review;
		#regulatory manager review out
		$col[$this->no-1][22] = $out_regulatory_manager_review;
		#regulatory manager review days
		$col[$this->no-1][23] = $duration_regulatory_manager_review;

		#regulatory head review in
		$col[$this->no-1][24] = $in_regulatory_head_review;
		#regulatory head review out
		$col[$this->no-1][25] = $out_regulatory_head_review;
		#regulatory head review days
		$col[$this->no-1][26] = $duration_regulatory_head_review;

		#legal review in
		$col[$this->no-1][27] = $in_legal_review;
		#legal review out
		$col[$this->no-1][28] = $out_legal_review;
		#legal review days
		$col[$this->no-1][29] = $duration_legal_review;

		#Communication In
		$col[$this->no-1][30] = $in_communication_review;
		#Communication Out
		$col[$this->no-1][31] = $out_communication_review;
		#Communication process days
		$col[$this->no-1][32] = $duration_communication_review;

		$col[$this->no-1][33] = (array_key_exists($data_row->Id, $this->last_request_for_revision_date)) ? $this->datetime->convert_mysql_date_format_to_short_string($this->last_request_for_revision_date[$data_row->Id]) : "";

		$col[$this->no-1][34] = (array_key_exists($data_row->Id, $this->last_request_for_revision_date) && array_key_exists($data_row->Id, $this->last_submission_date)) ? $this->datetime->convert_mysql_date_format_to_short_string($this->last_submission_date[$data_row->Id]) : "";

		#msl final review in
		$col[$this->no-1][35] = $in_msl_final_review;
		#msl final review out
		$col[$this->no-1][36] = $out_msl_final_review;
		#msl final review days
		$col[$this->no-1][37] = $duration_msl_final_review;

		#med affairs head review in
		$col[$this->no-1][38] = $in_med_affairs_head_review;
		#med affairs head review out
		$col[$this->no-1][39] = $out_med_affairs_head_review;
		#med affairs head review days
		$col[$this->no-1][40] = $duration_med_affairs_head_review;

		#med affairs head review in
		$col[$this->no-1][41] = $in_gm_review;
		#med affairs head review out
		$col[$this->no-1][42] = $out_gm_review;
		#med affairs head review days
		$col[$this->no-1][43] = $duration_gm_review;

		#BPOM
		if ($data_row->is_bpom_required == 1) {
			$in_bpom = $last_approval_date;
			$status_bpom = $data_row->bpom_process_status;

			if ($data_row->bpom_process_status != "In Process") {
				$out_bpom = $this->datetime->convert_mysql_date_format_to_short_string($data_row->bpom_finish_process_date);

				$date1 = date_create($in_bpom);
				$date2 = date_create($this->datetime->convert_mysql_date_format_to_short_string($data_row->bpom_finish_process_date));
				$diff = date_diff($date1,$date2);
				$duration_bpom = (int) $diff->format("%R%a");
			}
		}
		#BPOM In
		$col[$this->no-1][44] = $in_bpom;
		#BPOM Out
		$col[$this->no-1][45] = $out_bpom;
		#BPOM process days
		$col[$this->no-1][46] = $duration_bpom;
		#BPOM process status
		$col[$this->no-1][47] = $status_bpom;
		
		$data_append_row[$this->no-1] = NULL;
		
		for($j=0;$j <= count($col[$this->no-1])-1;$j++) {
			$data_append_row[$this->no-1] .= "<td align='center'>".$col[$this->no-1][$j]."</td>";
		}
		$data_append_row[$this->no-1] = "<tr>".$data_append_row[$this->no-1]."</tr>";
		$this->data_append_rows .= $data_append_row[$this->no-1];
	}

	function get_data_rows($xls,$create_date_from = NULL, $create_date_until = NULL) {
		
		$query = $this->data_process_rpt_pmap_tracker->get_data_request($create_date_from,$create_date_until);

		foreach ($query as $row) {
			$this->data_id .= ($this->data_id == "") ? $row->Id : ",".$row->Id ;
		}

		if ($this->data_id != "") {
			$this->last_request_for_revision_date = $this->data_process_rpt_pmap_tracker->get_last_request_for_revision_date($this->data_id);
			$this->last_submission_date = $this->data_process_rpt_pmap_tracker->get_last_submission_date($this->data_id);
			$this->get_processed_request_date = $this->data_process_rpt_pmap_tracker->get_processed_request_date($this->data_id);
		}

		//$this->pending_approvers = $this->data_process_rpt_pmap_tracker->get_data_pending_approvers();

		foreach ($query as $row) {
			$this->append_data_rows($xls,$row);
		}
		
		return $this->data_append_rows;
	}

	function generate_xls($create_date_from=NULL,$create_date_until=NULL) {
		$this->generate_rpt($create_date_from,$create_date_until,TRUE);
	}

	
	function generate_rpt($create_date_from = NULL,$create_date_until = NULL,$xls = FALSE) {
		
		$buttons = NULL;
		$ajax_string = NULL;
		$js_close = NULL;

		$data_btn_close = array(
	        'name'          => 'button',
	        'id'            => 'button',
	        'value'         => 'true',
	        'type'          => 'button',
	        'content'       => 'Close',
	        'onClick'		=> 'window.close();'
		);

		$data_btn_xls = array(
	        'name'          => 'button',
	        'id'            => 'button',
	        'value'         => 'true',
	        'type'          => 'button',
	        'content'       => 'Export to Excel'
		);

		if ($xls) {
			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=rpt_pmap_tracker_".$create_date_from."_".$create_date_until.".xls");
			header("Content-type: application/octet-stream");
		  	header("Pragma: no-cache");
		  	header("Expires: 0");
		}else{
			$buttons = form_button($data_btn_close)."&nbsp;&nbsp;&nbsp;<a href=\"".site_url()."rpt_pmap_tracker/generate_xls/".$create_date_from."/".$create_date_until."\">".form_button($data_btn_xls)."</a>";
		}

		$html = "
		<html>
			<head>
				<style>
					body{
						font-family: sans-serif;
						font-size: 0.875em;
					}
			     	table.table_report,th,td{
			        	border: 1px solid black;
			        	border-collapse: collapse;
			        	font-size: 0.875em;
			     	}
			    </style>
			    ".$ajax_string."
			</head>
			<body>
			".$buttons."
			<br>
			<br>
		";

		$column_header = 
		"<tr>
			<th rowspan='2'>No.</th>
			<th rowspan='2'>Tracking Code</th>
			<th rowspan='2'>Submission Code</th>
			<th rowspan='2'>Brand</th>
			<th rowspan='2'>Material Name</th>
			<th rowspan='2'>Material Type</th>
			<th rowspan='2'>Requisition Type</th>
			<th rowspan='2'>Wih Product-related<br>Information</th>
			<th rowspan='2'>Material Proponent</th>
			<th rowspan='2'>Submission Date</th>
			<th rowspan='2'>Approval Date</th>
			<th rowspan='2'>Total Review<br>Days</th>
			<th rowspan='2'>Approval Code</th>
			<th rowspan='2'>Status</th>
			<th rowspan='2'>Remarks CAMBER</th>
			<th colspan='2'>MSL First Review</th>
			<th rowspan='2'>MSL First Review<br>Days</th>
			<th colspan='2'>Marketing</th>
			<th rowspan='2'>Marketing Review<br>Days</th>
			<th colspan='2'>Regulatory Manager</th>
			<th rowspan='2'>Regulatory Manager<br>Review Days</th>
			<th colspan='2'>Regulatory Head</th>
			<th rowspan='2'>Regulatory Head<br>Review Days</th>
			<th colspan='2'>Legal</th>
			<th rowspan='2'>Legal Review<br>Days</th>
			<th colspan='2'>Communication</th>
			<th rowspan='2'>Communication Review<br>Days</th>
			<th rowspan='2'>Last Date<br>for Request Revision</th>
			<th rowspan='2'>Last Submission Date*</th>
			<th colspan='2'>MSL Final Review</th>
			<th rowspan='2'>MSL Final<br>Review Days</th>
			<th colspan='2'>Medical Affairs Head</th>
			<th rowspan='2'>Medical Affairs Head<br>Review Days</th>
			<th colspan='2'>GM</th>
			<th rowspan='2'>GM Review<br>Days</th>
			<th colspan='2'>BPOM/Gov.<br>Process</th>
			<th rowspan='2'>BPOM/Gov.<br>Process Days</th>
			<th rowspan='2'>BPOM/Gov.<br>Status</th>
		</tr>
		<tr>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
			<th>In</th>
			<th>Out</th>
		</tr>
		";

		$table = "<table class='table_report' cellpadding='10'>".$column_header.$this->get_data_rows($xls,$create_date_from,$create_date_until)."</table><br><br>*) Note: Last submission date is last resubmission date due to material revision.<br>&nbsp;&nbsp;&nbsp;&nbsp;Last submission date is used as In Date for MSL Final Review (for requests with revision).";
		$html = $html.$table."</body></html>";
		echo $html;	
	}

	function index() {
		if($this->session->userdata('logged_in')) {
			$current_date = date_create(date('m/d/Y h:i:s a', time()));
			
			$datasession = $this->session->userdata('logged_in');
			$language = $datasession['language'];
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];
			
			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];

			$datapost['generate_rpt'] = isset($_POST['generate']) ? $_POST['generate'] : FALSE;

			$datapost['submit_date_from'] = isset($_POST['submit_date_from']) ? $_POST['submit_date_from'] : NULL;
			$datapost['submit_date_until'] = isset($_POST['submit_date_until']) ? $_POST['submit_date_until'] : NULL;
			
			$data['menu'] = $this->menu->generatemenu($this->control_name,$menu);
			$data['content_header'] = $this->content->content_header();
			$data['breadcrumb'] = $this->content->breadcrumb();
			$data['java_functions'] = NULL;
			$data['content'] = $this->content->load_content($language,$this->control_name,$datapost);


		   	$this->load->view($this->view_name,$data);	
		}else{
			header ("Location: ".base_url());
		}
	}
}
