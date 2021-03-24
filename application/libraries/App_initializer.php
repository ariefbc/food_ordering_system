<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class App_initializer {
	function __construct() {
		
	}
	
	function app_init(){
		$init['app_code'] = 'fos';
		
		$init['file_upload_dir'] = '../docvault/'.$init['app_code'].'/';

		$init['reference_generator_id'] = 'FOS';

		$init['applat_db_name'] = 'established_order';
		
		return $init;
	}
}

?>