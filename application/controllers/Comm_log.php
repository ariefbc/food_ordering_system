<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Comm_log extends CI_Controller {
	private $view_name = 'Vwcommlog';
	private $control_name = 'home';
	private $content_name = 'content/content_home';
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->model('data_process_comm_log','dp_comm_log',TRUE);
		$this->load->model('data_process_translate','',TRUE);
	}
	
	function load_datagrid($data_menu_id,$data_id){
		$datasession = $this->session->userdata('logged_in');
		$language = $datasession['language'];
		$query = $this->dp_comm_log->load_data($data_menu_id,$data_id);
		$tmpl = array ('table_open' => '<table id=\'dbgrid\' class=\'table table-bordered table-striped\'>');
		$this->table->set_template($tmpl);
		
		$no = 0;
		foreach ($query as $row) {
			 $task = "&nbsp;";
		        
			 $no++;
		        $content = array($no, $this->data_process_translate->check_vocab($language,$row->status), $row->createdate, $row->fullname,$row->comm_msg,$task);
		        $this->table->add_row($content);
		    }
		
		//$this->table->set_heading($this->data_process_translate->check_vocab($language,'No.'),$this->data_process_translate->check_vocab($language,'Status'),$this->data_process_translate->check_vocab($language,'Tanggal Proses'),$this->data_process_translate->check_vocab($language,'Oleh'),$this->data_process_translate->check_vocab($language,'Submission / Approval Message'));
		$this->table->set_heading($this->data_process_translate->check_vocab($language,'No.'),$this->data_process_translate->check_vocab($language,'Status'),$this->data_process_translate->check_vocab($language,'Process Date'),$this->data_process_translate->check_vocab($language,'By'),$this->data_process_translate->check_vocab($language,'Request/Approval Message'));
		return $this->table->generate();
	}
	
	function index($data_menu_id,$data_id){
		if($this->session->userdata('logged_in')) {
			$datasession = $this->session->userdata('logged_in');
			$language = $datasession['language'];
			$header = $this->data_process_translate->check_vocab($language,'Approval Log');
			//$header = 'Riwayat Status Pengajuan';

			$datasession = $this->session->userdata('logged_in');
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];
			
			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data['menu'] = NULL;
			$data['content_header'] = $this->content->content_header($header,$this->dp_comm_log->get_reference_number($data_menu_id,$data_id));
			$data['breadcrumb'] = NULL;
			$data['content'] = $this->load_datagrid($data_menu_id,$data_id);
		   $this->load->view($this->view_name,$data);	
		}else{
			header ("Location: ".base_url());
		}
	}
}
