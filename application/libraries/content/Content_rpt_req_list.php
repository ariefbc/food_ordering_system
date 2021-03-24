
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_rpt_req_list {

	function __construct()
    {
    	$this->ci =& get_instance();
        $this->ci->load->library("Pdf",'',"Pdf");
        $this->ci->load->model('data_process_timeset','datetime',TRUE);
        $this->ci->load->library('app_initializer','','app_init');
    }

    function update_eng_vocab($vocab) {
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
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select('Id');
		$this->ci->db->where('isdelete', 0);
		$this->ci->db->where('vocabs', $vocab);
		return count($this->ci->db->get($applat_db.'.refenglishvocabs')->result_array());		
	}
	
	function do_translate($language,$vocab) {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select($applat_db.'.refothervocabs.Id, '.$applat_db.'.refenglishvocabs. vocabs, '.$applat_db.'.refothervocabs.othervocabs');
		$this->ci->db->from($applat_db.'.refothervocabs');
		$this->ci->db->join($applat_db.'.refenglishvocabs', $applat_db.'.refothervocabs.english_id = '.$applat_db.'.refenglishvocabs.Id');
		$this->ci->db->where($applat_db.'.refenglishvocabs.vocabs',$vocab);
		$this->ci->db->where($applat_db.'.refothervocabs.countryid',$language);
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

	public function load_content($language,$control_name,$datapost)
    {	
    	
    	$contents = "<div class=\"box box-primary\">
                <!-- form start -->
                ".form_open($control_name, array('role'=>'form'))."
                  	<div class=\"box-body\">
                  	<div class=\"form-group\">
	                      <label for=\"lblservice\">Request Create Date From</label><br/>
	                      <input type=\"input\"   class=\"form-control\" id=\"request_date_from\" name = \"request_date_from\" placeholder=\"Request Create Date From\" value=\"".$datapost['request_date_from']."\" maxlength=\"255\"  >
	                </div>
	                <div class=\"form-group\">
	                      <label for=\"lblservice\">Request Create Date Until</label><br/>
	                      <input type=\"input\"   class=\"form-control\" id=\"request_date_until\" name = \"request_date_until\" placeholder=\"Request Create Date Until\" value=\"".$datapost['request_date_until']."\" maxlength=\"255\"  >
	                </div>
	                </div><!-- /.box-body -->
                  	<div class=\"box-footer\">
                    <button type=\"submit\" name=\"generate\" value=TRUE class=\"btn btn-primary\" onClick=\"test();\"><i class=\"fa fa-print\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Generate Report")."</button>&nbsp;
                  </div>
                </form>
                </div><!-- /.box -->";

        return $contents;
    }
	
	function content_header($header = "Cash Advance Requests List") {
		$content_header = "<h1>".$header."</h1>";
		return $content_header;	
	}
	
	function breadcrumb() {
		$breadcrumb = "<li>Reports</li>
            <li>Cash Advance Requests List</li>";
		return $breadcrumb;	
	}
}	
