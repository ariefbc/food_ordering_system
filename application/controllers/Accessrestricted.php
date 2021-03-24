<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Accessrestricted extends CI_Controller {
	private $view_name = 'Vwhome';
	private $control_name = 'accessrestricted';
	private $content_name = 'content/content_accessrestricted';
	
	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
	}
	
	function index(){
		if($this->session->userdata('logged_in')) {
			$datasession = $this->session->userdata('logged_in');
			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];
			$data['menu'] = $this->menu->generatemenu('accessrestricted',$menu);
			$data['content_header'] = $this->content->content_header();
			$data['breadcrumb'] = $this->content->breadcrumb();
			$data['content'] = $this->content->load_content();
		   $this->load->view($this->view_name,$data);	
		}else{
			header ("Location: ".base_url());
		}
	}
}
