<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once (APPPATH.'/libraries/REST_Controller.php');
//use Restserver\Libraries\REST_Controller;

class Ws extends REST_Controller {

	function __construct($config = 'rest') {
		parent::__construct($config);
	}

	function data_get($full_table_name,$data_date,$data_type)
    {	
        $data_date = str_replace('zzz', ' ',$data_date);
         
        if ($full_table_name !== '') {
            $this->load->model('data_process_ws','dp',TRUE);

            $query = $this->dp->get_data($full_table_name,$data_date,$data_type);
            
            if($query)
            {
                $this->response($query, 200); // 200 being the HTTP response code
            }
            else
            {
                $this->response($query, 200);
            }
        } else {
            $this->response(NULL, 404);
        }
    }
}