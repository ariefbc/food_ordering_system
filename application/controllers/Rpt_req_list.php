<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Rpt_req_list extends CI_Controller {
	private $view_name = 'Vw_rpt_req_list';
	private $control_name = 'rpt_req_list';
	private $content_name = 'content/content_rpt_req_list';

	private $data_append_rows = "";
	private $no = 0;

	private $pending_approvers = array();

	private $approval_log_dm_sm_mm = array();
	private $approval_log_sm_mm = array();
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_rpt_req_list','',TRUE);
		$this->load->model('data_process_translate','',TRUE);
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('app_initializer','','app_init');
	}
	
	function append_data_rows($xls,$data_row) {

		$datasession = $this->session->userdata('logged_in');
		$language = $datasession['language'];

		$this->no++;
		/*
			<th>No.</th>
			<th>Request Number</th>
			<th>Create Date</th>
			<th>Requester Name</th>
			<th>Status</th>
			<th>Purpose</th>
			<th>Requested Amount</th>
			<th>Settled Amount</th>
			<th>Settlement Status</th>
		*/
		
		$col[$this->no-1][0] = $this->no; 
		$col[$this->no-1][1] = $data_row->request_reference_number;
		$col[$this->no-1][2] = ($data_row->createdate != "") ? $this->datetime->convert_mysql_date_format_to_short_string($data_row->createdate) : "";
		$col[$this->no-1][3] = $data_row->requester;
		$col[$this->no-1][4] = $data_row->status;
		$col[$this->no-1][5] = $data_row->purpose_request;

		$col[$this->no-1][6] = (!$xls) ? number_format($data_row->amount_request,0,',','.') : $data_row->amount_request;
		$col[$this->no-1][7] = (!$xls) ? number_format($data_row->amount_settle,0,',','.') : $data_row->amount_settle;

		$col[$this->no-1][8] = $data_row->expense_settlement_status;

		$data_append_row[$this->no-1] = NULL;
		
		for($j=0;$j <= count($col[$this->no-1])-1;$j++) {
			$data_append_row[$this->no-1] .= "<td align='center'>".$col[$this->no-1][$j]."</td>";
		}
		$data_append_row[$this->no-1] = "<tr>".$data_append_row[$this->no-1]."</tr>";
		$this->data_append_rows .= $data_append_row[$this->no-1];
	}

	function get_data_rows($xls,$create_date_from = NULL, $create_date_until = NULL) {
		
		$query = $this->data_process_rpt_req_list->get_data_request($create_date_from,$create_date_until);

		//$this->pending_approvers = $this->data_process_rpt_req_list->get_data_pending_approvers();

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
			header("Content-Disposition: attachment; filename=rpt_req_list_".$create_date_from."_".$create_date_until.".xls");
			header("Content-type: application/octet-stream");
		  	header("Pragma: no-cache");
		  	header("Expires: 0");
		}else{
			$buttons = form_button($data_btn_close)."&nbsp;&nbsp;&nbsp;<a href=\"".site_url()."rpt_req_list/generate_xls/".$create_date_from."/".$create_date_until."\">".form_button($data_btn_xls)."</a>";
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
			<th>No.</th>
			<th>Request Number</th>
			<th>Create Date</th>
			<th>Requester Name</th>
			<th>Status</th>
			<th>Purpose</th>
			<th>Requested Amount</th>
			<th>Settled Amount</th>
			<th>Settlement Status</th>
		</tr>
		";

		$table = "<table class='table_report' cellpadding='10'>".$column_header.$this->get_data_rows($xls,$create_date_from,$create_date_until)."</table>";
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

			$datapost['request_date_from'] = isset($_POST['request_date_from']) ? $_POST['request_date_from'] : NULL;
			$datapost['request_date_until'] = isset($_POST['request_date_until']) ? $_POST['request_date_until'] : NULL;
			
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
