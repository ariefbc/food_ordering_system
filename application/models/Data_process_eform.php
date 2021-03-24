<?php
class Data_process_eform extends CI_Model {
	
	
		
	function __construct() {
		parent::__construct();
		$this->load->model('data_process_timeset','datetime',TRUE);
		$this->load->library('Wf','',TRUE);
		$this->load->library('app_initializer','','app_init');
		$this->load->library('shared_variables','','shared_variables');
	}

	function initiate_hash_link($data_menu) {
		#mainform table
		$this->set_hash_link_column($data_menu,'mainform');

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('full_table_name');
		$this->db->where('menu_id', $data_menu['id']);
		$this->db->where('isdelete', 0);
		$this->db->from($applat_db.'.refsubform_menu');

		$query =  $this->db->get()->result();

		foreach ($query as $row) {
			#subform table
			$this->set_hash_link_column($data_menu,'subform',$row->full_table_name);
		}

		return NULL;
	}

	function set_hash_link_column($data_menu,$formtype,$subform_full_table_name = '') {

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$full_table_name = ($formtype == 'mainform') ? $data_menu['full_table_name'] : $subform_full_table_name;

		if ($full_table_name != '') {
			$this->db->select('dbname');
			$this->db->where('Id', $data_menu['app_id']);
			$this->db->limit(1);
			$this->db->from($applat_db.'.refapps');
			$query =  $this->db->get()->result();
	
			$app_db_name = $query[0]->dbname;
	
			$this->db->select('COLUMN_NAME');
			$this->db->from('information_schema.`COLUMNS`');
			$this->db->where('COLUMN_NAME','hash_link');
			$this->db->where('TABLE_NAME',$full_table_name);
			$this->db->where('TABLE_SCHEMA',$app_db_name);
			$this->db->limit(1);
			
			$query =  $this->db->get()->result();
	
			if (!$query) {
				$fields = array('hash_link'=> array(
									  'type' => 'varchar',
							                'constraint' =>'255',
	                						  'null' => TRUE, 
	                						  'after' => 'Id'
							        )
							);
				$this->load->dbforge();
				$this->dbforge->add_column($app_db_name.'.'.$full_table_name, $fields);	

				$sql = "CREATE INDEX idx_hash_link ON ".$full_table_name."(hash_link)";
				$this->db->query($sql);
			}

			#update column hash_link
			$this->db->select('Id');
			$this->db->from($full_table_name);
			$this->db->where('hash_link is null',NULL);
			
			$query =  $this->db->get()->result();

			foreach ($query as $row) {
				$hash_link = md5($this->datetime->get_current_datetime());
				$new_hash_link = FALSE;

				while (!$new_hash_link) {
					$this->db->select('Id');
					$this->db->from($full_table_name);
					$this->db->where('hash_link',$hash_link);
					
					$query_check_hash_link =  $this->db->get()->result();

					if ($query_check_hash_link) {
						$hash_link = md5($hash_link);
					} else {
						$new_hash_link = TRUE;
					}
				}

				$data = array(
					'hash_link' => $hash_link,
			    );
				
				$this->db->where('Id', $row->Id);
				$this->db->update($full_table_name, $data);
			}
		}

		return NULL;
	}
	
	function get_fields_id_to_revise($data_menu,$approved_fields_id_array) {
		$approved_field_id_string = "";

		if ($approved_fields_id_array) {
			foreach ($approved_fields_id_array as $key => $value) {
				$approved_field_id_string .= ($approved_field_id_string == "") ? $key : ','.$key ;
			}
		}
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('Id');
		$this->db->where('url', $data_menu['url']);
		if ($approved_field_id_string != "") {
			$this->db->where('Id not in ('.$approved_field_id_string.')', NULL);
		}
		$this->db->from('v_fieldstoreview');
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}

	function update_log_field_review($data_id,$data_menu,$revised_fields_id,$review_note) {
		$datasession = $this->session->userdata('logged_in');
		$current_datetime = $this->datetime->get_current_datetime();

		foreach ($revised_fields_id as $row) {
			$data = array();

			$tmp = array(
			        'createby' => $datasession['username'],
	        		'createdate' => $current_datetime,
	        		'isdelete' => 0,
	        		'menu_id' => $data_menu['id'],
	        		'data_id' => $data_id,
	        		'form_id' => $row->Id,
	        		'comm_msg' => $review_note[$row->Id]
			);

			$data = array_merge($data,$tmp);
			
			$current_conn = $this->db;
			$current_db = $current_conn->database;
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->query('use '.$applat_db);

			$this->db->insert('trnlogfieldreview', $data);

			$this->db->query('use '.$current_db);
		}
	}

	function remove_file_upload($data_menu,$fu_fieldname,$data_id,$form_fields,$formtype) {

		$full_table_name = ($formtype == 'mainform') ? $form_fields[0]->full_table_name : $form_fields[0]->subform_full_table_name;
		$data_id = $this->get_data_id_from_hash_link($data_id,$full_table_name);

		$datasession = $this->session->userdata('logged_in');
		$data_before = $this->get_data_prior_change($full_table_name,$form_fields,$data_id);

		$data = array(
			$fu_fieldname =>NULL,
        );
		
		$this->db->where('Id', $data_id);
		$this->db->update($full_table_name, $data);

		$log['app_id'] = $datasession['app_id'];
		$log['data_trans_type'] = 'DATA REMOVE';
		$log['username'] = $datasession['username'];
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $full_table_name." zzz ".$fu_fieldname.": ".$data_before[$fu_fieldname]." => NULL zzz Id: ".$data_id;
		$this->update_log($log);
	}

	function get_update_log_field_review($data_id,$data_menu,$field_name) {
		$tmp = array();
		$this->db->select('last_approval_process_by');
		$this->db->where('Id', $data_id);
		$this->db->from($data_menu['full_table_name']);
		
		$tmp = $this->db->get()->result();

		if ($tmp) {
			$last_approval_process_by = $tmp[0]->last_approval_process_by;
		} else {
			$last_approval_process_by = "";
		}

		$tmp = array();

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select();
		$this->db->where('data_id', $data_id);
		$this->db->where('full_table_name', $data_menu['full_table_name']);
		$this->db->where('field_name', $field_name);
		$this->db->where('createby', $last_approval_process_by);
		$this->db->from($applat_db.'.v_fieldsreviewlog');
		$this->db->order_by('createdate','desc');
		$this->db->limit(1);
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}

	function get_application_users() {
		
		$tmp = array();

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('username,fullname');

		$this->db->from($applat_db.'.refnoncoreusers');
		
		$query = $this->db->get()->result();

		if ($query) {
			foreach ($query as $row) {
				$tmp[strtoupper($row->username)] = $row->fullname;
			}
		}

		return $tmp;
	}

	function get_user_fullname($username,$userid = 0) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('fullname');

		if ($userid == 0) {
			$this->db->where('username', $username);
		} else {
			$this->db->where('Id', $userid);
		}
		
		$this->db->from($applat_db.'.refnoncoreusers');
		
		$query = $this->db->get()->result();

