<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_accessrestricted {

    public function load_content()
    {
    	return 'You are accessing a restricted area. Please contact you system administrator access authorization.';
    }
	
	function content_header(){
		$content_header = "<h1>Restricted Area</h1>";
		return $content_header;	
	}
	
	function breadcrumb(){
		$breadcrumb = "<li>Warning</li>
            <li>Restricted Area</li>";
		return $breadcrumb;	
	}
}