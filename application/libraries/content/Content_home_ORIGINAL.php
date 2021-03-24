<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_home {

    public function load_content($content = 'Content Home')
    {
    	return $content;
    }
	
	function content_header($header = "Dashboard"){
		$content_header = "<h1>".$header."</h1>";
		return $content_header;	
	}
	
	function breadcrumb(){
		$breadcrumb = "<li>Dashboard</li>
            <li>Dashboard 1</li>";
		return $breadcrumb;	
	}
}