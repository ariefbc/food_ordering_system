<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_home {

	function __construct() {
		$this->ci =& get_instance();
	}

    function content_header($header = "Food Ordering System",$request_reference_number = "") {
		$request_reference_number = ($request_reference_number != "") ? " : ".$request_reference_number : "";
		$content_header = "<h1>".$header.$request_reference_number."</h1>";
		return $content_header;	
	}

	function update_eng_vocab($vocab) {
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$data = array(
		        'vocabs' => $vocab,
		        'createby' => 'sys',
        		'createdate' => date("Y/m/d h:i:s a"),
        		'isdelete' => 0
		);

		$this->ci->db->insert($applat_db.'.refenglishvocabs', $data);
	}

	function check_eng_vocab($vocab) {
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('Id');
		$this->ci->db->where('isdelete', 0);
		$this->ci->db->where('vocabs', $vocab);
		
		$tmp = count($this->ci->db->get($applat_db.'.refenglishvocabs')->result_array());

		return $tmp;
	}

	function do_translate($language,$vocab) {
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('refothervocabs.Id,refenglishvocabs.vocabs,refothervocabs.othervocabs');
		$this->ci->db->from($applat_db.'.refothervocabs');
		$this->ci->db->join($applat_db.'.refenglishvocabs', $applat_db.'.refothervocabs.english_id = '.$applat_db.'.refenglishvocabs.Id');
		$this->ci->db->where('refenglishvocabs.vocabs',$vocab);
		$this->ci->db->where('refothervocabs.countryid',$language);
		$query = $this->ci->db->get()->result();
		
		$tmp = $vocab;
		foreach ($query as $row) {
			$tmp = ($row->othervocabs != '')? $row->othervocabs : $row->vocabs;
		}
		return $tmp;
	}

	function check_vocab($language,$vocab) {
		if ($this->check_eng_vocab($vocab) == 0) $this->update_eng_vocab($vocab);
		
		if ($language != 1) {
			$vocab = $this->do_translate($language, $vocab);		
		}
		
		return $vocab;
	}
	
	public function load_content($content = 'Home') {
    	//return $content;
    	return $content;
    }
	
	function breadcrumb() {
		$datasession = $this->ci->session->userdata('logged_in');

		$breadcrumb = "<li>".$this->check_vocab($datasession['language'],'Home')."</li>";
		return $breadcrumb;	
	}
}