		if ($query) {
			return $query[0]->fullname;
		} else {
			return NULL;
		}
	}

	function get_field_to_review($data_menu,$field_id) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('Id');
		$this->db->where('url', $data_menu['url']);
		$this->db->where('Id', $field_id);
		$this->db->from('v_fieldstoreview');
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;	
	}

	function loaddata_dropdown_look_up_value($look_up_data) {
		$component_id = $look_up_data['component_id'];
		$value = $look_up_data['value'];
		
		///////// Get ID Field Source as data selection items /////////////////////
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('component_id_data');
		$this->db->where('form_component_id', $component_id);
		$this->db->from($applat_db.'.refselectiondata');
		
		$row = array();
		$row =  $this->db->get()->result()[0];

		$source_field_id = $row->component_id_data;
		///////////////////////////////////////////////////////////////////////////////////////////////
		
		///////// Get field & table name of data selection items source ///////
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('field_name,full_table_name,sub_form_id,dbname');
		$this->db->from($applat_db.'.refforms');
		$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
		$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
		$this->db->where($applat_db.'.refforms.Id', $source_field_id);
		
		$row = array();
		$row =  $this->db->get()->result()[0];

		$field_name = $row->field_name;
		$table_name = $row->dbname.'.'.$row->full_table_name;

		if ($row->sub_form_id != NULL) {
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('full_table_name,dbname');
			$this->db->from($applat_db.'.refsubform_menu');
			$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refsubform_menu.menu_id');
			$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
			$this->db->where($applat_db.'.refsubform_menu.Id', $row->sub_form_id);
			
			$row = array();
			$row =  $this->db->get()->result()[0];

			$table_name = $row->dbname.'.'.$row->full_table_name;
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		
		//////// Return value from table of selected dropdown ///////////////////
		$this->db->select('Id,'.$field_name);
		$this->db->from($table_name);
		$this->db->where($table_name.'.Id in ('.$value.')', NULL);

		$query['query'] = $this->db->get()->result_array();
		$query['field_name'] = $field_name;

		return $query;

		//////////////////////////////////////////////////////////////////////////////////////////////
	}

	function look_up_value($look_up_data,$data_menu,$formtype) {
		$component_id = $look_up_data['component_id'];
		$value = $look_up_data['value'];
		
		///////// Get ID Field Source as data selection items /////////////////////
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('component_id_data');
		$this->db->where('form_component_id', $component_id);
		$this->db->from($applat_db.'.refselectiondata');
		
		$row = array();
		$row =  $this->db->get()->result()[0];

		$source_field_id = $row->component_id_data;
		///////////////////////////////////////////////////////////////////////////////////////////////
		
		///////// Get field & table name of data selection items source ///////
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('field_name,full_table_name,sub_form_id');
		$this->db->from($applat_db.'.refforms');
		$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
		$this->db->where($applat_db.'.refforms.Id', $source_field_id);
		
		$row = array();
		$row =  $this->db->get()->result()[0];

		$field_name = $row->field_name;
		$table_name = $row->full_table_name;

		if ($row->sub_form_id != NULL) {
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('full_table_name');
			$this->db->from($applat_db.'.refsubform_menu');
			$this->db->where($applat_db.'.refsubform_menu.Id', $row->sub_form_id);
			
			$row = array();
			$row =  $this->db->get()->result()[0];

			$table_name = $row->full_table_name;
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
		
		//////// Return value from table of selected dropdown ///////////////////
		$this->db->select($field_name);
		$this->db->from($table_name);
		$this->db->where($table_name.'.Id', $value);

		$query = $this->db->get()->result();

		if ($query) {
			$row = array();
			$row =  $query[0];
			$tmp = $row->$field_name;

			return $tmp;	
		} else {
			return '';
		}
		//////////////////////////////////////////////////////////////////////////////////////////////
	}
	
	function loaddata_dropdown_look_up_item($look_up_item) {
		
		$component_id = $look_up_item['component_id'];
		$value = $look_up_item['value'];
		
		//////// Return value from item selection table of selected dropdown ///////////////////
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('item_value, item_text');
		$this->db->from($applat_db.'.refselectionitems');
		$this->db->where('item_value in ('.$value.')', NULL);
		$this->db->where('form_component_id', $component_id);
		
		$query = $this->db->get()->result();
		
		return $query;

		//////////////////////////////////////////////////////////////////////////////////////////////
	}

	function look_up_item($look_up_item) {
		$tmp = '';

		$component_id = $look_up_item['component_id'];
		$value = $look_up_item['value'];
		
		//////// Return value from item selection table of selected dropdown ///////////////////
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('item_text');
		$this->db->from($applat_db.'.refselectionitems');
		$this->db->where('item_value', $value);
		$this->db->where('form_component_id', $component_id);
		
		$query = $this->db->get()->result();

		if ($query) {
			$tmp = $query[0]->item_text;	
		}
		
		return $tmp;
		//////////////////////////////////////////////////////////////////////////////////////////////
	}

	function loaddata_dropdown_look_up_user($look_up_user) {
		$value = $look_up_user['value'];
		
		//////// Return value from item selection table of selected dropdown ///////////////////
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		/*$this->db->distinct();
		$this->db->select('user_id, fullname');
		$this->db->from($applat_db.'.v_menu');
		$this->db->where('user_id in ('.$value.')', NULL);*/

		$this->db->distinct();
		$this->db->select('Id user_id, fullname');
		$this->db->from($applat_db.'.refnoncoreusers');
		$this->db->where('Id in ('.$value.')', NULL);
		
		$query = $this->db->get()->result();

		return $query;
		//////////////////////////////////////////////////////////////////////////////////////////////
	}

	function look_up_user($look_up_user) {
		$value = $look_up_user['value'];
		
		//////// Return value from item selection table of selected dropdown ///////////////////
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('fullname');
		$this->db->from('v_menu');
		$this->db->where('user_id', $value);
		
		$query = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		if (count($query) >= 1) {
			$row = array();
			$row =  $query[0];
			return $row->fullname;	
		}
			return '';
		//////////////////////////////////////////////////////////////////////////////////////////////
	}
	
	function get_data_parent_menu($menu_id) {
		$title_parent = '';
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('title');
		$this->db->where('Id', $menu_id);
		
		$query =  $this->db->get('refmenu')->result();

		$this->db->query('use '.$current_db);
		
		if (count($query) >=1) {
			$row = $query[0];
			$title_parent = $row->title;
		}
		
		return $title_parent;
	}
	
	function get_data_specific_component($component_id) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select();
		$this->db->where('Id', $component_id);
		$this->db->from('refforms');
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return  $tmp;
	}
	
	function get_extended_data_detail($data_menu,$task,$formtype,$extended_data_field_name,$full_table_name,$data_id,$is_data_subform,$search_by_column = 'Id') {
		$this->db->select();
		$this->db->from($full_table_name);
		if (!$is_data_subform) {
			$this->db->where('Id',$data_id);	
		}else{
			$this->db->where($search_by_column,$data_id);
		}
		
		$tmp = $this->db->get()->result();

		return  $tmp;
	}
	
	function get_data_menu($menu_id) {
		$tmp['id'] = '';
		$tmp['title'] = '';
		$tmp['title_parent'] = '';
		$tmp['full_table_name'] = '';
		$tmp['app_code'] = '';
		$tmp['is_masterdata'] = NULL;
		$tmp['is_workflowdata'] = NULL;
		$tmp['is_transdata'] = NULL;
		$tmp['is_report'] = NULL ;
		$tmp['is_approval'] = NULL ;
		$tmp['url'] = NULL ;
		$tmp['js_script_page'] = NULL ;
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);
		
		$this->db->select('refmenu.Id');
		$this->db->select('title,parent_id,full_table_name,app_code,is_masterdata,is_workflowdata,is_transdata,is_report,is_approval,url,js_script_page');
		$this->db->where('url', $menu_id);
		$this->db->or_where('refmenu.Id', $menu_id);
		$this->db->from('refmenu');
		$this->db->join('refapps','refapps.Id = refmenu.app_id');
		
		$query =  $this->db->get()->result();
		
		$this->db->query('use '.$current_db);
		
		if (count($query) >=1) {
			$row = $query[0];
			$tmp['id'] = $row->Id;
			$tmp['title'] = $row->title;
			$tmp['title_parent'] = $this->get_data_parent_menu($row->parent_id);
			$tmp['full_table_name'] = $row->full_table_name;
			$tmp['app_code'] = $row->app_code;
			$tmp['is_masterdata'] = $row->is_masterdata;
			$tmp['is_workflowdata'] = $row->is_workflowdata;
			$tmp['is_transdata'] = $row->is_transdata;
			$tmp['is_report'] = $row->is_report;
			$tmp['is_approval'] = $row->is_approval;
			$tmp['url'] = $row->url;
			$tmp['js_script_page'] = $row->js_script_page;
		}
		
		return $tmp;
	}
	
	function count_rows($data_menu,$datapost,$formtype,$subform_name) {
		$count_field = 0;
		$pending_id = "-1";
		
		$processed_requests_id = '-1';
		$search_requestor = "";

		$query = $this->get_grid_fields($data_menu,$formtype,$subform_name);

		/// search within extended field //////////////
		$extended_field_search_string = '';

		if ($data_menu['is_approval'] == 1) {
			$datasession = $this->session->userdata('logged_in');
			$allow_view_all_workflowdata = $this->allow_user_view_all_workflowdata($datasession['username']);
			$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);
		}

		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				$extended_field_search = '';
				if (is_numeric($row->field_name)) {
					$extended_field_id = $row->field_name;

					$app_init = $this->app_init->app_init();
					$applat_db = $app_init['applat_db_name'];
					
					$this->db->select('full_table_name,field_name,control_type,field_type,item_source');
					$this->db->from($applat_db.'.refforms');
					$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
					$this->db->where($applat_db.'.refforms.Id',$extended_field_id);
					$this->db->where('(field_type = "varchar" or control_type = "dropdown")',NULL);

					$query_extended_table = $this->db->get()->result();

					if (count($query_extended_table) == 1) {
						if ($query_extended_table[0]->control_type == "dropdown") {
							$dropdown_search_string = "";
							$source_table = "";
							$source_field = "";
							$select_field = "";

							switch ($query_extended_table[0]->item_source) {
								case 'datatable':
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$this->db->select($applat_db.'.refmenu.full_table_name, '.$applat_db.'.refforms.field_name');
									$this->db->from($applat_db.'.refselectiondata');
									$this->db->join($applat_db.'.refforms',$applat_db.'.refforms.Id = '.$applat_db.'.refselectiondata.component_id_data');
									$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
									$this->db->where($applat_db.'.refselectiondata.form_component_id', $extended_field_id);

									$query_dropdown_source_table =  $this->db->get()->result();

									$source_table = $query_dropdown_source_table[0]->full_table_name;
									$source_field = $query_dropdown_source_table[0]->field_name;
									$select_field = "Id";
									break;
								case 'appusers':
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$source_table = $applat_db.".refnoncoreusers";
									$source_field = $applat_db.".refnoncoreusers.fullname";
									$select_field = "Id";

									break;
								default:
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$source_table = $applat_db.".refselectionitems";
									$source_field = $applat_db.".refselectionitems.item_text";
									$select_field = "item_value";
									break;
							}

							$this->db->select($select_field);
							$this->db->from($source_table);
							$this->db->like($source_field,$datapost['search']);

							if ($row->item_source == "manageditems") {
								$this->db->where($source_table.".form_component_id",$extended_field_id);
							}
							
							$query_dropdown_source_data =  $this->db->get()->result();

							if ($query_dropdown_source_data) {
								$id_data_dropdown_string = "";

								foreach ($query_dropdown_source_data as $row_dropdown_source_data) {
									$id_data_dropdown_string .= ($id_data_dropdown_string == "") ? $row_dropdown_source_data->Id : ",".$row_dropdown_source_data->Id ;
								}
								
								$dropdown_search_string .= ($dropdown_search_string == "") ? $query_extended_table[0]->field_name." in (".$id_data_dropdown_string.")" : " or ".$query_extended_table[0]->field_name." in (".$id_data_dropdown_string.")" ;

								$this->db->select('Id');
								$this->db->from($query_extended_table[0]->full_table_name);
								$this->db->where($dropdown_search_string,NULL);

								$query_extended_search_result = $this->db->get()->result();
							}
						}

						if ($query_extended_table[0]->field_type == "varchar") {
							$this->db->select('Id');
							$this->db->from($query_extended_table[0]->full_table_name);
							$this->db->like($query_extended_table[0]->field_name,$datapost['search']);
							
							$query_extended_search_result = $this->db->get()->result();
						}
						
						foreach ($query_extended_search_result as $row_extended_result) {
							$extended_field_search = ($extended_field_search == '')? $row_extended_result->Id : $extended_field_search.','.$row_extended_result->Id;
						}

						if ($extended_field_search != '') {
							$extended_field_search_tmp = '';
							$data_ids_array = explode(',',$extended_field_search);
							$data_ids_chunk = array_chunk($data_ids_array,25);
							
							foreach($data_ids_chunk as $data_ids_search)
							{
								$data_ids_string = '';
								foreach ($data_ids_search as $key => $value) {
									$data_ids_string .= ($data_ids_string == '') ? $value : ','.$value ;
								}

							    $extended_field_search_tmp .= ($extended_field_search_tmp == '') ? $query_extended_table[0]->full_table_name.'_id in ('.$data_ids_string.')' : ' or '.$query_extended_table[0]->full_table_name.'_id in ('.$data_ids_string.')';
							}

							if ($extended_field_search_tmp != '') $extended_field_search_tmp= '('.$extended_field_search_tmp.')';

							$extended_field_search_string = ($extended_field_search_string == '')? $extended_field_search_tmp : $extended_field_search_string.' or '.$extended_field_search_tmp;
						}
					}
				}
			}
		}

		if ($data_menu['is_approval'] == 1 && $formtype == 'mainform') {
			$datasession = $this->session->userdata('logged_in');

			$this->db->select('Id');
			$this->db->from('v_id_request_pending_approval');
			$this->db->where('username',$datasession['username']);

			$pending_ids = $this->db->get()->result();

			foreach ($pending_ids as $row_pending_id) {
				$pending_id .= ($pending_id == '') ? $row_pending_id->Id : ','.$row_pending_id->Id;
			}
		}
		///////////////////////////////////////////////
		
		if ($data_menu['is_workflowdata'] == 1) {
			///// data requests can only be viewed by the user who created it EXCEPT for those who are allowed to view all
			if ($formtype != 'subform') {
				$datasession = $this->session->userdata('logged_in');
				$allow_view_all_workflowdata = $this->allow_user_view_all_workflowdata($datasession['username']);
			}
			///////////////////////////////////////////////////////////////////////////////////////////
		}

		//// Search by reference number /////////////////
		$search_by_reference_number = "";
		$additional_forms = $this->shared_variables->display_reference_column;
		
		if ($formtype == 'mainform' && $datapost['search'] != '' and ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || in_array($data_menu['url'],$additional_forms))) {

			$this->db->select($data_menu['full_table_name'].'.Id');
			$this->db->from($data_menu['full_table_name']);
			$this->db->like('request_reference_number',$datapost['search']);

			$query_search_reference = array();
			$query_search_reference = $this->db->get()->result();

			foreach ($query_search_reference as $row) {
					$search_by_reference_number .= ($search_by_reference_number == "")? $row->Id : ','.$row->Id;
			}
		}
		//// [END] Serach by reference number [END] /////

		#search filter for dropdown fields
		$dropdown_search_string = "";
		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				if ($row->control_type == "dropdown") {
					$source_table = "";
					$source_field = "";
					$select_field = "";

					switch ($row->item_source) {
						case 'datatable':
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$this->db->select($applat_db.'.refmenu.full_table_name, '.$applat_db.'.refforms.field_name, '.$applat_db.'.refapps.dbname');
							$this->db->from($applat_db.'.refselectiondata');
							$this->db->join($applat_db.'.refforms',$applat_db.'.refforms.Id = '.$applat_db.'.refselectiondata.component_id_data');
							$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
							$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
							$this->db->where($applat_db.'.refselectiondata.form_component_id', $row->Id);

							$query_dropdown_source_table =  $this->db->get()->result();

							$source_table = $query_dropdown_source_table[0]->dbname.'.'.$query_dropdown_source_table[0]->full_table_name;
							$source_field = $query_dropdown_source_table[0]->field_name;
							$select_field = "Id";
							break;
						case 'appusers':
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$source_table = $applat_db.".refnoncoreusers";
							$source_field = $applat_db.".refnoncoreusers.fullname";
							$select_field = "Id";

							break;
						default:
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$source_table = $applat_db.".refselectionitems";
							$source_field = $applat_db.".refselectionitems.item_text";
							$select_field = "item_value as Id";
							break;
					}
					
					$this->db->select($select_field);
					$this->db->from($source_table);
					$this->db->like($source_field,$datapost['search']);

					if ($row->item_source == "manageditems") {
						$this->db->where($source_table.".form_component_id",$row->Id);
					}
					
					$query_dropdown_source_data =  $this->db->get()->result();

					if ($query_dropdown_source_data) {
						$id_data_dropdown_string = "";

						foreach ($query_dropdown_source_data as $row_dropdown_source_data) {
							$id_data_dropdown_string .= ($id_data_dropdown_string == "") ? "'".$row_dropdown_source_data->Id."'" : ",'".$row_dropdown_source_data->Id."'" ;
						}
						
						$dropdown_search_string .= ($dropdown_search_string == "") ? $row->field_name." in (".$id_data_dropdown_string.")" : " or ".$row->field_name." in (".$id_data_dropdown_string.")" ;
					}
				}
			}

			$app_init = $this->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];
				
				$this->db->select('username');
				$this->db->from($applat_db.'.refnoncoreusers');
				$this->db->like($applat_db.'.refnoncoreusers.fullname', $datapost['search']);
				
				$query_requestor =  $this->db->get()->result();
				
				if ($query_requestor) {
					foreach ($query_requestor as $row_requestor) {
						$search_requestor .= ($search_requestor == "") ? "'".$row_requestor->username."'" : ",'".$row_requestor->username."'" ;
					}
				}	
		}		
		#

		$this->db->select('count('.$data_menu['full_table_name'].'.Id) as count_rows');
		
		$bracket_opened = FALSE;

		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				if (!is_numeric($row->field_name)) {
					$count_field++;

					if (!$bracket_opened) {
						$bracket_opened = TRUE;
						$this->db->group_start();
					}
				
					if ($count_field == 1) $this->db->like($data_menu['full_table_name'].'.'.$row->field_name,$datapost['search']); else $this->db->or_like($data_menu['full_table_name'].'.'.$row->field_name,$datapost['search']);
				}
			}

			if ($extended_field_search_string != '') {
				$this->db->or_where($extended_field_search_string,NULL);
			}

			#dropdown search
			if ($dropdown_search_string != "") {
				$this->db->or_where($dropdown_search_string,NULL);
			}
			#

			//// Search by reference number /////////////////
			if ($search_by_reference_number != "") {
				$this->db->or_where($data_menu['full_table_name'].'.Id in ('.$search_by_reference_number.')',NULL);
			}
			//// [END] Serach by reference number [END] /////
			
			if ($search_requestor != "") {
				$this->db->or_where($data_menu['full_table_name'].'.createby in ('.$search_requestor.')', NULL);
			}

			if ($bracket_opened) {
				$this->db->group_end();
			}
		}
		
		$this->db->where($data_menu['full_table_name'].'.isdelete', 0);

		if ($data_menu['is_workflowdata']) {
			///// data requests can only be viewed by the user who created it EXCEPT for admin /////////////////
			if ($formtype != 'subform') {
				if (!$allow_view_all_workflowdata) {
					$datasession = $this->session->userdata('logged_in');
					$this->db->where('createby', $datasession['username']);	
				}
			}
			///////////////////////////////////////////////////////////////////////////////////////////
		}

		#display requested based on selected status filter, if form is workflow or approval
		if ($data_menu['is_approval'] == 1 && $formtype == 'mainform') {
			$datasession = $this->session->userdata('logged_in');
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];

			$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnworkflowseq where username = "'.$datasession['username'].'" and status is null and is_data_displayed = 1 and menu_id in ('.$requestor_form_menu_id.')';

			if ($this->session->userdata($data_menu['url'].'_show_request')) {
				$status_filter = $this->session->userdata($data_menu['url'].'_show_request');

				if (in_array($status_filter, array('Full Approve','I have processed'))) {$status_filter = 'Approve';}

				if ($status_filter != 'Waiting Approval') {
					$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where createby = "'.$datasession['username'].'" and status = "'.$status_filter.'" and menu_id in ('.$requestor_form_menu_id.')';

						if ($allow_view_all_workflowdata) {
							$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where status = "'.$status_filter.'" and menu_id in ('.$requestor_form_menu_id.')';
						}

					if ($status_filter == 'Revise') {
						$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where createby = "'.$datasession['username'].'" and (status = "'.$status_filter.'" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';

						if ($allow_view_all_workflowdata) {
							$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where (status = "'.$status_filter.'" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';	
						}
					}
				}
			}

			//$this->db->where($data_menu['full_table_name'].'.Id in ('.$pending_id.')', NULL);
			$this->db->where($data_menu['full_table_name'].'.Id in ('.$processed_requests_id_query_string.')', NULL);
		}

		if ($this->session->userdata($data_menu['url'].'_show_request') && ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1) && $formtype = 'mainform') {

			$status_filter = str_replace("I have processed","All", $this->session->userdata($data_menu['url'].'_show_request'));

			if ($status_filter != 'All' && $formtype == 'mainform') {
				$this->db->where('status', $status_filter);
			}
		}
		
		$this->db->from($data_menu['full_table_name']);

		#filter data by activity type
		if ($formtype == 'mainform' && ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1)) {
			$this->db->where($data_menu['full_table_name'].'.activity_type',$this->filter_data_by_activity_type($data_menu['url']));
		}
		#[END OF] filter data by activity type
		
		#customized fot MUNDIPHARMA, display data for update bpom status form
		if (strtolower($data_menu['url']) == 'trans_frm_bpomgov_status' && $formtype == 'mainform') {
			$this->db->where($data_menu['full_table_name'].'.is_bpom_required',1);
			$this->db->where($data_menu['full_table_name'].'.status','Full Approve');
		}
		#
		#customized fot MUNDIPHARMA, display data for update regional status form
		if (strtolower($data_menu['url']) == 'trans_frm_regional_status' && $formtype == 'mainform') {
			$this->db->where($data_menu['full_table_name'].'.is_regional_required',1);
			$this->db->where($data_menu['full_table_name'].'.status','Full Approve');
			$this->db->where('(coalesce('.$data_menu['full_table_name'].'.is_bpom_required,0) = 0 or ('.$data_menu['full_table_name'].'.is_bpom_required = 1 and '.$data_menu['full_table_name'].'.bpom_process_status = "Approved"))',NULL);
		}
		#
		
		//echo $this->db->get_compiled_select();exit;
		
		$query = $this->db->get()->result();

		if ($query) {
			return $query[0]->count_rows;
		} else {
			return 0;
		}
	}

	function get_extended_default_value($extended_data_id,$extended_field_default_value) {
		$tmp = NULL;

		$extended_components_field_name = $extended_field_default_value[0]->extended_components_field_name;
		$extended_components_full_table_name = $extended_field_default_value[0]->extended_components_full_table_name;

		$this->db->select($extended_components_field_name);
		$this->db->from($extended_components_full_table_name);
		$this->db->where('Id', $extended_data_id);
		
		$query =  $this->db->get()->result();

		if ($query) {
			$tmp = $query[0]->$extended_components_field_name;
		}

		return $tmp;
	}

	function get_extended_field_default_value($form_field_id) {
		/////////////////// Get extended field for default value //////////////////////////////////////////
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('extended_components_field_name,extended_components_full_table_name');
		$this->db->from('v_extended_field_default_value');
		$this->db->where('Id', $form_field_id);
		
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $query;
		////////////////////////////////////////////////////////////////////////////////////////////////
	}

	function get_checkbox_selections_origin_table($form_component_id) {
		/////////////////// Get ID data for selection //////////////////////////////////////////
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('component_id_data');
		$this->db->from('refselectiondata');
		$this->db->where('refselectiondata.form_component_id', $form_component_id);
		$this->db->where('refselectiondata.isdelete', 0);
		
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);

		$component_id_data = $query[0]->component_id_data;
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		/////////////////// Get ID field name and table ////////////////////////////////////
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('field_name,full_table_name,sub_form_id');
		$this->db->from('refforms');
		$this->db->join('refmenu','refmenu.Id = refforms.menu_id');
		$this->db->where('refforms.Id', $component_id_data);
		$this->db->where('refforms.isdelete', 0);
		
		$query = array();
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);
		
		$field_name = $query[0]->field_name;
		$full_table_name = $query[0]->full_table_name;

		if ($query[0]->sub_form_id != NULL) {
			$current_conn = $this->db;
			$current_db = $current_conn->database;
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->query('use '.$applat_db);

			$this->db->select('field_name,full_table_name,sub_form_id');
			$this->db->from('refforms');
			$this->db->join('refsubform_menu','refsubform_menu.Id = refforms.sub_form_id');
			$this->db->where('refforms.Id', $component_id_data);
			$this->db->where('refforms.isdelete', 0);

			$query = array();
			$query =  $this->db->get()->result();
			$field_name = $query[0]->field_name;
			$full_table_name = $query[0]->full_table_name;

			$this->db->query('use '.$current_db);
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		return $full_table_name;
	}

	#customized for MUNDIPHARMA, check if approver is on at least the last 2 approvers
	function check_approver_sequence_level_1_2($data_menu, $data_id, $datasession) {
		$form_request_id = $this->wf->get_origin_data_menu_id($data_menu);
		
		$this->db->select('Id');
		$this->db->from($data_menu['full_table_name']);
		$this->db->where('hash_link', $data_id);

		$query =  $this->db->get()->result();

		$data_id = $query[0]->Id;

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('Id');
		$this->db->from($applat_db.'.trnworkflowseq');
		$this->db->where('menu_id', $form_request_id);
		$this->db->where('data_id', $data_id);
		$this->db->where('username', $datasession['username']);
		$this->db->where('is_data_displayed', 1);
		$this->db->where('status is null', NULL);
		$this->db->where('wflayer_sequence in (1,2)', NULL);

		$query =  $this->db->get()->result();

		if ($query) return TRUE; else return FALSE;
	}
	#

	#customized for Mundipharma 2020, ePMAP Project, get specific brand drop selection for submitted request
	function get_brand_id($data_menu, $hash_link) {
		$this->db->select('product_prefix');
		$this->db->from($data_menu['full_table_name']);
		$this->db->where('hash_link', $hash_link);

		$query =  $this->db->get()->result();

		if (!$query) {
			return array();
		} else {
			$product_prefix = $query[0]->product_prefix;

			if ($product_prefix == '') {
				return array();
			} else {
				$app_init = $this->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];

				$this->db->select('Id');
				$this->db->from($applat_db.'.cmbr_ref_product');
				$this->db->where('isdelete', 0);
				$this->db->where('epmap_prefix', $product_prefix);

				$query =  $this->db->get()->result();

				return $query;
			}
		}
	}
	#[END OF] customized for Mundipharma 2020, ePMAP Project, get specific brand drop selection for submitted request

	function get_selectiondatatableitems($form_component_id,$data_menu = array(),$main_id = 0) {
		/////////////////// Get ID data for selection //////////////////////////////////////////
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('component_id_data,order_by,order_sort,field_name,full_table_name,sub_form_id,dbname');
		$this->db->from($applat_db.'.refselectiondata');
		$this->db->join($applat_db.'.refforms',$applat_db.'.refforms.Id = '.$applat_db.'.refselectiondata.component_id_data');
		$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
		$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
		$this->db->where($applat_db.'.refselectiondata.form_component_id', $form_component_id);
		$this->db->where($applat_db.'.refselectiondata.isdelete', 0);
		
		$query =  $this->db->get()->result();

		$component_id_data = $query[0]->component_id_data;
		$order_by = ($query[0]->order_by != '') ? $query[0]->order_by : 'order_by_selected_field';
		$order_sort = ($query[0]->order_sort != '') ? strtoupper($query[0]->order_sort) : 'ASC';
		$field_name = $query[0]->field_name;
		$full_table_name = $query[0]->dbname.'.'.$query[0]->full_table_name;

		if ($query[0]->sub_form_id != NULL) {
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('field_name,full_table_name,sub_form_id,dbname');
			$this->db->from($applat_db.'.refforms');
			$this->db->join($applat_db.'.refsubform_menu',$applat_db.'.refsubform_menu.Id = '.$applat_db.'.refforms.sub_form_id');
			$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
			$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
			$this->db->where($applat_db.'.refforms.Id', $component_id_data);
			$this->db->where($applat_db.'.refforms.isdelete', 0);

			$query = array();
			$query =  $this->db->get()->result();
			$field_name = $query[0]->field_name;
			$full_table_name = $query[0]->dbname.'.'.$query[0]->full_table_name;

		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////
		
		/////////////////// Get Data ///////////////////////////////////////////////////////////////
		$query = array();

		$this->db->select($full_table_name.'.Id as item_value,'.$full_table_name.'.'.$field_name.' as item_text');
		
		$this->db->from($full_table_name);
		
		$this->db->where($full_table_name.'.isdelete', 0);

		if ($order_by == 'order_by_selected_field') {
			$this->db->order_by($full_table_name.'.'.$field_name,$order_sort);
		} else {
			$this->db->order_by($full_table_name.'.Id',$order_sort);
		}
		
		$query['query'] =  $this->db->get()->result();

		////////////////////////////////////////////////////////////////////////////////////////////////
		
		return $query;
	}
	
	function get_selectiondatataappusers($app_id,$form_field,$data_menu,$datasession,$formtype,$main_id,$task) {

		/////////////////// Get App User Data ///////////////////////////////////////////////////////////////
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('user_id as item_value, fullname as item_text');
		$this->db->from('v_menu');
		$this->db->where('app_id', $app_id);

		##customized fot MUNDIPHARMA, display specific users for MSL dan Regulatory PIC selection
		if (strtolower($data_menu['url']) == 'msl_regulatory_setting' && $formtype == 'subform') {
			if ($form_field->field_name == 'msl_user_id') {
				$this->db->where('usergroup_name', 'Medical Scientific Liaison user group');
			}
			if ($form_field->field_name == 'regulatory_user_id') {
				$this->db->where('usergroup_name', 'epmap - Regulatory Affairs Manager');
			}
		}
		#

		$this->db->order_by('fullname','ASC');
		
		$query = array();
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);
		////////////////////////////////////////////////////////////////////////////////////////////////
		//print_r($query);exit;
		return $query;
	}
	
	function get_selectionitems($form_component_id) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('item_value,item_text');
		$this->db->from('refselectionitems');
		$this->db->where('refselectionitems.form_component_id', $form_component_id);
		$this->db->where('refselectionitems.isdelete', 0);
		$this->db->order_by('refselectionitems.order_index','ASC');
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);
		
		return $tmp;
	}
	
	function get_draft_id($full_table_name) {
		$draft_id = 0;
		$not_exist = FALSE;
		
		while(!$not_exist) {
			$draft_id = mt_rand();
			$this->db->select('Id');
			$this->db->from($full_table_name);
			$this->db->where($full_table_name.'.draft_id', $draft_id);
			$this->db->where($full_table_name.'.isdelete', 0);
			
			$query =  $this->db->get()->result();
			if (count($query) >= 1) $not_exist = FALSE; else $not_exist = TRUE;	
		}
		return $draft_id;
	}	
	
	function get_form_fields_for_data_processing($menu_id,$formtype,$task,$data_id,$sub_form_name = '') {
		$sub_form_id = 0;
		
		if ($formtype == 'subform') {
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->select('Id');
			$this->db->from($applat_db.'.refsubform_menu');
			$this->db->where($applat_db.'.refsubform_menu.menu_id', $menu_id);
			$this->db->where($applat_db.'.refsubform_menu.subform_name', str_replace("zzz", "/", str_replace('%20',' ',$sub_form_name)));
			$this->db->where($applat_db.'.refsubform_menu.isdelete', 0);
			
			$row = $this->db->get()->result()[0];

			$sub_form_id = $row->Id;
		}
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('refforms.*,refmenu.full_table_name,refsubform_menu.full_table_name subform_full_table_name');
		$this->db->from($applat_db.'.refforms');
		$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
		$this->db->join($applat_db.'.refsubform_menu',$applat_db.'.refsubform_menu.Id = '.$applat_db.'.refforms.sub_form_id','left');
		$this->db->where($applat_db.'.refforms.menu_id', $menu_id);
		$this->db->where($applat_db.'.refforms.isdelete', 0);
		$this->db->where($applat_db.'.refforms.control_name is not null',NULL);
		$this->db->where($applat_db.'.refforms.is_disabled is null',NULL);
		if ($formtype == 'mainform') $this->db->where($applat_db.'.refforms.sub_form_id is null', NULL);
		if ($formtype == 'subform') $this->db->where($applat_db.'.refforms.sub_form_id', $sub_form_id);

		$this->db->order_by($applat_db.'.refforms.order_index','ASC');
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}
	
	function get_form_fields($menu_id,$formtype,$task,$data_id,$sub_form_name = '') {
		$sub_form_id = 0;
		
		if ($formtype == 'subform') {
			$current_conn = $this->db;
			$current_db = $current_conn->database;
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->query('use '.$applat_db);

			$this->db->select('Id');
			$this->db->from('refsubform_menu');
			$this->db->where('refsubform_menu.menu_id', $menu_id);
			$this->db->where('refsubform_menu.subform_name', str_replace("zzz", "/",str_replace('%20',' ',$sub_form_name)));
			$this->db->where('refsubform_menu.isdelete', 0);
			
			$row = $this->db->get()->result()[0];
			$sub_form_id = $row->Id;

			$this->db->query('use '.$current_db);
		}
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select();
		$this->db->from('refforms');
		$this->db->where('refforms.menu_id', $menu_id);
		$this->db->where('refforms.isdelete', 0);
		if ($formtype == 'mainform') $this->db->where('refforms.sub_form_id is null', NULL);
		if ($formtype == 'subform') $this->db->where('refforms.sub_form_id', $sub_form_id);

		$this->db->order_by('refforms.order_index','ASC');
		
		$tmp = $this->db->get()->result();

		$this->db->query('use '.$current_db);

		return $tmp;
	}
	
	function validate_subform_data($data_id,$data_menu,$data_subform,$task) {

		$data_id = $this->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

		$this->db->select('Id');
		$this->db->from($data_subform->full_table_name);
		$this->db->where($data_menu['full_table_name'].'_id', $data_id);
		$this->db->where('isdelete', 0);
		
		$query = $this->db->get()->result();
		
		return (count($query) >= 1)? "" : "disabled";
	}
	
	function get_data_subform_by_id($id) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select();
		$this->db->from($applat_db.'.refsubform_menu');
		$this->db->where('Id', $id);
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}
	
	function get_data_subform($menu_id,$subform_name = '') {
		$subform_name = str_replace("zzz", "/", $subform_name);

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select();
		$this->db->from($applat_db.'.refsubform_menu');
		$this->db->where($applat_db.'.refsubform_menu.menu_id', $menu_id);
		$this->db->where($applat_db.'.refsubform_menu.isdelete', 0);
		if ($subform_name != '') { $this->db->where($applat_db.'.refsubform_menu.subform_name', str_replace("zzz", "/",str_replace('%20',' ',$subform_name))); }
		$this->db->order_by($applat_db.'.refsubform_menu.order_index','ASC');
		
		//echo $this->db->get_compiled_select();exit;
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}
	
	function get_grid_fields($data_menu,$formtype,$subform_name) {
		
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$subform_id = $query[0]->Id;
		}
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('field_name,field_type,column_header,control_type,item_source,refforms.Id,upload_path');

		$this->db->from($applat_db.'.refdatagrid');
		$this->db->join($applat_db.'.refforms','refforms.Id = '.$applat_db.'.refdatagrid.form_component_id');
		$this->db->where($applat_db.'.refdatagrid.menu_id', $data_menu['id']);
		if ($formtype != 'subform') {
			$this->db->where($applat_db.'.refdatagrid.subform_id is null', NULL);	
		}else{
			$this->db->where($applat_db.'.refdatagrid.subform_id', $subform_id);
		}
		$this->db->where('refdatagrid.isdelete', 0);
		$this->db->where('refforms.isdelete', 0);
		$this->db->order_by('refdatagrid.order_index','ASC');
		
		$tmp = $this->db->get()->result();

		return $tmp;
	}

	function allow_user_view_all_workflowdata($username) {
		$tmp = FALSE;

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('allow_view_all_workflowdata');
		$this->db->from($applat_db.'.refnoncoreusers');
		$this->db->where('username', $username);
		$this->db->where('allow_view_all_workflowdata', 1);
		
		$query =  $this->db->get()->result();

		if (count($query) >= 1) {
			$tmp = TRUE;
		}

		return $tmp;
	}

	
	function load_data($data_menu,$datapost,$limit,$offset,$formtype,$subform_name,$main_id) {
		$count_field = 0;
		$table_name = "";
		$pending_id = "-1";
		$search_requestor = "";

		if ($data_menu['is_approval'] == 1) {
			$datasession = $this->session->userdata('logged_in');
			$allow_view_all_workflowdata = $this->allow_user_view_all_workflowdata($datasession['username']);
			$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);
		}
		
		if ($formtype != 'subform') {
			$table_name = $data_menu['full_table_name'];
		}else{
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$table_name = $query[0]->full_table_name;
			$main_id = $this->get_data_id_from_hash_link($main_id,$data_menu['full_table_name']);
		}
		
		$open_bracket = FALSE;
		$opened_bracket = FALSE;
		$query = $this->get_grid_fields($data_menu,$formtype,$subform_name);

		/// search within extended field //////////////
		$extended_field_search_string = '';
		
		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				$extended_field_search = '';
				if (is_numeric($row->field_name)) {
					$extended_field_id = $row->field_name;

					$app_init = $this->app_init->app_init();
					$applat_db = $app_init['applat_db_name'];
					
					$this->db->select('full_table_name,field_name,control_type,item_source,field_type');
					$this->db->from($applat_db.'.refforms');
					$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
					$this->db->where($applat_db.'.refforms.Id',$extended_field_id);
					$this->db->where('(field_type = "varchar" or control_type = "dropdown")',NULL);

					$query_extended_table = $this->db->get()->result();

					if (count($query_extended_table) == 1) {
						if ($query_extended_table[0]->control_type == "dropdown") {
							$dropdown_search_string = "";
							$source_table = "";
							$source_field = "";
							$select_field = "";

							switch ($query_extended_table[0]->item_source) {
								case 'datatable':
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$this->db->select($applat_db.'.refmenu.full_table_name, '.$applat_db.'.refforms.field_name');
									$this->db->from($applat_db.'.refselectiondata');
									$this->db->join($applat_db.'.refforms',$applat_db.'.refforms.Id = '.$applat_db.'.refselectiondata.component_id_data');
									$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
									$this->db->where($applat_db.'.refselectiondata.form_component_id', $extended_field_id);

									$query_dropdown_source_table =  $this->db->get()->result();

									$source_table = $query_dropdown_source_table[0]->full_table_name;
									$source_field = $query_dropdown_source_table[0]->field_name;
									$select_field = "Id";
									break;
								case 'appusers':
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$source_table = $applat_db.".refnoncoreusers";
									$source_field = $applat_db.".refnoncoreusers.fullname";
									$select_field = "Id";

									break;
								default:
									$app_init = $this->app_init->app_init();
									$applat_db = $app_init['applat_db_name'];

									$source_table = $applat_db.".refselectionitems";
									$source_field = $applat_db.".refselectionitems.item_text";
									$select_field = "item_value";
									break;
							}

							$this->db->select($select_field);
							$this->db->from($source_table);
							$this->db->like($source_field,$datapost['search']);

							if ($row->item_source == "manageditems") {
								$this->db->where($source_table.".form_component_id",$extended_field_id);
							}
							
							$query_dropdown_source_data =  $this->db->get()->result();

							if ($query_dropdown_source_data) {
								$id_data_dropdown_string = "";

								foreach ($query_dropdown_source_data as $row_dropdown_source_data) {
									$id_data_dropdown_string .= ($id_data_dropdown_string == "") ? $row_dropdown_source_data->Id : ",".$row_dropdown_source_data->Id ;
								}
								
								$dropdown_search_string .= ($dropdown_search_string == "") ? $query_extended_table[0]->field_name." in (".$id_data_dropdown_string.")" : " or ".$query_extended_table[0]->field_name." in (".$id_data_dropdown_string.")" ;

								$this->db->select('Id');
								$this->db->from($query_extended_table[0]->full_table_name);
								$this->db->where($dropdown_search_string,NULL);

								$query_extended_search_result = $this->db->get()->result();
							}
						}

						if ($query_extended_table[0]->field_type == "varchar") {
							$this->db->select('Id');
							$this->db->from($query_extended_table[0]->full_table_name);
							$this->db->like($query_extended_table[0]->field_name,$datapost['search']);

							$query_extended_search_result = $this->db->get()->result();
						}

						foreach ($query_extended_search_result as $row_extended_result) {
							$extended_field_search = ($extended_field_search == '')? $row_extended_result->Id : $extended_field_search.','.$row_extended_result->Id;
						}

						if ($extended_field_search != '') {
							$extended_field_search_tmp = '';
							$data_ids_array = explode(',',$extended_field_search);
							$data_ids_chunk = array_chunk($data_ids_array,25);
							
							foreach($data_ids_chunk as $data_ids_search)
							{
								$data_ids_string = '';
								foreach ($data_ids_search as $key => $value) {
									$data_ids_string .= ($data_ids_string == '') ? $value : ','.$value ;
								}

							    $extended_field_search_tmp .= ($extended_field_search_tmp == '') ? $query_extended_table[0]->full_table_name.'_id in ('.$data_ids_string.')' : ' or '.$query_extended_table[0]->full_table_name.'_id in ('.$data_ids_string.')';
							}

							if ($extended_field_search_tmp != '') $extended_field_search_tmp= '('.$extended_field_search_tmp.')';

							$extended_field_search_string = ($extended_field_search_string == '')? $extended_field_search_tmp : $extended_field_search_string.' or '.$extended_field_search_tmp;
						}
					}
				}
			}

			if ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1) {
				$app_init = $this->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];
				
				$this->db->select('username');
				$this->db->from($applat_db.'.refnoncoreusers');
				$this->db->like($applat_db.'.refnoncoreusers.fullname', $datapost['search']);
				
				$query_requestor =  $this->db->get()->result();
				
				if ($query_requestor) {
					foreach ($query_requestor as $row_requestor) {
						$search_requestor .= ($search_requestor == "") ? "'".$row_requestor->username."'" : ",'".$row_requestor->username."'" ;
					}
				}
			}
		}

		if ($data_menu['is_approval'] == 1 && $formtype == 'mainform') {
			$datasession = $this->session->userdata('logged_in');

			$this->db->select('Id');
			$this->db->from('v_id_request_pending_approval');
			$this->db->where('username',$datasession['username']);

			$pending_ids = $this->db->get()->result();

			foreach ($pending_ids as $row_pending_id) {
				$pending_id .= ($pending_id == '') ? $row_pending_id->Id : ','.$row_pending_id->Id;
			}

			if ($this->session->userdata($data_menu['url'].'_show_srf')) {
				$pending_id = "-1";
				$pending_ids = array();

				$this->db->distinct();
				$this->db->select('Id');
				$this->db->from('v_approval_log_by_approver');
				$this->db->where('createby',$datasession['username']);

				$pending_ids = $this->db->get()->result();
				
				foreach ($pending_ids as $row_pending_id) {
					$pending_id .= ($pending_id == '') ? $row_pending_id->Id : ','.$row_pending_id->Id;
				}				
			}
		}
		///////////////////////////////////////////////
		
		if ($data_menu['is_workflowdata'] == 1) {
			///// data requests can only be viewed by the user who created it EXCEPT for those who are allowed to view all
			if ($formtype != 'subform') {
				$datasession = $this->session->userdata('logged_in');
				$allow_view_all_workflowdata = $this->allow_user_view_all_workflowdata($datasession['username']);
			}
			///////////////////////////////////////////////////////////////////////////////////////////
		}

		//// Search by reference number /////////////////
		$search_by_reference_number = "";
		$additional_forms = $this->shared_variables->display_reference_column;

		if ($formtype == 'mainform' && $datapost['search'] != '' and ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || in_array($data_menu['url'], $additional_forms))) {

			$this->db->select($table_name.'.Id');
			$this->db->from($table_name);
			$this->db->like('request_reference_number',$datapost['search']);

			$query_search_reference = array();
			$query_search_reference = $this->db->get()->result();

			foreach ($query_search_reference as $row) {
					$search_by_reference_number .= ($search_by_reference_number == "")? $row->Id : ','.$row->Id;
			}
		}
		//// [END] Serach by reference number [END] /////

		#search filter for dropdown fields
		$dropdown_search_string = "";
		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				if ($row->control_type == "dropdown") {
					$source_table = "";
					$source_field = "";
					$select_field = "";

					switch ($row->item_source) {
						case 'datatable':
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$this->db->select($applat_db.'.refmenu.full_table_name, '.$applat_db.'.refforms.field_name, '.$applat_db.'.refapps.dbname');
							$this->db->from($applat_db.'.refselectiondata');
							$this->db->join($applat_db.'.refforms',$applat_db.'.refforms.Id = '.$applat_db.'.refselectiondata.component_id_data');
							$this->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refforms.menu_id');
							$this->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
							$this->db->where($applat_db.'.refselectiondata.form_component_id', $row->Id);

							$query_dropdown_source_table =  $this->db->get()->result();

							$source_table = $query_dropdown_source_table[0]->dbname.'.'.$query_dropdown_source_table[0]->full_table_name;
							$source_field = $query_dropdown_source_table[0]->field_name;
							$select_field = "Id";
							break;
						case 'appusers':
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$source_table = $applat_db.".refnoncoreusers";
							$source_field = $applat_db.".refnoncoreusers.fullname";
							$select_field = "Id";

							break;
						default:
							$app_init = $this->app_init->app_init();
							$applat_db = $app_init['applat_db_name'];

							$source_table = $applat_db.".refselectionitems";
							$source_field = $applat_db.".refselectionitems.item_text";
							$select_field = "item_value as Id";
							break;
					}
					
					$this->db->select($select_field);
					$this->db->from($source_table);
					$this->db->like($source_field,$datapost['search']);

					if ($row->item_source == "manageditems") {
						$this->db->where($source_table.".form_component_id",$row->Id);
					}
					
					$query_dropdown_source_data =  $this->db->get()->result();

					if ($query_dropdown_source_data) {
						$id_data_dropdown_string = "";

						foreach ($query_dropdown_source_data as $row_dropdown_source_data) {
							$id_data_dropdown_string .= ($id_data_dropdown_string == "") ? "'".$row_dropdown_source_data->Id."'" : ",'".$row_dropdown_source_data->Id."'" ;
						}
						
						$dropdown_search_string .= ($dropdown_search_string == "") ? $row->field_name." in (".$id_data_dropdown_string.")" : " or ".$row->field_name." in (".$id_data_dropdown_string.")" ;
					}
				}
			}	
		}		
		#

		//$this->db->select('Id');
		$this->db->select($table_name.'.*');
		$this->db->from($table_name);

		if ($datapost['search'] != '') {
			foreach ($query as $row) {
				
				//$this->db->select($row->field_name);
				
				if ($formtype != 'subform') {
					if (!is_numeric($row->field_name)) {
						if (!$opened_bracket) {
							$this->db->group_start();
							$opened_bracket = TRUE;
						}

						$count_field++;
						if ($count_field == 1) $this->db->like($table_name.'.'.$row->field_name,$datapost['search']); else $this->db->or_like($table_name.'.'.$row->field_name,$datapost['search']);
					}
				}

				#dropdown search
					if ($dropdown_search_string != "") {
						$this->db->or_where($dropdown_search_string,NULL);
					}
					#
			}

			if ($extended_field_search_string != '') {
				$this->db->or_where($extended_field_search_string,NULL);
			}

			//// Search by reference number /////////////////
			if ($search_by_reference_number != "") {
				$this->db->or_where($table_name.'.Id in ('.$search_by_reference_number.')',NULL);
			}
			//// [END] Serach by reference number [END] /////
			
			if ($search_requestor != "") {
				$this->db->or_where($data_menu['full_table_name'].'.createby in ('.$search_requestor.')', NULL);
			}

			if ($opened_bracket) {
				$this->db->group_end();
			}
		} 
		
		$this->db->where($table_name.'.isdelete', 0);

		if ($data_menu['is_workflowdata'] == 1) {
			///// data requests can only be viewed by the user who created it EXCEPT for those who are allowed to view all
			if ($formtype != 'subform') {
				if (!$allow_view_all_workflowdata) {
					$datasession = $this->session->userdata('logged_in');
					$this->db->where('createby', $datasession['username']);	
				}
			}
			///////////////////////////////////////////////////////////////////////////////////////////
		}
		
		if ($formtype == 'subform') {
			$this->db->where($data_menu['full_table_name'].'_id', $main_id);
		}

		#display requested based on selected status filter, if form is workflow or approval
		if ($data_menu['is_approval'] == 1 && $formtype == 'mainform') {
			$datasession = $this->session->userdata('logged_in');
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];

			$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnworkflowseq where username = "'.$datasession['username'].'" and status is null and is_data_displayed = 1 and menu_id in ('.$requestor_form_menu_id.')';

			if ($this->session->userdata($data_menu['url'].'_show_request')) {
				$status_filter = $this->session->userdata($data_menu['url'].'_show_request');

				if (in_array($status_filter, array('Full Approve','I have processed'))) {$status_filter = 'Approve';}

				if ($status_filter != 'Waiting Approval') {
					$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where createby = "'.$datasession['username'].'" and status = "'.$status_filter.'" and menu_id in ('.$requestor_form_menu_id.')';

						if ($allow_view_all_workflowdata) {
							$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where status = "'.$status_filter.'" and menu_id in ('.$requestor_form_menu_id.')';
						}

					if ($status_filter == 'Revise') {
						$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where createby = "'.$datasession['username'].'" and (status = "'.$status_filter.'" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';

						if ($allow_view_all_workflowdata) {
							$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where (status = "'.$status_filter.'" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';	
						}
					}

					if ($this->session->userdata($data_menu['url'].'_show_request') == 'I have processed') {
						$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where createby = "'.$datasession['username'].'" and (status = "Reject" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';

						if ($allow_view_all_workflowdata) {
							$processed_requests_id_query_string = 'select data_id from '.$applat_db.'.trnlogworkflow where (status = "'.$status_filter.'" or status = "Approve") and menu_id in ('.$requestor_form_menu_id.')';	
						}
					}
				}
			}

			//$this->db->where($data_menu['full_table_name'].'.Id in ('.$pending_id.')', NULL);
			$this->db->where($data_menu['full_table_name'].'.Id in ('.$processed_requests_id_query_string.')', NULL);
		}

		if ($this->session->userdata($data_menu['url'].'_show_request') && ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1) && $formtype == 'mainform') {

			$status_filter = str_replace("I have processed","All", $this->session->userdata($data_menu['url'].'_show_request'));

			if ($status_filter != 'All' && $formtype == 'mainform') {
				$this->db->where('status', $status_filter);
			}
		}
		
		#filter data by activity type
		if ($formtype == 'mainform' && ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1)) {
			$this->db->where($data_menu['full_table_name'].'.activity_type',$this->filter_data_by_activity_type($data_menu['url']));
		}
		#[END OF] filter data by activity type

		#customized fot MUNDIPHARMA, display data for update bpom status form
		if (strtolower($data_menu['url']) == 'trans_frm_bpomgov_status' && $formtype == 'mainform') {
			$this->db->where($data_menu['full_table_name'].'.is_bpom_required',1);
			$this->db->where($data_menu['full_table_name'].'.status','Full Approve');
		}
		#
		#customized fot MUNDIPHARMA, display data for update regional status form
		if (strtolower($data_menu['url']) == 'trans_frm_regional_status' && $formtype == 'mainform') {
			$this->db->where($data_menu['full_table_name'].'.is_regional_required',1);
			$this->db->where($data_menu['full_table_name'].'.status','Full Approve');
			$this->db->where('(coalesce('.$data_menu['full_table_name'].'.is_bpom_required,0) = 0 or ('.$data_menu['full_table_name'].'.is_bpom_required = 1 and '.$data_menu['full_table_name'].'.bpom_process_status = "Approved"))',NULL);
		}
		#
		#
		#customized fot MUNDIPHARMA, display submit request message on primary file log table
		if ($formtype == "subform" && $subform_name == "Uploaded Material Primary File Log") {
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];

			$this->db->distinct();
			$this->db->select($applat_db.".trnlogworkflow.comm_msg");
			$this->db->join($applat_db.".trnlogworkflow",$table_name.".createby = ".$applat_db.".trnlogworkflow.createby and DATE_FORMAT(".$table_name.".material_file_submit_date, '%Y-%m-%d %H:%i') = DATE_FORMAT(".$applat_db.".trnlogworkflow.createdate, '%Y-%m-%d %H:%i')","left");
				
		}
		#[END OF] customized fot MUNDIPHARMA, display submit request message on primary file log table
		
		$this->db->limit($limit);
		$this->db->offset($offset);

		$this->db->order_by('Id','DESC');
		
		//echo $this->db->get_compiled_select(); exit();
		
		return $this->db->get()->result();
	}

	function filter_data_by_activity_type($url) {
		$activity_type = '';
		$activity_type_array = array();

		$activity_type_array['opioid'] = array('req_frm_opioid','apprv_frm_opioid');
		$activity_type_array['training_internal'] = array('req_frm_internal_training','apprv_frm_internal_training');
		$activity_type_array['product_name'] = array('req_frm_product_material','apprv_frm_product_material');
		$activity_type_array['speaker_brief'] = array('req_frm_speaker_brief','apprv_frm_speaker_brief');
		$activity_type_array['corporate'] = array('req_frm_corporate_materials','apprv_frm_corporate_materials');
		$activity_type_array['key_promo_aid'] = array('req_frm_key_promotional_aid','apprv_frm_key_promotional_aid');
		$activity_type_array['storemedia_pos'] = array('req_frm_in_store_pos','apprv_frm_in_store_pos');
		$activity_type_array['social_media'] = array('req_frm_social_media','apprv_frm_social_media');
		$activity_type_array['ecommerce'] = array('req_frm_ecommerce','apprv_frm_ecommerce');
		$activity_type_array['gimmicks'] = array('req_frm_gimmicks','apprv_frm_gimmicks');
		$activity_type_array['other'] = array('req_frm_other','apprv_frm_other');

		foreach ($activity_type_array as $key => $value) {
			if (in_array(strtolower($url), $activity_type_array[$key])) {
				$activity_type = $key;
			}
		}

		return ($activity_type != '') ? $activity_type : 'zzz' ;
	}
	
	function get_data_detail($data_menu,$form_fields,$data_id,$formtype,$subform_name = '') {
		$tmp = array();
		$table_name = "";
		$has_extended_data = FALSE;

		$additional_forms = $this->shared_variables->display_reference_column;
		
		if ($formtype == 'mainform') { 
			$table_name = $data_menu['full_table_name'];
		}

		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}

		if ($data_id == 0) {
			$task = 'new';
		} else {
			$task = 'edit';
		}

		$data_id = $this->get_data_id_from_hash_link($data_id,$table_name);

		if ($data_id == 0 && $task == 'edit') {
			redirect('eform/index/'.$data_menu['url']);
		}
		
		$query = array();
		
		$this->db->select('Id,createby,hash_link');

		if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || in_array($data_menu['url'], $additional_forms)) && $formtype == 'mainform') {
			$this->db->select('status,request_reference_number');
		}

		foreach($form_fields as $form_field) {
			if ($form_field->control_type != 'extend' && $form_field->control_type != 'checkbox' && $form_field->control_type != 'separator') {
				$tmp[$form_field->field_name] = '';
				$this->db->select($form_field->field_name);
			}else{
				if ($form_field->control_type == 'extend') {
					$has_extended_data = TRUE;
				}
			}
		}
		$this->db->where('isdelete', 0);
		$this->db->where('Id', $data_id);
		$this->db->from($table_name);
		
		$query = $this->db->get()->result();

		$tmp['Id'] = 0;
		$tmp['createby'] = '';
		$tmp['hash_link'] = '';

		if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || in_array($data_menu['url'], $additional_forms) && $formtype == 'mainform')) {
			$tmp['status'] = '';
			$tmp['request_reference_number'] = '';
		}

		if ($query) {
			$row = $query[0];
			$tmp['Id'] = $row->Id;
			$tmp['createby'] = $row->createby;
			$tmp['hash_link'] = $row->hash_link;
			if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || in_array($data_menu['url'], $additional_forms)) && $formtype == 'mainform') {
				$tmp['status'] = $row->status;
				$tmp['request_reference_number'] = $row->request_reference_number;
			}

			foreach($form_fields as $form_field) {
				if ($form_field->control_type != 'extend' && $form_field->control_type != 'checkbox' && $form_field->control_type != 'separator') {
					$field_name = $form_field->field_name;
					$tmp[$field_name] = $row->$field_name;

					if ($form_field->field_type == 'datetime') {
						if ($tmp[$field_name] != NULL) {
							$tmp[$field_name] = $this->datetime->convert_mysql_date_format_to_short_string($tmp[$field_name]);	
						}else{
							$tmp[$field_name] = NULL;
						}
						
					}		
				}
			}
			
			if ($has_extended_data) {
				if ($this->session->userdata('selection_id') == NULL) {

					$extended_field_id = $this->get_data_extend_from_table($data_menu)['full_table_name'].'_id';
				
					$this->db->select($extended_field_id);
					$this->db->from($table_name);
					$this->db->where('Id', $data_id);
					$this->db->limit(1);
					
					$query = array();

					$query =  $this->db->get()->result();
					$this->session->set_userdata('selection_id', $query[0]->$extended_field_id);
				}
			}
		}
		
		return $tmp;
	}
	
	function check_if_menu_has_extended_data($data_menu) {
		$tmp = FALSE;
		
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select('Id');
		$this->db->from($applat_db.'.refextend_datamenu');
		$this->db->where($applat_db.'.refextend_datamenu.to_menu_id', $data_menu['id']);
		$this->db->where($applat_db.'.refextend_datamenu.isdelete', 0);
		$this->db->limit(1);
		
		$query =  $this->db->get()->result();

		if (count($query) == 1) {
			$tmp = TRUE;
		}
		return $tmp;
	}
	
	function get_data_extend_from_table($data_menu) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('from_menu_id,label,field_name,selection_component_id');
		$this->db->from('refextend_datamenu');
		$this->db->join('refforms','refforms.Id = refextend_datamenu.selection_component_id');
		$this->db->where('refextend_datamenu.to_menu_id', $data_menu['id']);
		$this->db->where('refextend_datamenu.isdelete', 0);
		$this->db->limit(1);
		
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);

		$from_menu_id = $query[0]->from_menu_id;
		$tmp['label'] = $query[0]->label;
		$tmp['field_name'] = $query[0]->field_name;
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select();
		$this->db->from('refmenu');
		$this->db->where('Id', $from_menu_id);
		$this->db->where('isdelete', 0);
		$this->db->limit(1);
		
		$from_data_menu =  $this->db->get()->result();

		$this->db->query('use '.$current_db);

		$tmp['full_table_name'] = $from_data_menu[0]->full_table_name;
		
		$this->db->select($from_data_menu[0]->full_table_name.'.*');
		$this->db->from($from_data_menu[0]->full_table_name);
		
		$this->db->where($from_data_menu[0]->full_table_name.'.isdelete', 0);
		
		$tmp['query'] = $this->db->get()->result();

		$tmp['data_from_full_table_name'] = $from_data_menu[0]->full_table_name;

		return $tmp;
	}
	
	function is_extended_form($data_menu) {
		$tmp = FALSE;
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('Id');
		$this->db->from('refextend_datamenu');
		$this->db->where('refextend_datamenu.to_menu_id', $data_menu['id']);
		$this->db->where('refextend_datamenu.isdelete', 0);
		$this->db->limit(1);	
		
		$query =  $this->db->get()->result();

		$this->db->query('use '.$current_db);

		if (count($query) == 1) {
			$tmp = TRUE;
		}
		return $tmp;
	}
		
	function get_function_access_data($menu_name,$menu) {
		$tmp['is_insert_disable'] = NULL;
		$tmp['is_edit_disable'] = NULL;
		$tmp['is_delete_disable'] = NULL;
		
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('usergroup_modul_insert_disable,usergroup_modul_edit_disable,usergroup_modul_delete_disable,apply_setting_to_menu,menu_insert_disable,menu_edit_disable,menu_delete_disable,is_used_user_credent,user_credent_insert_disable,user_credent_edit_disable,user_credent_delete_disable');	
		$this->db->from('v_functionaccess');
		$this->db->where('username',$menu['user']);
		$this->db->where('app_id',$menu['app_id']);
		$this->db->where('url',$menu_name);
		
		$query = $this->db->get()->result();

		$this->db->query('use '.$current_db);
		
		if (count($query) >= 1) {
			$row =  $query[0];
		
			if ($row->is_used_user_credent == 1) {
				$tmp['is_insert_disable'] = $row->user_credent_insert_disable;
				$tmp['is_edit_disable'] = $row->user_credent_edit_disable;
				$tmp['is_delete_disable'] = $row->user_credent_delete_disable;
			} else{
				if ($row->apply_setting_to_menu == 1) {
					$tmp['is_insert_disable'] = $row->usergroup_modul_insert_disable;
					$tmp['is_edit_disable'] = $row->usergroup_modul_edit_disable;
					$tmp['is_delete_disable'] = $row->usergroup_modul_delete_disable;
				}else{
					$tmp['is_insert_disable'] = $row->menu_insert_disable;
					$tmp['is_edit_disable'] = $row->menu_edit_disable;
					$tmp['is_delete_disable'] = $row->menu_delete_disable;
				}
			}
		}
		
		return $tmp;
	}
	

	function get_request_reference_number($data_menu, $data_id) {
		$this->db->select('request_reference_number');
		$this->db->from($data_menu['full_table_name']);
		$this->db->where('Id', $data_id);
		
		$query =  $this->db->get()->result();

		if ($query) {
			return $query[0]->request_reference_number;
		} else {
			return '';
		}
	}

	function get_subform_full_table_name($menu_id,$subform_name) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$subform_name = str_replace("zzz", "/", $subform_name);

		$this->db->select('full_table_name');
		$this->db->from($applat_db.'.refsubform_menu');
		$this->db->where('menu_id', $menu_id);
		$this->db->where('subform_name', $subform_name);
		$this->db->limit(1);	
		
		$query =  $this->db->get()->result();

		return $query[0]->full_table_name;
	}

	function get_next_approver($data_menu,$data_id) {
		$tmp = '';

		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->select();
		$this->db->from($applat_db.'.v_next_approver');
		$this->db->where('menu_id', $data_menu['id']);
		$this->db->where('data_id', $data_id);
		
		$query =  $this->db->get()->result();

		if ($query) {
			foreach ($query as $row) {
				$tmp .= ($tmp == '') ? $row->fullname : ", ".$row->fullname;
			}
		}

		return $tmp;
	}

	function get_data_prior_change($full_table_name,$form_fields,$id) {
		if (count($form_fields)>0) {
			foreach($form_fields as $form_field) {
				if ($form_field->control_type != 'checkbox' && $form_field->control_type != 'separator' && $form_field->control_type != 'extend') {
					$field_name = $form_field->field_name;
					$tmp[$field_name] = NULL;
				}
			}
			
			foreach($form_fields as $form_field) {
				if ($form_field->control_type != 'checkbox' && $form_field->control_type != 'separator' && $form_field->control_type != 'extend') { 
					$this->db->select($form_field->field_name);
				}
			}
			$this->db->where('id', $id);
			$this->db->from($full_table_name);

			$query = $this->db->get()->result();
		
			if (count($query) >= 1) {
				$row =  $query[0];
				foreach($form_fields as $form_field) {
					if ($form_field->control_type != 'checkbox' && $form_field->control_type != 'separator' && $form_field->control_type != 'extend') { 
						$field_name = $form_field->field_name;
						$tmp[$field_name] = $row->$field_name;
					}
				}	
			}
			
			return $tmp;
		}else{
			return NULL;
		}
	}
	
	function update_log($log) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$data = array(
		        'app_id' => $log['app_id'],
		        'data_trans_type' => $log['data_trans_type'],
		        'username' => $log['username'],
		        'ip_address' => $log['ip_address'],
		        'data_changes' => $log['data_changes'],
		        'createby' => 'sys',
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);

		$this->db->insert($applat_db.'.trnlogdata', $data);	
	}
	
	function get_data_id_from_draft($data_menu,$draft_id) {
		$tmp_id = "";
		
		$this->db->select('hash_link');
		$this->db->where('draft_id', $draft_id);
		$this->db->from($data_menu['full_table_name']);
		
		$row = array();
		$row =  $this->db->get()->result()[0];
		
		$tmp_id = $row->hash_link;
		
		////////// set draft_id to null ////////////////////////////////////////
		$data['draft_id'] = NULL;
		$this->db->where('hash_link', $tmp_id);
		$this->db->update($data_menu['full_table_name'], $data);
		/////////////////////////////////////////////////////////////////////////////
		return $tmp_id;	
	}

	#customized fot MUNDIPHARMA, ePMAP Project 2020, insert usergroups to reviewer log table
	function update_usergroups_reviewer_log() {
		$this->db->select('createby');
		$this->db->from('epmap_req_material_reviewer_approver_file');
		$this->db->where('createby is not null', NULL);
		$this->db->where('usergroups is null', NULL);
		
		$query =  $this->db->get()->result();

		foreach ($query as $row) {
			$this->db->select('usergroup_name');
			$this->db->from('v_epmap_users_usergroups');
			$this->db->where('username', $row->createby);
			
			$usergroup =  $this->db->get()->result();

			if ($usergroup) {
				$usergroup = $usergroup[0]->usergroup_name;

				$data['usergroups'] = $usergroup;
				$this->db->where('createby', $row->createby);
				$this->db->where('usergroups is null', NULL);
				$this->db->update('epmap_req_material_reviewer_approver_file', $data);
			}
		}
	}
	#
	
	#customized fot MUNDIPHARMA, ePMAP Project 2020, check bpom status
	function check_bpom_status($data_menu, $data_id) {
		$this->db->select('is_bpom_required, bpom_process_status, is_regional_required, regional_status');
		$this->db->from($data_menu['full_table_name']);
		$this->db->where('Id', $data_id);
		
		$query =  $this->db->get()->result();

		if ($query) {
			return $query[0];
		} else {
			return FALSE;
		}
	}
	# [END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, check bpom status

	#customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is first submit
	function check_request_first_submit($data_menu, $data_id) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);
		$data_id = $this->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

		$this->db->select('count(Id) count_id_submit');
		$this->db->from($applat_db.'.trnlogworkflow');
		$this->db->where('data_id', $data_id);
		$this->db->where('menu_id', $requestor_form_menu_id);
		$this->db->where('status', 'Submit');
		
		$query =  $this->db->get()->result();

		if ($query) {
			return ($query[0]->count_id_submit == 0) ? 1 : $query[0]->count_id_submit;
		} else {
			return 1;
		}

	}
	#[END OF]customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is first submit
	#
	#customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is already approved by MSL
	function check_request_msl_prior_approved($data_menu, $data_id) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);
		$data_id = $this->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

		$this->db->select($applat_db.'.trnlogworkflow.Id');
		$this->db->from($applat_db.'.trnlogworkflow');
		$this->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnlogworkflow.createby');
		$this->db->join($applat_db.'.refnoncoreusergroups_users',$applat_db.'.refnoncoreusers.Id = '.$applat_db.'.refnoncoreusergroups_users.user_id');
		$this->db->join($applat_db.'.refnoncoreusergroups',$applat_db.'.refnoncoreusergroups.Id = '.$applat_db.'.refnoncoreusergroups_users.group_id');
		$this->db->where('data_id', $data_id);
		$this->db->where('menu_id', $requestor_form_menu_id);
		$this->db->where('usergroup_name', 'Medical Scientific Liaison user group');
		$this->db->where('status', 'Approve');
		
		$query =  $this->db->get()->result();

		return $query;

	}
	#[END OF]customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is already approved by MSL
	
	#customized fot MUNDIPHARMA, ePMAP Project 2020
	function get_product_prefix($data_id) {
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->db->select('epmap_prefix,epmap_start_number');
		$this->db->from($applat_db.'.cmbr_ref_product');
		$this->db->where('Id', $data_id);
		$this->db->limit(1);
		
		$query =  $this->db->get()->result();

		if ($query) {
			return array('epmap_prefix' => $query[0]->epmap_prefix, 'epmap_start_number' => (int) $query[0]->epmap_start_number);
		} else {
			return array('epmap_prefix' => '', 'epmap_start_number' => 0);
		}
	}
	#
	
	function update_data($datasession,$data_menu,$form_fields,$datapost,$id,$formtype,$subform_name) {
		
		$table_name = '';
		if ($formtype == 'mainform') { $table_name = $data_menu['full_table_name'];}
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}
		
		$id = $this->get_data_id_from_hash_link($id,$table_name);
		$data_before = $this->get_data_prior_change($table_name,$form_fields,$id);
		
		$log['data_changes'] = '';
		$data = array();
		foreach($form_fields as $form_field) {
			if ($form_field->control_type != 'checkbox' && $form_field->control_type != 'separator' && $form_field->control_type != 'extend') {
				if ($form_field->field_type == 'datetime') {
					if ($datapost[$form_field->field_name] != NULL) {
						$datapost[$form_field->field_name] = $this->datetime->convert_short_date_format_to_mysql($datapost[$form_field->field_name]);					}else{
						$datapost[$form_field->field_name] = NULL;
						}
					
				}
				
				$field_name = $form_field->field_name;
				$field_value = $datapost[$form_field->field_name];

				$data = array_merge($data,array($field_name => $field_value));

				if ($data_before != NULL) {
					$log['data_changes'] .= " zzz ".$form_field->field_name.": ".$data_before[$form_field->field_name]." => ".$datapost[$form_field->field_name];
				}
			}
		}
		
		if ($formtype == 'mainform' && $data_menu['is_workflowdata'] == 1) {
			$tmp = array();
			$tmp = array('status' => 'Draft');
			$data = array_merge($data,$tmp);

			#customized fot MUNDIPHARMA, ePMAP Project 2020
			if (strtolower($data_menu['full_table_name']) == 'epmap_req_material_data') {
				$product_prefix = $this->get_product_prefix($datapost['brand_id']);

				$tmp = array();
				$tmp = array('product_prefix' => $product_prefix['epmap_prefix']);
				$data = array_merge($data,$tmp);
			}
			#
		}

		#customized fot MUNDIPHARMA, ePMAP Project 2020
		if ($data_menu['url'] == 'trans_frm_bpomgov_status') {
			if (array_key_exists('bpom_process_status', $datapost)) {
				if ($datapost['bpom_process_status'] != 'In Process') {
					$tmp = array();
					$tmp = array('bpom_finish_process_date' => $this->datetime->get_current_datetime());
					$data = array_merge($data,$tmp);
				}
			}
		}
		#
		#customized fot MUNDIPHARMA, ePMAP Project 2020
		if ($data_menu['url'] == 'trans_frm_regional_status') {
			if (array_key_exists('regional_status', $datapost)) {
				if ($datapost['regional_status'] != 'In Process') {
					$tmp = array();
					$tmp = array('regional_finish_process_date' => $this->datetime->get_current_datetime());
					$data = array_merge($data,$tmp);

					if ($datapost['regional_status'] == 'Approved') {
						$this->db->select('request_reference_number, last_approval_process_date');
						$this->db->from($table_name);
						$this->db->where('Id', $id);

						$query = $this->db->get()->result();

						$full_approved_date = $query[0]->last_approval_process_date;
						$full_approved_date_array = explode(" ", $full_approved_date);
						$full_approved_date = $full_approved_date_array[0];
						$full_approved_date = new DateTime($full_approved_date);

						$pmap_validity_date = $full_approved_date;
						$interval = new DateInterval('P2Y'); #PMAP Validity period of 2 years
						$pmap_validity_date->add($interval);

						$full_approved_date_tmp = $query[0]->last_approval_process_date;
						$full_approved_date_array_tmp = explode(" ", $full_approved_date_tmp);
						$full_approved_date_tmp = $full_approved_date_array_tmp[0];
						$full_approved_date_tmp = new DateTime($full_approved_date_tmp);

						$month_approval_code = 100 + (int) $full_approved_date_tmp->format('m');
						$month_approval_code = (string) $month_approval_code;
						$month_approval_code = substr($month_approval_code,1);

						$year_approval_code = (string) $full_approved_date_tmp->format('Y');
						$year_approval_code = substr($year_approval_code,2);

						$request_reference_number = $query[0]->request_reference_number.'/'.$month_approval_code.$year_approval_code;

						$tmp = array();
						$tmp = array('request_reference_number' => $request_reference_number,
									'pmap_validity_date' => $pmap_validity_date->format('Y-m-d')
							);
						$data = array_merge($data,$tmp);
					}
				}
			}
		}
		#
		
		$tmp = array();
		$tmp = array(
		     'updateby' => $datasession['username'],
	 		 'updatedate' => $this->datetime->get_current_datetime()
		);
		$data = array_merge($data,$tmp);

		$this->db->where('Id', $id);
		$this->db->update($table_name, $data);

		#customized fot MUNDIPHARMA, ePMAP Project 2020, send email to requestor for BPOM & Regional Process Status Notification
		if ($data_menu['url'] == 'trans_frm_bpomgov_status') {
			if (array_key_exists('bpom_process_status', $datapost)) {
				if ($datapost['bpom_process_status'] != 'In Process') {
					$data_smtp = $this->wf->get_data_smtp();
					$app_init = $this->app_init->app_init();
					$applat_db = $app_init['applat_db_name'];

					if ($data_smtp['is_enable'] == 1) {
						$this->db->select('username,fullname,email_address');
						$this->db->from($applat_db.'.refnoncoreusers');
						$this->db->join($table_name, $table_name.'.createby = '.$applat_db.'.refnoncoreusers.username');
						$this->db->where($table_name.'.Id',$id);
						
						$query = $this->db->get()->result();

						$row = $query[0];

						$content = $this->wf->get_detail_request_for_email($data_menu,$id);

						$body_mail_content = "Dear ".$row->fullname.",<br><br><br>
								BPOM / Government Agency Approval Process for the following Material is <strong>".strtoupper($datapost['bpom_process_status'])."</strong>
								<br><br>".$content['content_email']."
								<br><br>Reference : ".$content['reference_number']."
								<br><br>BPOM/Gov. Agency Process confirmed by : ".$datasession['fullname']."
								<br><br>Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

						$data = array();
						$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);

						$data = array(
							        'menu_id' => $requestor_form_menu_id,
							        'data_id' => $id,
							        'username' => $row->username,
							        'to_email_address' => $row->email_address,
							        'email_subject' => 'BPOM/Gov. Process Status for Request#.'.$content['reference_number'].' : '.strtoupper($datapost['bpom_process_status']),
							        'email_body_content' => $body_mail_content,
							        'createby' => $datasession['username'],
							        'createdate' => $this->datetime->get_current_datetime()
								);
						
						$this->db->insert($applat_db.'.refmailmanjobs', $data);
						
						if (strtolower($datapost['bpom_process_status']) == strtolower("Approved")) {
							$this->db->select('username,fullname,email_address');
							$this->db->from($data_menu['full_table_name']);
							$this->db->join($applat_db.'.refnoncoreusers', $data_menu['full_table_name'].'.required_regional_process_by = '.$applat_db.'.refnoncoreusers.username');
							$this->db->where($data_menu['full_table_name'].'.Id',$id);
							$this->db->where($data_menu['full_table_name'].'.is_regional_required',1);
							
							$query_regional = $this->db->get()->result();

							if ($query_regional) {
								$row_regional = $query_regional[0];

								$data = array();
								$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);

								$body_mail_content = "Dear ".$row_regional->fullname.",<br><br><br>
										Kindly proceed Regional Communication Approval Process for the following  Material
										<br><br>".$content['content_email']."
										<br><br>Reference : ".$content['reference_number']."
										<br><br>BPOM/Gov. Agency Process confirmed by : ".$datasession['fullname']."
										<br><br>Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

								$data = array();
								$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);

								$data = array(
									        'menu_id' => $requestor_form_menu_id,
									        'data_id' => $id,
									        'username' => $row_regional->username,
									        'to_email_address' => $row_regional->email_address.','.$row->email_address,
									        'email_subject' => 'Proceed Regional Process for ePMAP#.'.$content['reference_number'],
									        'email_body_content' => $body_mail_content,
									        'createby' => $datasession['username'],
									        'createdate' => $this->datetime->get_current_datetime()
										);
								
								$this->db->insert($applat_db.'.refmailmanjobs', $data);
							}
						}
					}
				}
			}
		}

		if ($data_menu['url'] == 'trans_frm_regional_status') {
			if (array_key_exists('regional_status', $datapost)) {
				if ($datapost['regional_status'] != 'In Process') {
					$data_smtp = $this->wf->get_data_smtp();
					$app_init = $this->app_init->app_init();
					$applat_db = $app_init['applat_db_name'];

					if ($data_smtp['is_enable'] == 1) {
						$this->db->select('username,fullname,email_address');
						$this->db->from($applat_db.'.refnoncoreusers');
						$this->db->join($table_name, $table_name.'.createby = '.$applat_db.'.refnoncoreusers.username');
						$this->db->where($table_name.'.Id',$id);
						
						$query = $this->db->get()->result();

						$row = $query[0];

						$content = $this->wf->get_detail_request_for_email($data_menu,$id);

						$body_mail_content = "Dear ".$row->fullname.",<br><br><br>
								Regional Communicaiton Approval Process for the following Material is <strong>".strtoupper($datapost['regional_status'])."</strong>
								<br><br>".$content['content_email']."
								<br><br>Reference : ".$content['reference_number']."
								<br><br>Regional Communication Process confirmed by : ".$datasession['fullname']."
								<br><br>Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

						$data = array();
						$requestor_form_menu_id = $this->wf->get_origin_data_menu_id($data_menu);

						$data = array(
							        'menu_id' => $requestor_form_menu_id,
							        'data_id' => $id,
							        'username' => $row->username,
							        'to_email_address' => $row->email_address,
							        'email_subject' => 'Regional Communication Process Status for Request#.'.$content['reference_number'].' : '.strtoupper($datapost['regional_status']),
							        'email_body_content' => $body_mail_content,
							        'createby' => $datasession['username'],
							        'createdate' => $this->datetime->get_current_datetime()
								);
						
						$this->db->insert($applat_db.'.refmailmanjobs', $data);
					}
				}
			}
		}
		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, send email to requestor for BPOM & Regional Process Status Notification
		#
		
		$log['app_id'] = $datasession['app_id'];
		$log['data_trans_type'] = 'DATA CHANGES';
		$log['username'] = $datasession['username'];
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $table_name.$log['data_changes'];
		$this->update_log($log);
	}
	
	function clear_existing_data_checkbox($formtype,$checkbox_table_name,$data_menu,$data_id) {
		$data_id = $this->get_data_id_from_hash_link($data_id,$table_name);

		if ($formtype == 'mainform') { $table_name = $data_menu['full_table_name'];}
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}

		$this->db->where($table_name.'_id', $data_id);
   		$this->db->delete($checkbox_table_name); 		
	}

	function get_data_checkbox($formtype,$checkbox_table_name,$data_menu,$data_id,$checkbox_selections_origin_table) {
		if ($formtype == 'mainform') { $table_name = $data_menu['full_table_name'];}
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}

		$this->db->select($checkbox_selections_origin_table.'_id');
		$this->db->where($table_name.'_id', $data_id);
		$this->db->from($checkbox_table_name);
		
		$tmp = array();
		foreach ($this->db->get()->result_array() as $key => $value) {
			$tmp[] = $value[$checkbox_selections_origin_table.'_id'];
		}
		
		return $tmp;
	}

	function get_upperlevel_extended_data_detail($extended_data_table_name,$extended_data_id,$extended_data_field_name) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('full_table_name,field_name');
		$this->db->from('refforms');
		$this->db->join('refmenu','refmenu.Id = refforms.menu_id');
		$this->db->where('refforms.Id',$extended_data_field_name);
		
		$query_upperlevel_extended_table_data = $this->db->get()->result();

		$this->db->query('use '.$current_db);
		$query_upperlevel_extended_full_table_name = $query_upperlevel_extended_table_data[0]->full_table_name;
		$query_upperlevel_extended_field_name = $query_upperlevel_extended_table_data[0]->field_name;

		$this->db->select($query_upperlevel_extended_field_name);
		$this->db->from($query_upperlevel_extended_full_table_name);
		$this->db->join($extended_data_table_name,$extended_data_table_name.'.'.$query_upperlevel_extended_full_table_name.'_Id = '.$query_upperlevel_extended_full_table_name.'.Id');
		$this->db->where($extended_data_table_name.'.Id',$extended_data_id);
		
		$tmp['extended_upperlevel_query_result'] = $this->db->get()->result();

		$tmp['extended_upperlevel_value'] = $tmp['extended_upperlevel_query_result'][0]->$query_upperlevel_extended_field_name;
		$tmp['extended_upperlevel_field_name'] = $query_upperlevel_extended_field_name;

		return $tmp;
	}

	function auto_insert_extended_data_id_handling($data_menu,$data_id,$datasession) {
		$current_conn = $this->db;
		$current_db = $current_conn->database;
		$app_init = $this->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->db->query('use '.$applat_db);

		$this->db->select('to_menu_id');
		$this->db->where('from_menu_id', $data_menu['id']);
		$this->db->where('is_auto_insert_extended_data_id', 1);
		$this->db->where('isdelete', 0);
		$this->db->from('refextend_datamenu');
		$this->db->limit(1);
		
		$query = $this->db->get()->result();

		$this->db->query('use '.$current_db);
		
		if (count($query) == 1) {
			$destination_menu_id = $query[0]->to_menu_id;
			$query = array();

			$current_conn = $this->db;
			$current_db = $current_conn->database;
			$app_init = $this->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->db->query('use '.$applat_db);

			$this->db->select('full_table_name,is_workflowdata');
			$this->db->where('Id', $destination_menu_id);
			$this->db->from('refmenu');
			$this->db->limit(1);
			
			$query = $this->db->get()->result();

			$this->db->query('use '.$current_db);

			if (count($query) == 1) {
				$destination_full_table_name = $query[0]->full_table_name;
				$is_workflowdata = ($query[0]->is_workflowdata == 1) ? TRUE : FALSE ;
				
				$data = array(
				        'createby' => $datasession['username'],
		        		'createdate' => $this->datetime->get_current_datetime(),
		        		'isdelete' => 0,
		        		$data_menu['full_table_name'].'_id' => $data_id
				);

				if ($is_workflowdata) {
					$tmp = array(
					        'status' => 'Draft'
					);
					$data = array_merge($data,$tmp);
				}
				
				$query = array();
				$this->db->select('Id');
				$this->db->where($data_menu['full_table_name'].'_id', $data_id);
				$this->db->where('isdelete', 0);
				$this->db->from($destination_full_table_name);
				$this->db->limit(1);
				
				$query = $this->db->get()->result();

				if (count($query) == 0) {
					$this->db->insert($destination_full_table_name, $data);
				}
			}
		}
	}

	function new_data_checkbox($datasession,$formtype,$checkbox_table_name,$data_menu,$data_id,$checkbox_selections_origin_table,$checkbox_value_array) {

		$data_id = $this->get_data_id_from_hash_link($data_id,$table_name);

		if ($formtype == 'mainform') { $table_name = $data_menu['full_table_name'];}
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}

		foreach ($checkbox_value_array as $key => $value) {
			$data = array(
					$table_name.'_id' => $data_id,
					$checkbox_selections_origin_table.'_id' =>$value, 
			        'createby' => $datasession['username'],
	        		'createdate' => $this->datetime->get_current_datetime(),
	        		'isdelete' => 0
			);

			$this->db->insert($checkbox_table_name, $data);
		}
	}

	function get_data_id_from_hash_link($hash_link,$full_table_name) {

		$this->db->select('Id');
		$this->db->from($full_table_name);
		$this->db->where('hash_link',(string) $hash_link);
		
		$query_check_hash_link =  $this->db->get()->result();
		
		if ($query_check_hash_link) {
			return $query_check_hash_link[0]->Id;
		} else {
			return 0;
		}
	}

	function generate_hash_link($full_table_name) {
		$hash_link = md5($this->datetime->get_current_datetime());
		$new_hash_link = FALSE;

		while (!$new_hash_link) {
			$this->db->select('Id');
			$this->db->from($full_table_name);
			$this->db->where('hash_link',$hash_link);
			
			$query_check_hash_link =  $this->db->get()->result();

			if ($query_check_hash_link) {
				$hash_link = md5($hash_link);
			} else {
				$new_hash_link = TRUE;
			}
		}

		return $hash_link;
	}

	#customized fot MUNDIPHARMA, ePMAP Project 2020
	function new_reviewer_data($reviewer_data,$datasession,$data_menu) {
		
		$get_current_datetime = $this->datetime->get_current_datetime();
		$reviewer_data['epmap_req_material_data_id'] = $this->get_data_id_from_hash_link($reviewer_data['epmap_req_material_data_id'],$data_menu['full_table_name']);

		$data = array('epmap_req_material_data_id' => $reviewer_data['epmap_req_material_data_id'],
						'reviewer_approver_note' => $reviewer_data['reviewer_approver_note'],
						'approver_id' => $datasession['id'],
						'reviewer_approver_date' => $get_current_datetime,
						'reviewer_approver_file' => $reviewer_data['reviewer_approver_file']);

		$tmp = array(
	        'createby' => $datasession['username'],
    		'createdate' => $get_current_datetime,
    		'isdelete' => 0,
    		'hash_link' => $this->generate_hash_link('epmap_req_material_reviewer_approver_file')
		);

		$data = array_merge($data,$tmp);

		if (array_key_exists('is_approved_with_changes', $reviewer_data)) {
			$tmp = array(
		        'is_approved_with_changes' => $reviewer_data['is_approved_with_changes']
			);

			$data = array_merge($data,$tmp);
		}

		$this->db->insert('epmap_req_material_reviewer_approver_file', $data);

		if (array_key_exists('is_bpom_required', $reviewer_data)) {
			if ($reviewer_data['is_bpom_required'] == 1) {
				$data = array(
					'is_bpom_required' => $reviewer_data['is_bpom_required'],
					'bpom_process_status' => 'In Process',
					'required_bpom_process_by' => $datasession['username']
				);
				
				$this->db->where('Id', $reviewer_data['epmap_req_material_data_id']);
				$this->db->update($data_menu['full_table_name'],$data);
			}
		}

		return NULL;
	}
	#

	function new_data($datasession,$data_menu,$form_fields,$datapost,$formtype,$subform_name = '') {
		$table_name = '';
		$log['data_changes'] = '';
		$data = array();
		
		if ($formtype == 'mainform') { $table_name = $data_menu['full_table_name'];}
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
			
			$datapost['main_id'] = $this->get_data_id_from_hash_link($datapost['main_id'],$data_menu['full_table_name']);

			$data = array_merge($data,array($data_menu['full_table_name'].'_id' => $datapost['main_id']));
		}
		
		foreach($form_fields as $form_field) {
			if ($form_field->control_type != 'checkbox' && $form_field->control_type != 'separator' && $form_field->control_type != 'extend') {
				if ($form_field->field_type == 'datetime') {
					if ($datapost[$form_field->field_name] != NULL) {
						$datapost[$form_field->field_name] = $this->datetime->convert_short_date_format_to_mysql($datapost[$form_field->field_name]);					}else{
						$datapost[$form_field->field_name] = NULL;
					}
				}
				
				$field_name = $form_field->field_name;
				$field_value = $datapost[$form_field->field_name];

				$data = array_merge($data,array($field_name => $field_value));
				$log['data_changes'] .= " zzz ".$field_name.": ".$field_value;
			}
		}

		if ($formtype == 'mainform' && $data_menu['is_workflowdata'] == 1) {
			$tmp = array();
			$tmp = array('status' => 'Draft');
			$data = array_merge($data,$tmp);

			#customized fot MUNDIPHARMA, ePMAP Project 2020
			if (strtolower($data_menu['full_table_name']) == 'epmap_req_material_data') {
				$product_prefix = $this->get_product_prefix($datapost['brand_id']);

				$tmp = array();
				$tmp = array('product_prefix' => $product_prefix['epmap_prefix']);
				$data = array_merge($data,$tmp);
			}
			#
		}
		
		if ($datapost['draft_id'] != NULL) {
			$data = array_merge($data,array('draft_id' => $datapost['draft_id']));
		}
		
		$tmp = array(
		        'createby' => $datasession['username'],
        		'createdate' => $this->datetime->get_current_datetime(),
        		'isdelete' => 0
		);
		$data = array_merge($data,$tmp);
		
		if ($formtype == 'mainform') {
			if ($this->dp_eform->check_if_menu_has_extended_data($data_menu)) {
				$tmp = array()	;
				$extended_field_id = $this->get_data_extend_from_table($data_menu)['full_table_name'].'_id';
				$tmp = array(
				       $extended_field_id => $datapost[$extended_field_id]
				);
				$data = array_merge($data,$tmp);
			}	
		}

		$hash_link = $this->generate_hash_link($table_name);
		$data = array_merge($data,array('hash_link' => $hash_link));

		$this->db->insert($table_name, $data);
		
		$log['app_id'] = $datasession['app_id'];
		$log['data_trans_type'] = 'DATA ENTRY';
		$log['username'] = $datasession['username'];
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $table_name.$log['data_changes'];
		$this->update_log($log);
	}
	
	function delete_data($datasession,$data_menu,$id,$formtype,$subform_name) {
		$table_name = '';
		if ($formtype == 'subform') {
			$query = $this->get_data_subform($data_menu['id'],$subform_name);
			$row = $query[0];
			$table_name = $row->full_table_name;
		}else{
			$table_name = $data_menu['full_table_name'];
		}
		
		$id = $this->get_data_id_from_hash_link($id,$table_name);

		$data = array(
		'isdelete' =>1,
        'deleteby' => $datasession['username'],
        'deletedate' => $this->datetime->get_current_datetime()
		);
		
		$this->db->where('Id', $id);
		$this->db->update($table_name, $data);
		
		$log['app_id'] = $datasession['app_id'];
		$log['data_trans_type'] = 'DATA REMOVE';
		$log['username'] = $datasession['username'];
		$log['ip_address'] = $this->input->ip_address();
		$log['data_changes'] = $table_name." zzz Id: ".$id;
		$this->update_log($log);
	}
}