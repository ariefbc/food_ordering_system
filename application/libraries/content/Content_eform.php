<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_eform {
	
	private $ci;
	
	function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->library('wf','','wf');
		$this->ci->load->model('data_process_translate','',TRUE);
		$this->ci->load->model('data_process_eform','dp_eform',TRUE);
		$this->ci->load->library('app_initializer','','app_init');
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
	
	function load_content_form_selection($language,$control_name,$menu_name,$label,$options) {
				$contents = "<div class=\"box box-primary\">
	                <!-- form start -->
	                ".form_open($control_name.'/select/'.$menu_name, array('role'=>'form'))."
	                  <div class=\"box-body\">
	                    <div class=\"form-group\">
	                      <label for=\"lblapp\">".$label."</label><br/>
	                      ".form_dropdown('selection',$options,'','class="form-control select2" style="width: 100%;"')."
	                    </div>
	                    </div><!-- /.box-body -->
						<div class=\"box-footer\">
	                    <button type=\"submit\" class=\"btn bg-orange\"><i class=\"fa fa-check\" aria-hidden=\"true\"></i>&nbsp;".$this->ci->data_process_translate->check_vocab($language,"Select")."</button>&nbsp;
	                  </div>
	                </form>
	              </div><!-- /.box -->";
	    	return $contents;
	}
		
	function load_content_form($validate_revise,$language,$control_name,$menu_name,$form_components,$data_id,$task,$data_detail,$msgerror,$data_menu,$subformgrid="",$draft_id,$btn_submit_disabled,$formtype,$subform_name = '',$main_id=0) {
			$formtype_tmp = $formtype;
			$formtype = $formtype_tmp['formtype'];
			$form_name = $formtype_tmp['form_name'];

			$subform_disable_insert = FALSE;
			$subform_disable_edit = FALSE;

			if ($formtype == 'subform') {
				$query = $this->ci->dp_eform->get_data_subform($data_menu['id'],$form_name);
				$row = $query[0];
				
				if ((int)$row->is_insert_disable == 1) {
					$subform_disable_insert = TRUE;
				}

				if ((int)$row->is_edit_disable == 1) {
					$subform_disable_edit = TRUE;
				}
			}
			
			$form_open = "";
			$form_close = "";
			$btn_cancel = "Close";
			$btn_save = "";
			$btn_submit_request = "";

			if (($formtype == 'mainform' && ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) || ($formtype == 'subform' && (!$subform_disable_edit || !$subform_disable_insert)))) {
				$form_additional_param = array('id'=>$data_id,'task'=> $task);
				
				if ($formtype == 'subform') { 
					$form_additional_param['main_id']	= $main_id;
				} else {
					if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_transdata'] == 1) && $task == 'new') {
						$form_additional_param['draft_id']	= $draft_id;
					}	
				}
				
				$form_url = ($formtype == 'mainform') ? $control_name.'/process/'.$menu_name.'/'.$formtype : $control_name.'/process/'.$menu_name.'/'.$formtype.'/'.$subform_name;
				
				$form_open = form_open_multipart($form_url, array('role'=>'form', 'name'=>'eform'),$form_additional_param);
				$form_close = "</form>";
				$btn_cancel = "Cancel";
				
				$draft_value = "";

				if ($task == 'delete') {
					$class_btn_save = "fa fa-trash-o";
					$txt_btn_save = "Remove";
				} else {
					$class_btn_save = "fa fa-floppy-o";
					$txt_btn_save = "";
					
					if ($data_menu['is_masterdata'] == 1 || $formtype == 'subform') {
						$txt_btn_save = "Save";

						// MODIFIED ESTABLISHED 2021, CHANGE SAVE BUTTON TEXT
						if ($data_menu['url'] == 'orders' && 
							$formtype== 'mainform' &&
							$task == 'new') {
								$txt_btn_save = "Open Order";
						}
						// [END OF]MODIFIED ESTABLISHED 2021, CHANGE SAVE BUTTON TEXT
					} else {
						$txt_btn_save = "Save as Draft";
						$draft_value = " value=\"Draft\" ";
					}
					
					$btn_submit_request = ($formtype == 'mainform' && ($data_menu['is_workflowdata'] == 1 || $data_menu['is_transdata'] == 1))? "&nbsp;<button type=\"submit\" ".$btn_submit_disabled." id=\"btn_submit\" name=\"submission\" value=\"1\" class=\"btn bg-orange\" onClick=\"btn_response();\"><i class=\"fa fa-check\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Submit")."</button>" : "";

				}
				
				$btn_save = "&nbsp;<button type=\"submit\" name=\"submission\" class=\"btn bg-orange\" onClick=\"btn_response();\" ".$draft_value."><i class=\"".$class_btn_save."\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,$txt_btn_save)."</button>&nbsp;".$msgerror;
				
				if ($task == 'edit' && ($data_menu['is_edit_disable'] == 1 || ($formtype == 'subform' && $subform_disable_edit))) {
					$form_open = "";
					$form_close = "";
					$btn_cancel = "Close";
					$btn_save = "";		
				}
			} else {
				if ($msgerror != '') {
					$btn_submit_request .= "&nbsp;".str_replace("\\x", "\\n", $msgerror);
				}
			}
			
			$btn_href_cancel = (($formtype == "mainform"))? site_url($control_name."/cancel/".$menu_name) : site_url($control_name."/edit/".$menu_name."/".$main_id);		
			
			if ($formtype == 'mainform' && $data_menu['is_approval'] == 1) {
				$menu_id = $this->ci->wf->get_origin_data_menu_id($data_menu);
				$form_additional_param = array('id'=>$data_id,'task'=> $task,'menu_id'=>$menu_id);

				$form_url = ($formtype == 'mainform') ? $control_name.'/process/'.$menu_name.'/'.$formtype : $control_name.'/process/'.$menu_name.'/'.$formtype.'/'.$subform_name;

				$form_open = form_open_multipart($form_url, array('role'=>'form','name' => 'eform'),$form_additional_param);	
				
				$button_disabled = "";
				$button_revise_disabled = "";
				$button_reject_disabled = "";

				$button_approve_text = "Approve";
				$button_approve_with_changes = "";

				$btn_submit_request = "&nbsp;<button type=\"submit\" name=\"submission\" value=\"revise\" class=\"btn btn-warning\" onClick=\"btn_response();\" ".$button_revise_disabled."><i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Revise")."</button>&nbsp;<button type=\"submit\" name=\"submission\" value=\"reject\" class=\"btn btn-danger\" onClick=\"btn_response();\" ".$button_reject_disabled."><i class=\"fa fa-times\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Reject")."</button>".$button_approve_with_changes."&nbsp;<button type=\"submit\" name=\"submission\" value=\"approve\" class=\"btn btn-success\" onClick=\"btn_response();\" ".$button_disabled."><i class=\"fa fa-check\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,$button_approve_text)."</button>";

				$form_close = "</form>";

				$datasession = $this->ci->session->userdata('logged_in');

				if ($msgerror != '' || !$this->ci->wf->show_approval_comm_box($datasession,$data_menu,$data_detail,$data_id)) {
					//$form_open = '';
					//$form_close = '';
					$msgerror = ($msgerror != '') ? $this->check_vocab($language,$msgerror) : $msgerror;
					if ($validate_revise) {
						if ($msgerror != '') {
							if ($this->ci->wf->show_approval_comm_box($datasession,$data_menu,$data_detail,$data_id)) {
								$btn_submit_request .= "<br><br>".$msgerror;
							} else {
								$btn_submit_request = "<br><br>".$msgerror;
								$form_open = '';
								$form_close = '';
							}
						}
					} else {
						$btn_submit_request .= "<br><br>".$msgerror;
					}
				}
			}
			
			$button_panel_1 = "";
			$button_panel_2 = "";
			
			if ($task == 'new') {
				$button_panel_1 = "
					<div class=\"box-footer\" id=\"div_button_panel\">
                    <button type=\"button\" class=\"btn bg-orange\" onclick=\"location.href='".$btn_href_cancel."';\"><i class=\"fa fa-times\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,$btn_cancel)."</button>".$btn_save.$btn_submit_request."
                  </div>".$form_close."</div><!-- /.box -->";
			} else {
				$button_panel_2 = "
					<div class=\"box-footer\" id=\"div_button_panel\">
                    <button type=\"button\" class=\"btn bg-orange\" onclick=\"location.href='".$btn_href_cancel."';\"><i class=\"fa fa-times\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,$btn_cancel)."</button>".$btn_save.$btn_submit_request."
                  </div>".$form_close."</div><!-- /.box -->";

				  // MODIFIED ESTABLISHED 2021, CHANGE BUTTON PANEL 2 FOR ORDER FORM
				  if ($formtype == 'mainform') {
					if ($data_menu['url'] == 'orders') {
						$button_panel_2 = "
							<div class=\"box-footer\" id=\"div_button_panel\">
							<button type=\"button\" class=\"btn bg-orange\" onclick=\"location.href='".$btn_href_cancel."';\"><i class=\"fa fa-arrow-circle-left\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Order List")."</button>"."
						</div>".$form_close."</div><!-- /.box -->";
					}

					if ($data_menu['url'] == 'payments') {
						$button_panel_2 = "
							<div class=\"box-footer\" id=\"div_button_panel\">
							<button type=\"button\" class=\"btn bg-orange\" onclick=\"location.href='".$btn_href_cancel."';\"><i class=\"fa fa-arrow-circle-left\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Order List")."</button>".$btn_save.$btn_submit_request."
						</div>".$form_close."</div><!-- /.box -->";
					}


				  }
				  // [END OF] MODIFIED ESTABLISHED 2021, CHANGE BUTTON PANEL 2 FOR ORDER FORM

            }

			$contents = "<div class=\"box box-primary table-responsive\">".$form_open."
                  <div class=\"box-body\">
                    <div class=\"form-group\">".$form_components."
                    </div><!-- /.box-body -->".$button_panel_1.$subformgrid.$button_panel_2;
    	return $contents;
	}
	
    public function load_content($language,$control_name,$menu_name,$content,$data_menu)
    {
    	$search = $content['search'];
    	$link_add = ($content['datagrid'] != '')? site_url($control_name)."/add/".$menu_name : site_url($control_name).'/index/'.$menu_name;
    	$content['datagrid'] = ($content['datagrid'] != '')? $content['datagrid'] : 'Table for this data is not created yet. Please request for development.';
    	
    	/////////// Check Function Access //////////////////////
    	$btn_add = ($data_menu['is_insert_disable'] == 1)? "" : "<br>
    			<button type=\"button\" class=\"btn bg-orange\" onclick=\"location.href='".$link_add."';\"><i class=\"fa fa-plus-circle\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Add New")."</button><br>";
    	/////////////////////////////////////////////////////////////////////
    	
    	$filter_status_dropdown = "";
    	$options_status_dropdown_array = array();

    	if ($data_menu['is_workflowdata'] == 1) {
    		$js = "id=\"show_request\" class=\"form-control select2\" style=\"width: 100%;\" 
    		onchange='window.location = \"".site_url($control_name."/show_request/".$menu_name."/")."\"+this.value;'";
    		$options_status_dropdown_array = array(
		        'All' => $this->check_vocab($language,"All"),
		        'Draft' => $this->check_vocab($language,"Draft"),
		        'Revise' => $this->check_vocab($language,"Revised"),
		        'Waiting Approval' => $this->check_vocab($language,"Waiting Approval"),
		        'Full Approve' => $this->check_vocab($language,"Full Approved")
		    );

		    $selected_show_request = "All";
		    
		}

		if ($data_menu['is_approval'] == 1) {
			$js = "id=\"show_request\" class=\"form-control select2\" style=\"width: 100%;\" 
    		onchange='window.location = \"".site_url($control_name."/show_request/".$menu_name."/")."\"+this.value;'";
    		$options_status_dropdown_array = array(
    			'Waiting Approval' => $this->check_vocab($language,"Waiting Approval"),
    			'I have processed' => $this->check_vocab($language,"Requests I have processed"),
		        'Revise' => $this->check_vocab($language,"Revised"),
		        'Full Approve' => $this->check_vocab($language,"Full Approved")
		    );

		    $selected_show_request = "Waiting Approval";
		    
		}

		if ($this->ci->session->userdata($menu_name.'_show_request')) {
	    	$selected_show_request = $this->ci->session->userdata($menu_name.'_show_request');
	    }

    	if ($options_status_dropdown_array) {
    		$filter_status_dropdown = form_open(site_url($control_name."/show_request/".$menu_name))."
    					<div class=\"form-group\">
                      		<label for=\"label\">".$this->check_vocab($language,"Show Requests")."</label></br>
                      		".form_dropdown('show_request', $options_status_dropdown_array, $selected_show_request,$js)."
						</div><button type=\"submit\" class=\"btn bg-orange\" name=\"btnfilter\" value=TRUE ><i class=\"fa fa-search\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Filter")."</button></form><br>";

		}
    	#

    	$contents="<div class='box'>
    			<div class='box-body' style='overflow-x:auto;overflow-y:auto;'>
    			".$filter_status_dropdown
    			.form_open($control_name.'/index/'.$menu_name, array('role'=>'form'))."
    				<div class=\"form-group\">
                      <label for=\"lblsearch\">".$this->check_vocab($language,"Search Data")."</label>
                      <input type=\"input\" class=\"form-control\" id=\"txtsearch\" name = \"txtsearch\" placeholder=\"".$this->check_vocab($language,"Search")."\" value=\"".$search['search']."\">
                    </div>
                      <button type=\"submit\" class=\"btn bg-orange\" name=\"btnsearch\" value=TRUE ><i class=\"fa fa-search\" aria-hidden=\"true\"></i>&nbsp;".$this->check_vocab($language,"Search")."</button>&nbsp;".anchor($control_name.'/showall/'.$menu_name, "[".$this->check_vocab($language,"Show All")."]")."
                 </form>".$btn_add."<br>
                ".$content['pagination'].$content['datagrid']."
                </div>
              </div>";
    	return $contents;
    }
	
	function content_header($language,$data_menu,$show_interface,$data_detail = array()) {
		//print_r($data_menu);exit;
		$request_reference_number = "";
		$this->ci->load->library('shared_variables','','shared_variables');
		$additional_forms = $this->ci->shared_variables->display_reference_column;

		if ($show_interface) {
			if (array_key_exists('request_reference_number',$data_detail)) {
				$request_reference_number = $data_detail['request_reference_number'];
			}
		}
		$request_reference_number = ($request_reference_number != "") ? " : <br>".$request_reference_number : "";

		if (array_key_exists('subform_title', $data_menu)) {
			$subform_title = ($data_menu['subform_title'] != "") ? "<h5>".$data_menu['subform_title']."</h5>" : "";
		} else {
			$subform_title = "";
		}
		
		$content_header = "<h1>".$this->check_vocab($language,$data_menu['title']).$request_reference_number."</h1>".$subform_title;
		
		return $content_header;	
	}
	
	function breadcrumb($language,$data_menu) {
		$breadcrumb = "<li>".str_replace('<br>',' ',$this->check_vocab($language,$data_menu['title_parent']))."</li>
            <li>".str_replace('<br>',' ',$this->check_vocab($language,$data_menu['title']))."</li>";
		return str_replace('<br>',' ',$breadcrumb);	
	}
}