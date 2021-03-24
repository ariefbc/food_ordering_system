<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Gmap_popup extends CI_Controller {
	private $view_name = 'Vw_gmap_popup';
	private $control_name = 'Gmap_popup';
	
	function __construct() {
		parent::__construct();
	}
	
	function index($lat,$long){
		if($this->session->userdata('logged_in')) {
			$data['lat'] = $lat;
			$data['long'] = $long;
		   $this->load->view($this->view_name,$data);	
		}else{
			header ("Location: ".base_url());
		}
	}
}
