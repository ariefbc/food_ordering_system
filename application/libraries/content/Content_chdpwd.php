<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_chdpwd {
	private $ci;
	
	function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->library('app_initializer','','app_init');
    }
	
	
	function update_eng_vocab($vocab){
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->query('use '.$applat_db);

		$data = array(
		        'vocabs' => $vocab,
		        'createby' => 'sys',
        		'createdate' => date("Y/m/d h:i:s a"),
        		'isdelete' => 0
		);

		$this->ci->db->insert('refenglishvocabs', $data);

		$this->ci->db->query('use '.$current_db);
	}
	
	function check_eng_vocab($vocab){
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->query('use '.$applat_db);

		$this->ci->db->select('Id');
		$this->ci->db->where('isdelete', 0);
		$this->ci->db->where('vocabs', $vocab);

		$tmp = count($this->ci->db->get('refenglishvocabs')->result_array());

		$this->ci->db->query('use '.$current_db);

		return $tmp;		
	}
	
	function do_translate($language,$vocab){
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->query('use '.$applat_db);

		$this->ci->db->select('refothervocabs.Id,refenglishvocabs.vocabs,refothervocabs.othervocabs');
		$this->ci->db->from('refothervocabs');
		$this->ci->db->join('refenglishvocabs', 'refothervocabs.english_id = refenglishvocabs.Id');
		$this->ci->db->where('refenglishvocabs.vocabs',$vocab);
		$this->ci->db->where('refothervocabs.countryid',$language);
		$query = $this->ci->db->get()->result();

		$this->ci->db->query('use '.$current_db);
				
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
	
    public function load_content($language,$control_name,$msgerror)
    {
    	$contents = "<div class=\"box box-primary\">
                <!-- form start -->
                ".form_open($control_name.'/process', array('role'=>'form','name'=>'eform'))."
                  <div class=\"box-body\">
                    <div class=\"form-group\">
                      <label for=\"oldpassword\">".$this->check_vocab($language,"Old Password")."</label>
                      <input type=\"password\" class=\"form-control\" id=\"oldpassword\" name = \"oldpassword\" placeholder=\"".$this->check_vocab($language,"Old Password")."\">
                    </div>
                    <div class=\"form-group\">
                      <label for=\"newpassword\">".$this->check_vocab($language,"New Password")."</label>
                      <input type=\"password\" class=\"form-control\" id=\"newpasword\" name=\"newpassword\" placeholder=\"".$this->check_vocab($language,"New Password")."\">
                    </div>
                    <div class=\"form-group\">
                      <label for=\"verifynewpassword\">".$this->check_vocab($language,"Verify New Password")."</label>
                      <input type=\"password\" class=\"form-control\" id=\"verifynewpasword\" name=\"verifynewpassword\" placeholder=\"".$this->check_vocab($language,"Verify New Password")."\">
                    </div>
                    </div><!-- /.box-body -->
					<div class=\"box-footer\">
                    <button type=\"submit\" class=\"btn bg-orange\"><i class=\"fa fa-floppy-o\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Change Password")."</button>&nbsp;".$msgerror."
                  </div>
                </form>
              </div><!-- /.box -->";
		return $contents;
    }
	
	function content_header($language){
		$content_header = "<h1>".$this->check_vocab($language,"Change Password")."</h1>";
		return $content_header;	
	}
	
	function breadcrumb($language){
		$breadcrumb = "<li>".$this->check_vocab($language,"My Profile")."</li>
            <li>".$this->check_vocab($language,"Change Password")."</li>";
		return $breadcrumb;	
	}
}