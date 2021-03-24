<?php

class Data_process_translate extends CI_Model {
	
	function __construct() {
		parent::__construct();
		$this->load->library('app_initializer','','app_init');
	}
	
	function update_eng_vocab($vocab){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$data = array(
		        'vocabs' => $vocab,
		        'createby' => 'sys',
        		'createdate' => date("Y/m/d h:i:s a"),
        		'isdelete' => 0
		);

		$this->db->insert('refenglishvocabs', $data);

		$this->db->query('use '.$current_db);
	}
	
	function check_eng_vocab($vocab){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('Id');
		$this->db->where('isdelete', 0);
		$this->db->where('vocabs', $vocab);

		$tmp = count($this->db->get('refenglishvocabs')->result_array());

		$this->db->query('use '.$current_db);

		return $tmp;		
	}
	
	function do_translate($language,$vocab){
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('refothervocabs.Id,refenglishvocabs.vocabs,refothervocabs.othervocabs');
		$this->db->from('refothervocabs');
		$this->db->join('refenglishvocabs', 'refothervocabs.english_id = refenglishvocabs.Id');
		$this->db->where('refenglishvocabs.vocabs',$vocab);
		$this->db->where('refothervocabs.countryid',$language);
		$query = $this->db->get()->result();

		$this->db->query('use '.$current_db);
		
		$tmp = $vocab;
		foreach ($query as $row) {
			$tmp = ($row->othervocabs != '')? $row->othervocabs : $row->vocabs;
		}
		return $tmp;
	}
	
	function check_vocab($language,$vocab){
		if ($this->check_eng_vocab($vocab) == 0) $this->update_eng_vocab($vocab);
		
		if ($language != 1){
			$vocab = $this->do_translate($language, $vocab);		
		}
		
		return $vocab;
	}
}