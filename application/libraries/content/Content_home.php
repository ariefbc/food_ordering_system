<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_home {

    public function load_content($content = 'Home')
    {
    	//return $content;
    	return $content;
    }
	
	function content_header($header = "e-PMAP",$request_reference_number = ""){
		$request_reference_number = ($request_reference_number != "") ? " : ".$request_reference_number : "";
		$content_header = "<h1>".$header.$request_reference_number."</h1>";
		return $content_header;	
	}
	
	function breadcrumb(){
		//$breadcrumb = "<li>Dashboard</li><li>Dashboard 1</li>";
		$breadcrumb = "<li>Home</li>";
		return $breadcrumb;	
	}
}