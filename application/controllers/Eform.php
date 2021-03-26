<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 */
class Eform extends CI_Controller {
	private $view_name = 'Vwhome';
	private $control_name = 'eform';
	private $content_name = 'content/content_eform';

	function __construct() {
		parent::__construct();
		$this->load->library($this->content_name,'','content');
		$this->load->library('app_initializer','','app_init');
		$this->load->library('wf','','wf');
		$this->load->model('data_process_eform','dp_eform',TRUE);
		$this->load->model('data_process_translate','',TRUE);
		$this->load->model('user','',TRUE);
		$this->load->model('data_process_timeset','datetime',TRUE);
	}
	

	function display_reference_column($url) {
		
		$this->load->library('shared_variables','','shared_variables');

		$form_name = $this->shared_variables->display_reference_column;

		if (in_array(strtolower($url), $form_name)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function load_datagrid($data_menu,$menu_name,$datapost,$limit,$offset,$formtype,$subform_name,$main_id = 0,$act='') {

		$get_application_users = array();
		$dropdown_data_array = array();
		$approvers_list_array = array();

		$datasession = $this->session->userdata('logged_in');
		$language = $datasession['language'];

		$query = $this->dp_eform->load_data($data_menu,$datapost,$limit,$offset,$formtype,$subform_name,$main_id);
		
		if (!$this->session->userdata('grid_fields_'.$data_menu['url'].'_'.$formtype.'_'.$subform_name)) {
			$grid_fields = $this->dp_eform->get_grid_fields($data_menu,$formtype,$subform_name);
			$this->session->set_userdata('grid_fields_'.$data_menu['url'].'_'.$formtype.'_'.$subform_name,$grid_fields);
		} else {
			$grid_fields = $this->session->userdata('grid_fields_'.$data_menu['url'].'_'.$formtype.'_'.$subform_name);
		}
		
		$headings = array($this->data_process_translate->check_vocab($language,"No."));

		if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || $this->display_reference_column($data_menu['url'])) && $formtype == 'mainform') {
			array_push($headings,$this->data_process_translate->check_vocab($language,"Ref. No."));
		}

		foreach ($grid_fields as $grid_field) {
			array_push($headings,$this->data_process_translate->check_vocab($language,$grid_field->column_header));
		}

		if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1 || $this->display_reference_column($data_menu['url'])) && $formtype == 'mainform') {

			array_push($headings,$this->data_process_translate->check_vocab($language,"Requestor"));
			$get_application_users = $this->dp_eform->get_application_users();

			array_push($headings,$this->data_process_translate->check_vocab($language,"Request Status"));
			
			$data_id_string = '';
			foreach ($query as $row) {
				if ($row->status == 'Waiting approval') {
					$data_id_string .= ($data_id_string == '') ? $row->Id : ','.$row->Id;
				}
			}
			$approvers_list_array = $this->wf->get_pending_approvers_list_array($data_menu,$data_id_string);
		}

		#set dropdown data array
		foreach ($grid_fields as $grid_field) {
			if ($grid_field->control_type == 'dropdown') {
				
				$data_id_collection = "";
				$dropdown_list = array();
				$grid_field_name = $grid_field->field_name;
				
				foreach ($query as $row) {
					$data_id_collection .= ($data_id_collection == "") ? "'".$row->$grid_field_name."'" : ",'".$row->$grid_field_name."'" ;
				}

				$look_up_item['component_id'] = $grid_field->Id;
				$look_up_item['value'] = $data_id_collection;

				if ($data_id_collection != "") {
					switch ($grid_field->item_source) {
						case 'manageditems':
							$dropdown_list =  $this->dp_eform->loaddata_dropdown_look_up_item($look_up_item);
							break;
						case 'datatable':
							$dropdown_list =  $this->dp_eform->loaddata_dropdown_look_up_value($look_up_item);
							$field_dropdown_text = $dropdown_list['field_name'];
							$dropdown_list =  $dropdown_list['query'];
							break;
						case 'appusers':
							$dropdown_list =  $this->dp_eform->loaddata_dropdown_look_up_user($look_up_item);
							break;
						default:
							# code...
							break;
					}
				}

				foreach ($query as $row) {
				
					$item_text = '';

					switch ($grid_field->item_source) {
						case 'manageditems':
							foreach ($dropdown_list as $dropdown_list_item) {
								if ($row->$grid_field_name == $dropdown_list_item->item_value) {
									$item_text = $dropdown_list_item->item_text;
								}
							}
							break;
						case 'datatable':
							foreach ($dropdown_list as $dropdown_list_item) {
								if ($row->$grid_field_name == $dropdown_list_item['Id']) {
									$item_text = $dropdown_list_item[$field_dropdown_text];
								}
							}
							break;
						case 'appusers':
							foreach ($dropdown_list as $dropdown_list_item) {
								if ($row->$grid_field_name == $dropdown_list_item->user_id) {
									$item_text = $dropdown_list_item->fullname;
								}
							}
							break;
						default:
							# code...
							break;
					}

					$dropdown_data_array[$row->Id][$grid_field_name][$row->$grid_field_name] = $item_text;
				}
			}
		}
		#
		
		$tmpl = array ('table_open' => '<table id=\'dbgrid\' class=\'table table-bordered table-striped\'>',
				'heading_cell_start' => '<th style="text-align:center">');
		$this->table->set_template($tmpl);
		$this->table->set_heading($headings);
		
		$data_menu_tmp['is_insert_disable'] = $data_menu['is_insert_disable'];
		$data_menu_tmp['is_edit_disable'] = $data_menu['is_edit_disable'];
		$data_menu_tmp['is_delete_disable'] = $data_menu['is_delete_disable'];
					
		$no = $offset;

		$subform_disable_delete = FALSE;
		$subform_disable_edit = FALSE;

		if ($formtype == 'subform') {
			$query_subform = $this->dp_eform->get_data_subform($data_menu['id'],$subform_name);
			if ($query_subform) {
				$row_subform = $query_subform[0];

				if ((int) $row_subform->is_delete_disable == 1) { $subform_disable_delete = TRUE;}
				if ((int) $row_subform->is_edit_disable == 1) { $subform_disable_edit = TRUE;}
			}
		}
		
		foreach ($query as $row) {
			////////// Link on the right column handling ///////////////////////////////
				if ($this->wf->lock_data_editing($data_menu,($formtype == 'mainform')? $row->hash_link : $main_id)) {
					$data_menu['is_insert_disable'] = 1;
					$data_menu['is_edit_disable'] = 1;
					$data_menu['is_delete_disable'] = 1;
				} else {
					$data_menu['is_insert_disable'] = $data_menu_tmp['is_insert_disable'];
					$data_menu['is_edit_disable'] = $data_menu_tmp['is_edit_disable'];
					$data_menu['is_delete_disable'] = $data_menu_tmp['is_delete_disable'];
				}
				
				$btn_edit_label = ($data_menu['is_edit_disable'] == 1)? "Detail" : "Edit";

				if ($formtype != 'subform') {
					$btn_remove = ($data_menu['is_delete_disable'] == 1)? "" : " " .anchor($this->control_name.'/remove/'.$menu_name.'/'. $row->hash_link, "[".$this->data_process_translate->check_vocab($language,"Remove")."]");
					
						$task = anchor($this->control_name.'/edit/'.$menu_name.'/'. $row->hash_link, "[".$this->data_process_translate->check_vocab($language,$btn_edit_label)."]") .$btn_remove;
				} else {
					$btn_remove_js = array();
					$btn_edit_js = array();

					if (strtolower($data_menu['url']) == 'trans_frm_bpomgov_status') {
						$btn_remove_js = array('onClick' => "
							var txtregulatorynotes = document.getElementById('txtregulatorynotes').value;
							if (txtregulatorynotes != '') {
								var localStorage = window.localStorage;
								localStorage.setItem('regulatorynotes_".$main_id."', txtregulatorynotes);
							}");
						$btn_edit_js = array('onClick' => "
							var txtregulatorynotes = document.getElementById('txtregulatorynotes').value;
							if (txtregulatorynotes != '') {
								var localStorage = window.localStorage;
								localStorage.setItem('regulatorynotes_".$main_id."', txtregulatorynotes);
							}");
					}

					if (strtolower($data_menu['url']) == 'trans_frm_regional_status') {
						$btn_remove_js = array('onClick' => "
							var txtregionnotes = document.getElementById('txtregionnotes').value;
							if (txtregionnotes != '') {
								var localStorage = window.localStorage;
								localStorage.setItem('regionnotes_".$main_id."', txtregionnotes);
							}");
						$btn_edit_js = array('onClick' => "
							var txtregionnotes = document.getElementById('txtregionnotes').value;
							if (txtregionnotes != '') {
								var localStorage = window.localStorage;
								localStorage.setItem('regionnotes_".$main_id."', txtregionnotes);
							}");
					}

					$btn_remove = ($act == 'delete' ||  $data_menu['is_edit_disable'] == 1)? "" : " " .anchor($this->control_name.'/subform/remove/'.$menu_name.'/'.$subform_name.'/'.$main_id.'/'. $row->hash_link, "[".$this->data_process_translate->check_vocab($language,"Remove")."]",$btn_remove_js);
					
					$btn_edit = anchor($this->control_name.'/subform/edit/'.$menu_name.'/'.$subform_name.'/'. $main_id.'/'.$row->hash_link, "[".$this->data_process_translate->check_vocab($language,$btn_edit_label)."]",$btn_edit_js);
					
					if ($act == 'new' && ($btn_remove != '' || $btn_edit != '')) {
						$btn_remove = '';
						$btn_edit = '';
					}

					if ($btn_remove != '' && $subform_disable_delete) {
						$btn_remove = '';
					}

					if ($btn_edit != '' && $subform_disable_edit) {
						$btn_edit = anchor($this->control_name.'/subform/edit/'.$menu_name.'/'.$subform_name.'/'. $main_id.'/'.$row->hash_link, "[".$this->data_process_translate->check_vocab($language,'Detail')."]",$btn_edit_js);
					}

					$task = $btn_edit.$btn_remove;
				}
		    /////////////////////////////////////////////////////////////////////////////////////
	        	$no++;
	        	$content = array(array('data'=>$no, 'style'=>'text-align:center'));

	        	if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1  || $this->display_reference_column($data_menu['url'])) && $formtype == 'mainform') {
	        		
	        		$request_reference_number = $row->request_reference_number;
	        		
	        		array_push($content,array('data'=>$request_reference_number));
	        	}

	        	foreach ($grid_fields as $grid_field) {
					$grid_value = '';
			 		if ($grid_field->control_type != 'extend') {
						$field = $grid_field->field_name;
						$tmp_value = $row->$field;
			 		} else {
			 			///////////// if field grid is a extend component ////////////////////////////////////
			 			$tmp = "";
						$extend_component_id = $grid_field->field_name;
						$data_component = $this->dp_eform->get_data_specific_component($extend_component_id);
						$data_extended_menu = $this->dp_eform->get_data_menu($data_component[0]->menu_id);
						$extended_data_table_name = $data_extended_menu['full_table_name'];
						$extended_data_field_name = $data_component[0]->field_name;
						$extended_data_column_id = $extended_data_table_name.'_id';
						
						$is_data_subform = FALSE;
						if ($data_component[0]->sub_form_id != NULL) {
							$is_data_subform = TRUE;
							$extended_data_table_name = $this->dp_eform->get_data_subform_by_id($data_component[0]->sub_form_id)[0]->full_table_name;
						}
						$extended_field =  $extended_data_table_name.'_id';
						$extended_data_id = $row->$extended_field;
						$extended_data_detail = $this->dp_eform->get_extended_data_detail($data_menu,$act,$formtype,$extended_data_field_name,$extended_data_table_name,$extended_data_id,$is_data_subform,$extended_data_column_id);
						if (count($extended_data_detail) >= 1) {
							if (!is_numeric($extended_data_field_name)) {
								$row_data_detail = $extended_data_detail[0];
								$tmp_value = $row_data_detail->$extended_data_field_name;
							} else {
								$row_data_detail = array();
								$tmp_value = $this->dp_eform->get_upperlevel_extended_data_detail($extended_data_table_name,$extended_data_id,$extended_data_field_name)['extended_upperlevel_value'];
							}
							
							if (count($extended_data_detail) > 1) {
								$tmp .= "<ul>";	
							}
							
							foreach($extended_data_detail as $row_data_detail) {
								switch ($data_component[0]->control_type)	{
									case 'text':
										$tmp .= (count($extended_data_detail) > 1)? '<li>'.$row_data_detail->$extended_data_field_name.'</li>' : $row_data_detail->$extended_data_field_name;
										break;
									case 'fileuploader':
										if ($row_data_detail->$extended_data_field_name != NULL && $row_data_detail->$extended_data_field_name != "") {
											
											$app_init = $this->app_init->app_init();
											$file_dir = $app_init['file_upload_dir'];

											$filename_contains_prefix = FALSE;
											$filename_link = $row_data_detail->$extended_data_field_name;

											if (strpos($filename_link, '__') !== false) {
											    $filename_contains_prefix = TRUE;
											}

											if ($filename_contains_prefix) {
												$filename_array = explode('__', $filename_string);
												$filename_link = $filename_array[1];
											}

											$tmp .= (count($extended_data_detail) > 1)? '<li>'.anchor_popup($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name,$filename_link,array()).'</li>' : anchor_popup($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name,$filename_link,array());
										}
										break;
									case 'datepicker':
										$tmp .= (count($extended_data_detail) > 1)? '<li>'.$this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name).'</li>' : $this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name);
										break;
									case 'datepickerforward':
										$tmp .= (count($extended_data_detail) > 1)? '<li>'.$this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name).'</li>' : $this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name);
										break;
									case 'dropdown':
										$options = array();
										if ($data_component[0]->item_source == 'manageditems') {
											$query_tmp = $this->dp_eform->get_selectionitems($data_component[0]->Id);
										}
										
										if ($data_component[0]->item_source == 'datatable') {
											$query_tmp = $this->dp_eform->get_selectiondatatableitems($data_component[0]->Id);
										}
										
										if ($data_component[0]->item_source == 'appusers') {
											$datasession = $this->session->userdata('logged_in');
											$query_tmp = $this->dp_eform->get_selectiondatataappusers($datasession['app_id']);
										}
										
										if ($data_component[0]->item_source == 'manageditems' || $data_component[0]->item_source == 'datatable' || $data_component[0]->item_source == 'appusers') {
											foreach ($query_tmp['query'] as $row_tmp) {
												$options[$row_tmp->item_value] = $row_tmp->item_text;		
											}
										}
										
										if ($data_component[0] == 'monthpicker') {
											$options = $this->get_array_months();
										}

										if ($data_component[0] == 'year_type_1') {
											$from_year = date('Y') - 0;
											$until_year = date('Y') + 1;
											while ($from_year <= $until_year) {
												$options[$from_year] = $from_year;
												$from_year++;
											}
										}
										
										if ($data_component[0] == 'year_type_2') {
											$from_year = date('Y') - 10;
											$until_year = date('Y') + 0;
											while ($from_year <= $until_year) {
												$options[$from_year] = $from_year;
												$from_year++;
											}
										}
										
										if ($data_component[0] == 'year_type_3') {
											$from_year = date('Y') - 60;
											$until_year = date('Y') - 18;
											while ($from_year <= $until_year) {
												$options[$from_year] = $from_year;
												$from_year++;
											}
										}	
										
										$tmp .= (count($extended_data_detail) > 1)? '<li>'.$options[$row_data_detail->$extended_data_field_name].'</li>' : $options[$row_data_detail->$extended_data_field_name];		
										break;
									default:
									$tmp = "";
								}		
							}
							
							if (count($extended_data_detail) > 1) {
								$tmp .= "</ul>";	
							}

							if (!is_numeric($extended_data_field_name)) { 
								$tmp_value = $tmp;
							}
						} else {
							$tmp_value = NULL;	
						}
						///////////////////////////////////////////////////////////////////////////////////////////////////
			 		}
			 		
			 		if ($grid_field->control_type != 'dropdown') {
			 			switch ($grid_field->field_type) {
							case 'decimal':
								$grid_value = number_format($tmp_value,2,',','.');
								break;
							case 'int':
								$grid_value = number_format($tmp_value,0,',','.');
								break;
							case 'bigint':
								$grid_value = number_format($tmp_value,0,',','.');
								break;
							case 'datetime': 
								$grid_value = ($tmp_value != NULL)? $this->datetime->convert_mysql_date_format_to_short_string($tmp_value) : $tmp_value;
								
								break;
							default:
								if ($grid_field->control_type == 'fileuploader') {
										if ($tmp_value != NULL && $tmp_value != '') {
											$app_init = $this->app_init->app_init();
											$file_dir = $app_init['file_upload_dir'];

											$filename_string = $tmp_value;

											$filename_contains_prefix = FALSE;
											$filename_link = $tmp_value;

											if (strpos($filename_link, '__') !== FALSE) {
											    $filename_contains_prefix = TRUE;
											}

											if ($filename_contains_prefix) {
												$filename_array = explode('__', $filename_string);
												$filename_link = $filename_array[1];
											}
											
											$image_thumbnail = "";
											if (file_exists($file_dir.$grid_field->upload_path.'/'.$tmp_value)) {
												$file_extension = pathinfo($file_dir.$grid_field->upload_path.'/'.$tmp_value, PATHINFO_EXTENSION);
												$file_extension = strtolower($file_extension);

												if (in_array($file_extension, array('jpg','jpeg','bmp','png','tiff'))) {
													$image_thumbnail = anchor_popup($file_dir.$grid_field->upload_path.'/'.$tmp_value,"<img src=\"".$file_dir.$grid_field->upload_path.'/'.$tmp_value."\" width=\"100\" height=\"100\">",array())."<br>";
												}

												if (in_array($file_extension, array('pdf'))) {
													$image_thumbnail = anchor_popup($file_dir.$grid_field->upload_path.'/'.$tmp_value,"<img src=\"./assets/images/pdf_icon.jpg\" width=\"50\" height=\"50\">",array())."<br>";
												}
											}

											$grid_value = $image_thumbnail.anchor_popup($file_dir.$grid_field->upload_path.'/'.$tmp_value,$filename_link,array());
										} else {
											$grid_value = $tmp_value;
										}
									} else {
										$grid_value = $tmp_value;
									}
						}
					} else {
						switch ($grid_field->item_source) {
							case 'datatable':
								$look_up_data['component_id'] = $grid_field->Id;
								$look_up_data['value'] = $tmp_value;
								
								//$grid_value =  $this->dp_eform->look_up_value($look_up_data,$data_menu,$formtype);
								$grid_value =  $dropdown_data_array[$row->Id][$grid_field->field_name][$tmp_value];
								break;
							case 'manageditems':
								$look_up_item['component_id'] = $grid_field->Id;
								$look_up_item['value'] = $tmp_value;
								
								//$grid_value =  $this->dp_eform->look_up_item($look_up_item);
								$grid_value =  $dropdown_data_array[$row->Id][$grid_field->field_name][$tmp_value];
								break;
							case 'appusers':
								#$look_up_user['value'] = $tmp_value;
							
								#$grid_value =  $this->dp_eform->look_up_user($look_up_user);
								$grid_value =  $dropdown_data_array[$row->Id][$grid_field->field_name][$tmp_value];
								break;
							case 'monthpicker':
								$options = $this->get_array_months();
								if ($tmp_value != '') {
									$grid_value = $options[$tmp_value];
								} else {
									$grid_value = $tmp_value;
								}
								break;
							default:
								if (in_array($grid_field->item_source, array('year_type_1','year_type_2','year_type_3'))) {
									$grid_value = $tmp_value;
								}
								break;
						}
					}
					
					//array_push($content,array('data'=>$grid_value));
					if (in_array($grid_field->field_type, array('bigint','int'))) {
						if ($grid_field->control_type == 'dropdown') {
							array_push($content,array('data'=>$grid_value));
						} else {
							array_push($content,array('data'=>$grid_value, 'style'=>'text-align:right'));
						}
					} else {
						if ($grid_field->control_type == 'fileuploader') {
							array_push($content,array('data'=>$grid_value, 'style'=>'text-align:center'));
						} else {
							array_push($content,array('data'=>$grid_value));
						}
					}
				}

				if (($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1  || $this->display_reference_column($data_menu['url'])) && $formtype == 'mainform') {
					//$requestor_string = $this->dp_eform->get_user_fullname($row->createby);
					
					$requestor_string = (array_key_exists(strtoupper($row->createby), $get_application_users)) ? $get_application_users[strtoupper($row->createby)] : '' ;
					array_push($content,array('data'=>$requestor_string));

					$status_string = "";
					if ($row->status != 'Waiting approval') { #append status approval
						$status_string = $this->translate_status($row->status);
					} else {
						//$status_string = $this->translate_status($row->status).$this->wf->get_pending_approvers_list($data_menu,$row->Id);
						$status_string = $this->translate_status($row->status);
						$status_string .= (array_key_exists($row->Id, $approvers_list_array)) ? $approvers_list_array[$row->Id] : '';
					}

					array_push($content,array('data'=>$status_string));
				}

				array_push($content,array('data'=>$task));
		     	
		     	if ($data_menu['is_approval'] != 1) {
		     		$this->table->add_row($content);
		     	} else {
		     		if ($formtype == 'subform') {
		     			$this->table->add_row($content);
		     		} else {
		     			if ($this->wf->check_is_data_displayed($data_menu,$row->Id,$datasession)) {
		     				$this->table->add_row($content);
			     		} else {
			     			if ($this->session->userdata($data_menu['url'].'_filter') || $this->session->userdata($data_menu['url'].'_show_request')) {
			     				$this->table->add_row($content);
			     			} else {
			     				$no--;
			     			}
			     		}
		     		}
		     	}
			}
		$query = NULL;
		$dropdown_data_array = array();
		return $this->table->generate();
	}
	
	function translate_status($status) {
		/*if (strtoupper($status) == strtoupper("Waiting Approval")) { $status = "Menunggu Persetujuan";}
		if (strtoupper($status) == strtoupper("Full Approve")) { $status = "Proses Persetujuan Selesai";}
		if (strtoupper($status) == strtoupper("Reject")) { $status = "Ditolak";}
		if (strtoupper($status) == strtoupper("Revise")) { $status = "Perlu Revisi";}*/

		return $status;
	}
	#

	function get_array_months() {
		$datasession = $this->session->userdata('logged_in');
		$language = $datasession['language'];

		$options[NULL] = NULL;
		$options['JAN'] = $this->data_process_translate->check_vocab($language,'January');
		$options['FEB'] = $this->data_process_translate->check_vocab($language,'February');
		$options['MAR'] = $this->data_process_translate->check_vocab($language,'March');
		$options['APR'] = $this->data_process_translate->check_vocab($language,'April');
		$options['MAY'] = $this->data_process_translate->check_vocab($language,'May');
		$options['JUN'] = $this->data_process_translate->check_vocab($language,'June');
		$options['JUL'] = $this->data_process_translate->check_vocab($language,'July');
		$options['AUG'] = $this->data_process_translate->check_vocab($language,'August');
		$options['SEP'] = $this->data_process_translate->check_vocab($language,'September');
		$options['OCT'] = $this->data_process_translate->check_vocab($language,'October');
		$options['NOV'] = $this->data_process_translate->check_vocab($language,'November');
		$options['DEC'] = $this->data_process_translate->check_vocab($language,'December');

		return $options;
	}

	function remove_fu($menu_name,$control_name,$data_id,$formtype = 'mainform',$sub_form_name = '',$data_id_subform = '') {
		$data_menu = $this->dp_eform->get_data_menu($menu_name);
		
		$form_fields = $this->dp_eform->get_form_fields_for_data_processing($data_menu['id'],$formtype,"edit",$data_id,$sub_form_name);
		$fu_fieldname = "";

		foreach ($form_fields as $form_field) {
			if ($form_field->control_name == $control_name) {
				$fu_fieldname = $form_field->field_name;
				break;
			}
		}

		if ($fu_fieldname!= "") {
			if ($formtype == 'mainform') {
				$data_id_remove_file = $data_id;
			} else {
				$data_id_remove_file = $data_id_subform;
			}
			$this->dp_eform->remove_file_upload($data_menu,$fu_fieldname,$data_id_remove_file,$form_fields,$formtype);
		}

		if ($formtype == 'mainform') {
			redirect($this->control_name.'/edit/'.$menu_name.'/'.$data_id);
		} else {
			redirect($this->control_name.'/subform/edit/'.$menu_name.'/'.$sub_form_name.'/'.$data_id.'/'.$data_id_subform);
		}
	}

	function get_form_components($java_alert,$validate_revise,$review_array,$formtype,$language,$form_fields,$data_detail,$task,$datapost,$data_menu,$main_id = 0) {

		$formtype_tmp = $formtype;
		$formtype = $formtype_tmp['formtype'];
		$form_name = $formtype_tmp['form_name'];

		$subform_disable_insert = FALSE;
		$subform_disable_edit = FALSE;

		if ($formtype == 'subform') {
			$query = $this->dp_eform->get_data_subform($data_menu['id'],$form_name);
			$row = $query[0];
			
			if ($task == 'edit' && (int)$row->is_edit_disable == 1) {
				$subform_disable_edit = TRUE;
			}

			if ($task == 'new' && (int)$row->is_insert_disable == 1) {
				$subform_disable_insert = TRUE;
			}
		}

		$re_input = (count($datapost) >= 1)? TRUE : FALSE;
		
		$components = "";
		$component = "";
		$javas = "";
		$java = "";
		$js_onchange_event = array();
		
		$review_status = array();
		$review_note = array();

		if (!$validate_revise) {
			$review_status = $review_array['review_status'];
			$review_note = $review_array['review_note'];
		}

		#
		$settlement_status_close = FALSE;
		#

		foreach ($form_fields as $form_field) {

			$form_field->is_disabled = ($subform_disable_insert || $subform_disable_edit) ? 1 : $form_field->is_disabled ;

			#set readonly or disable if settlement is close
			$form_field->is_disabled = ($settlement_status_close) ? 1 : $form_field->is_disabled ;
			#
			
			$readonly = ($task == 'delete' || $form_field->is_disabled == 1)? 'readonly' : '';
			$disabled = ($task == 'delete' || $form_field->is_disabled == 1)? 'disabled' : '';

			$extended_field_default_value = array();
			
			if ($form_field->control_type != 'extend' && $form_field->control_type != 'separator') {
				if ($form_field->control_type != 'checkbox') {
					if (!array_key_exists($form_field->field_name, $datapost)) {
						$datapost[$form_field->field_name] = $data_detail[$form_field->field_name];
					}

					$extended_field_default_value = $this->dp_eform->get_extended_field_default_value($form_field->Id);

					if (!$extended_field_default_value) {
						$data_value = (count($datapost) >= 1)? html_escape($datapost[$form_field->field_name]) : html_escape($data_detail[$form_field->field_name]); /// <--- if reinput set value from user input else set from db
					} else {
						$extended_data_id = $this->session->userdata('selection_id');

						$data_value = (count($datapost) >= 1 && $datapost[$form_field->field_name] != '')? html_escape($datapost[$form_field->field_name]) : html_escape($this->dp_eform->get_extended_default_value($extended_data_id,$extended_field_default_value)); /// <--- if reinput set value from user input else set from extended field as default value
					}
				} else {
					$checkbox_selections_origin_table = $this->dp_eform->get_checkbox_selections_origin_table($form_field->Id);

					$data_checkbox_array = $this->dp_eform->get_data_checkbox($formtype,$form_field->field_name,$data_menu,$data_detail['Id'],$checkbox_selections_origin_table);

					$data_value = ($re_input)? $datapost[$form_field->field_name] : $data_checkbox_array; /// <--- if reinput set value from user input else set from db
				}
			} else {
				$data_value='';
			}

			if ($form_field->control_type != 'separator') {
				$label = $this->data_process_translate->check_vocab($language,$form_field->control_label);
				
				//// provide checkbox for each question for requestors ////////////
				$get_update_log_field_review = array();
				$reviewer_note_per_field = "";
				if ($data_menu['is_workflowdata'] == 1 && $form_field->control_type != 'extend' && $form_field->control_type != 'separator' && $form_field->control_type != 'hidden') {

					$get_update_log_field_review = $this->dp_eform->get_update_log_field_review($main_id,$data_menu,$form_field->field_name);
					
					if ($get_update_log_field_review)  {
						$label = "<font color=\"red\">".$label."</font>";
						$reviewer_note_per_field = "&nbsp;<i>".$get_update_log_field_review[0]->fullname." : ".$get_update_log_field_review[0]->comm_msg."</i>";
					}
				}
				////////////////////////////////////////////////////////////////////////////////////////
				
				$label .= ($form_field->required == 1)? "<font color=\"red\">*</font>" : "";

				//// provide checkbox for each question for approvers ////////////
				if ($data_menu['is_approval'] == 1 && $form_field->control_type != 'extend' && $form_field->control_type != 'separator' && $form_field->control_type != 'hidden' && $this->dp_eform->get_field_to_review($data_menu,$form_field->Id)) {
					
					$review_checked = FALSE;
					if (isset($review_status[$form_field->Id])) {
						$review_checked = TRUE;
					}

					$review_note_value = '';
					if (isset($review_note[$form_field->Id])) {
						$review_note_value = $review_note[$form_field->Id];
					}

					$label .= '&nbsp;&nbsp;&nbsp;&nbsp;'.form_checkbox('review_status['.$form_field->Id.']', 1,$review_checked).'&nbsp;&nbsp;&nbsp;&nbsp;'.form_input(array('name' => 'review_note['.$form_field->Id.']','id' => 'review_note['.$form_field->Id.']','value' => $review_note_value));
				}
				////////////////////////////////////////////////////////////////////////////////////////
				
				$component = ($form_field->control_type != 'hidden')? "<label for=\"label\">".$label."</label>" : "";
				$component .= $reviewer_note_per_field;

			} else {
				$component = "";
			}

			$java = "";
			
			////////////// for extended data ///////////////////////////////////////////
			if ($form_field->control_type == 'extend') {
				$tmp = "";
				$extend_component_id = $form_field->field_name;
				$data_component = $this->dp_eform->get_data_specific_component($extend_component_id);
				
				$data_extended_menu = $this->dp_eform->get_data_menu($data_component[0]->menu_id);	
				$extended_data_table_name = $data_extended_menu['full_table_name'];
				$extended_data_field_name = $data_component[0]->field_name;
				$extended_data_column_id = $extended_data_table_name.'_id';
				$extended_data_field_type = $data_component[0]->field_type;
				
				$is_data_subform = FALSE;
				if ($data_component[0]->sub_form_id != NULL) {
					$is_data_subform = TRUE;
					$extended_data_table_name = $this->dp_eform->get_data_subform_by_id($data_component[0]->sub_form_id)[0]->full_table_name;
					}
				
				$extended_data_id = $this->session->userdata('selection_id');
				
				if (!is_numeric($extended_data_field_name)) {
								$extended_data_detail = $this->dp_eform->get_extended_data_detail($data_menu,$task,$formtype,$extended_data_field_name,$extended_data_table_name,$extended_data_id,$is_data_subform,$extended_data_column_id);
				} else {
					$extended_upperlevel_data_detail = $this->dp_eform->get_upperlevel_extended_data_detail($extended_data_table_name,$extended_data_id,$extended_data_field_name);

					$extended_data_detail = $extended_upperlevel_data_detail['extended_upperlevel_query_result'];
					$extended_data_field_name = $extended_upperlevel_data_detail['extended_upperlevel_field_name'];
				}

				if (count($extended_data_detail) >= 1) {
					if (count($extended_data_detail) > 1) {
						$tmp .= "<ul>";	
					}
					foreach($extended_data_detail as $row_data_detail) {
						switch ($data_component[0]->control_type)	{
							case 'extend':
								$tmp .= $row_data_detail->$extended_data_field_name;
								break;
							case 'text':
								$extended_data_field_value = $row_data_detail->$extended_data_field_name;
								if (in_array($extended_data_field_type, array('int','bigint'))) {
									$extended_data_field_value = number_format($extended_data_field_value,0,',','.');
								}

								$tmp .= (count($extended_data_detail) > 1)? '<li>'.$extended_data_field_value.'</li>' : $extended_data_field_value;
								break;
							case 'fileuploader':
								$filename_string = $row_data_detail->$extended_data_field_name;
								$filename_array = explode('__',$filename_string);
								if (count($filename_array)>=2) {
									$filename_string = $filename_array[1];
								}
								
								$app_init = $this->app_init->app_init();
								$file_dir = $app_init['file_upload_dir'];
								$geotag_location = "";

								if (file_exists($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name) && $row_data_detail->$extended_data_field_name != '') {
									$file_extension = pathinfo($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name, PATHINFO_EXTENSION);
									$file_extension = strtolower($file_extension);

									if (in_array($file_extension, array('jpg','jpeg','bmp','png','tiff'))) {
										$tmp .= anchor_popup($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name,"<img src=\"".$file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name."\" width=\"150\" height=\"150\">",array())."<br>";

										$this->load->library('image_geotags','','geolocation');
										$geolocation_array = $this->geolocation->get_geolocation($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name);

										if (!$geolocation_array) {
											$geotag_location = "<br>".$this->data_process_translate->check_vocab($language,"Geo Location")." : <i>".$this->data_process_translate->check_vocab($language,"Data Not Available")."</i>";
										} else {
											$geo_lat = $geolocation_array['lat'];
											$geo_long = $geolocation_array['lng'];
											$geotag_location = "<br>".$this->data_process_translate->check_vocab($language,"Geo Location")." : <i>".anchor_popup('gmap_popup/index/'.$geo_lat.'/'.$geo_long,$geo_lat.",".$geo_long,array())."</i>";
										}
									}
								
									$tmp .= (count($extended_data_detail) > 1)? '<li>'.anchor_popup($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name,$filename_string,array()).'</li>' : anchor_popup($file_dir.$data_component[0]->upload_path.'/'.$row_data_detail->$extended_data_field_name,$filename_string,array());

									$tmp .= $geotag_location;
								}
								break;
							case 'datepicker':
								$tmp .= (count($extended_data_detail) > 1)? '<li>'.$this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name).'</li>' : $this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name);
								break;
							case 'datepickerforward':
								$tmp .= (count($extended_data_detail) > 1)? '<li>'.$this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name).'</li>' : $this->datetime->convert_mysql_date_format_to_short_string($row_data_detail->$extended_data_field_name);
								break;
							case 'dropdown':
								$options = array();
								if ($data_component[0]->item_source == 'manageditems') {
									$query = $this->dp_eform->get_selectionitems($data_component[0]->Id);
								}
								
								if ($data_component[0]->item_source == 'datatable') {
									$query = $this->dp_eform->get_selectiondatatableitems($data_component[0]->Id);
									$query = $query['query'];
								}
								
								if ($data_component[0]->item_source == 'appusers') {
									$datasession = $this->session->userdata('logged_in');
									$query = $this->dp_eform->get_selectiondatataappusers($datasession['app_id']);
								}
								
								if ($data_component[0]->item_source == 'manageditems' || $data_component[0]->item_source == 'datatable' || $data_component[0]->item_source == 'appusers') {
									foreach ($query as $row) {
										$options[$row->item_value] = $row->item_text;		
									}
								}
								
								if ($data_component[0] == 'monthpicker') {
									$options = $this->get_array_months();
								}

								if ($data_component[0] == 'year_type_1') {
									$from_year = date('Y') - 0;
									$until_year = date('Y') + 1;
									while ($from_year <= $until_year) {
										$options[$from_year] = $from_year;
										$from_year++;
									}
								}
								
								if ($data_component[0] == 'year_type_2') {
									$from_year = date('Y') - 20;
									$until_year = date('Y') + 0;
									while ($from_year <= $until_year) {
										$options[$from_year] = $from_year;
										$from_year++;
									}
								}
								
								if ($data_component[0] == 'year_type_3') {
									$from_year = date('Y') - 60;
									$until_year = date('Y') - 18;
									while ($from_year <= $until_year) {
										$options[$from_year] = $from_year;
										$from_year++;
									}
								}

								if ($data_component[0] == 'year_type_4') {
									$from_year = date('Y') - 5;
									$until_year = date('Y') + 5;
									while ($from_year <= $until_year) {
										$options[$from_year] = $from_year;
										$from_year++;
									}
								}	

								if ($data_component[0] == 'year_type_5') {
									$from_year = date('Y') - 0;
									$until_year = date('Y') + 20;
									while ($from_year <= $until_year) {
										$options[$from_year] = $from_year;
										$from_year++;
									}
								}
								
								$tmp .= (count($extended_data_detail) > 1)? '<li>'.$options[$row_data_detail->$extended_data_field_name].'</li>' : $options[$row_data_detail->$extended_data_field_name];		
								break;
							default:
							$tmp = "";
						}		
					}
					
					if (count($extended_data_detail) > 1) {
						$tmp .= "</ul>";	
					}
				} else {
					$tmp = "";
				}
				
				$component .= "</br>".$tmp;
			}
			///////////////////////////////////////////////////////////////////////////////////
			
			////////////// for hidden component ///////////////////////////////////////////
			if ($form_field->control_type == 'hidden') {
				$component .= "<input type=\"hidden\" name=\"".$form_field->control_name."\" value=\"".$form_field->hidden_value."\">";
			}
			////////////////////////////////////////////////////////////////////////////////////
			
			///////////////// for form separator ///////////////////////////////////////////
			if ($form_field->control_type == 'separator') {
				$components .= "<table class='table table-bordered table-striped'>
								  <tr style='background-color: ".$form_field->separator_background_color.";'>
								    <td align=\"center\"><strong>".$this->data_process_translate->check_vocab($language,$form_field->control_label)."</strong></td>
								  </tr>
								</table>";
			}
			////////////////////////////////////////////////////////////////////////////
			
			////////////// for checkbox ///////////////////////////////////////////
			if ($form_field->control_type == 'checkbox') {
				$options = array();
				$js_onchange_caller = "";

				if ($form_field->js_onchange_caller != "" && $form_field->js_script != "") {
					$js_onchange_caller = " onchange=\"".$form_field->js_onchange_caller."\"";
					$js_onchange_event[$js_onchange_caller] = $form_field->js_script;
				}

				$query = $this->dp_eform->get_selectiondatatableitems($form_field->Id,$data_menu,$main_id);
				foreach ($query['query'] as $row) {
					$options[$row->item_value] = $row->item_text;
				}

				foreach ($options as $key => $value) {
					$checkbox_checked = FALSE;

					if ($data_value) {
						foreach ($data_value as $key_datapost_checkbox => $value_datapost_checkbox) {
							if ($key == $value_datapost_checkbox) {
								$checkbox_checked = TRUE;
								break;
							}
						}	
					}
					
					if ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) { 
						$checkbox_disabled = "";
					} else {
						$checkbox_disabled = "disabled";
					}
					$component .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					$component .= form_checkbox($form_field->control_name.'[]', $key, $checkbox_checked,$checkbox_disabled.$js_onchange_caller.' id="'.$form_field->control_name.'"')."&nbsp;".$value;
				}
			}
			////////////////////////////////////////////////////////////////////////
			
			///////////////// for radio button ///////////////////////////////////////////
			if ($form_field->control_type == 'radio') {
				$options = array();
				$query = array();
				$js_onchange_caller = "";

				if ($form_field->item_source == 'manageditems') {
						$query = $this->dp_eform->get_selectionitems($form_field->Id);
				}
				
				if ($form_field->item_source == 'datatable') { 
					$query = $this->dp_eform->get_selectiondatatableitems($form_field->Id,$data_menu,$main_id);
					$query = $query['query'];
				}
				
				foreach ($query as $row) {
					$options[$row->item_value] = $row->item_text;
				}

				if ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) { 

					if ($form_field->js_onchange_caller != "" && $form_field->js_script != "") {
						$js_onchange_caller = "onchange=\"".$form_field->js_onchange_caller."\"";
						$js_onchange_event[$js_onchange_caller] = $form_field->js_script;
					}

					$disable = ($form_field->is_disabled == 1) ? " disabled = 'disabled'" : "" ;

					$disable_2 = $disable;

					foreach ($options as $key => $value) {
						$radio_checked = FALSE;
						
						if ($data_value) {
							if ($key == $data_value) {
								$radio_checked = TRUE;
							}
						}

						$component .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						$component .= form_radio($form_field->control_name, $key, $radio_checked,$js_onchange_caller.' id="'.$form_field->control_name.'"'.$disable)."&nbsp;".$value; 

						$disable = $disable_2;
					}

				} else {
					if (array_key_exists($data_value,$options)) {
						$component .= "<br>".$options[$data_value];
					}
						$component .= "<br>";
				}
			}
			///////////////////////////////////////////////////////////////////////////
			
			////////////// for text area /////////////////////////////////////////// 
			if ($form_field->control_type == 'textarea') {

				$data_value = html_entity_decode($data_value);
				$data_value = str_replace("&#039;", "'", $data_value);
				
				if ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) {
					$data_ta = array(
							        'name'        => $form_field->control_name,
							        'id'          => $form_field->control_name,
							        'value'       => $data_value,
							        'rows'        => $form_field->control_rows,
							        'cols'        => $form_field->control_cols,
							        'style'       => 'width:100%',
							        'class'       => 'form-control'
							    );

					if ($form_field->is_disabled == 1) {
						$data_ta['disabled'] = 'disabled';
					}

					$component .= form_textarea($data_ta);
				} else {
					$component .= "<br>".nl2br($data_value);
				}
			}

			////////////////////////////////////////////////////////////////////////

			////////////// for textbox component ///////////////////////////////////
			if ($form_field->control_type == 'text' || $form_field->control_type == 'datepicker' || $form_field->control_type == 'datepickerforward' || $form_field->control_type == 'timepicker') {
				
				if ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) {

					$disable_keyed_in = "";
					$yellow_box = "";
					if ($java_alert) {
						if ($java_alert['form_control_name'] == $form_field->control_name) {
							$yellow_box = "style=\"background-color: yellow\"";
						}
					}

					$validate = ($form_field->is_numeric_only == 1) ? "onkeyup=\"validate_onkeypress(this)\"" : "";

					$validate = (in_array($form_field->field_type, array('int','bigint'))) ? "onkeydown=\"return numbersonly(this, event);\" onkeyup=\"javascript:tandaPemisahTitik(this);\"" : $validate;			
					$data_value = (in_array($form_field->field_type, array('int','bigint'))) ? number_format( (int)$data_value,0,',','.') : $data_value;

					$timepicker = ($form_field->control_type == 'timepicker')? "timepicker" : "";
					
					if (in_array($form_field->control_type, array('datepicker','timepicker'))) {
						$disable_keyed_in = " onkeydown = \"if (event.key === 'Backspace' || event.key === 'Delete') {return true;} else {return false;}\" ";
					}

					$tmp = "<input type=\"input\" ".$disable_keyed_in." ".$yellow_box." ".$readonly." class=\"form-control ".$timepicker."\" id=\"".$form_field->control_name."\" name = \"".$form_field->control_name."\" placeholder=\"".$this->data_process_translate->check_vocab($language,$form_field->control_label)."\" value=\"".$data_value."\" maxlength=\"".$form_field->control_maxlength."\" ".$validate." >";

					switch ($form_field->control_type) {
						    case 'datepicker':
						    	if ($task != "selection") {
						    		if ($task == 'new') {
							    		$java = "$(\"#".$form_field->control_name."\").datepicker({autoclose: true,format: 'dd-M-yyyy'}).datepicker(\"setDate\", \"0\");";
							    	} else {
							    		$java = "$(\"#".$form_field->control_name."\").datepicker({autoclose: true,format: 'dd-M-yyyy'});";
							    	}

						    	}
						    	break;
						    case 'datepickerforward':
						    	if ($task == 'new') {
						    		$java = "$(\"#".$form_field->control_name."\").datepicker({autoclose: true,format: 'dd-M-yyyy',startDate: \"+0d\"}).datepicker(\"setDate\", \"0\");";
						    	} else {
						    		$java = "$(\"#".$form_field->control_name."\").datepicker({autoclose: true,format: 'dd-M-yyyy',startDate: \"+0d\"});";
						    	}
						    	break;
						    case 'timepicker':
						        $java = "$(\"#".$form_field->control_name."\").timepicker({
								          showInputs: false,
								          minuteStep: 1,
								          showMeridian: false
								        });";
						        break;
						    default:
						        $java = "";
						}

					if ($java != "" && $readonly != "") $java = "";
					//////////////////////////////////////////////////////////////////////////////////////////
					
					///////// If function add new enable and edit disabled ///////////////
					if ($task == 'edit' && $data_menu['is_edit_disable'] == 1) {
						$java = "";	
						$tmp = "</br>".$data_value;
					}
					////////////////////////////////////////////////////////////////////////////////////////
					$component .= $tmp;
				} else {
					if (in_array($form_field->field_type, array('int','bigint'))) {
						$component .= "</br>".number_format($data_value,0,',','.');
					} else {
						$component .= "</br>".$data_value;
					}
				}
			}
			//////////////////////////////////////////////////////////////////////////////////
			
			////////////// for file uploader /////////////////////////////////////////////
			if ($form_field->control_type == 'fileuploader') {
				if ($task != 'delete') {
					if (($task == 'new' && $data_menu['is_insert_disable'] != 1) || ($task == 'edit' && $data_menu['is_edit_disable'] != 1 || ($formtype == 'subform' && (!$subform_disable_edit || !$subform_disable_insert)))) {
						$component .= "<input type=\"file\" ".$disabled." id=\"".$form_field->control_name."\" name = \"".$form_field->control_name."\">";
					} else {
						$component .= "</br>";
					}
				} else {
					$component .= "</br>";	
				}				
				
				if ($data_value != "") {
					$filename_string = $data_value;
					$filename_array = explode('__',$filename_string);
					if (count($filename_array)>=2) {
						$filename_string = $filename_array[1];
					}

					$app_init = $this->app_init->app_init();
					$file_dir = $app_init['file_upload_dir'];
					$geotag_location = "";

					if (file_exists($file_dir.$form_field->upload_path.'/'.$data_value)) {
						$file_extension = pathinfo($file_dir.$form_field->upload_path.'/'.$data_value, PATHINFO_EXTENSION);
						$file_extension = strtolower($file_extension);

						if (in_array($file_extension, array('jpg','jpeg','bmp','png','tiff'))) {
							/*$component .= "<br>".anchor_popup($file_dir.$form_field->upload_path.'/'.$data_value,"<img src=\"".$file_dir.$form_field->upload_path.'/'.$data_value."\" width=\"150\" height=\"150\">",array())."<br>";

							$this->load->library('image_geotags','','geolocation');
							$geolocation_array = $this->geolocation->get_geolocation($file_dir.$form_field->upload_path.'/'.$data_value);

							if (!$geolocation_array) {
								$geotag_location = "<br>".$this->data_process_translate->check_vocab($language,"Geo Location")." : <i>".$this->data_process_translate->check_vocab($language,"Data Not Available")."</i>";
							} else {
								$geo_lat = $geolocation_array['lat'];
								$geo_long = $geolocation_array['lng'];
								$geotag_location = "<br>".$this->data_process_translate->check_vocab($language,"Geo Location")." : <i>".anchor_popup('gmap_popup/index/'.$geo_lat.'/'.$geo_long,$geo_lat.",".$geo_long,array())."</i>";
							}*/

							$component .= "<br>";
						}

						if (in_array($file_extension, array('pdf'))) {
							$component .= "<br>".anchor_popup($file_dir.$form_field->upload_path.'/'.$data_value,"<img src=\"./assets/images/pdf_icon.jpg\" width=\"50\" height=\"50\">",array())."<br>";
						}
					}
					$component .= "&nbsp;".anchor_popup($file_dir.$form_field->upload_path.'/'.$data_value,$filename_string,array());

					if (($task == 'new' && $data_menu['is_insert_disable'] != 1) || ($task == 'edit' && $data_menu['is_edit_disable'] != 1)) {

						if ($disabled == "") {
							$request_reference_number = "";

							if (array_key_exists("request_reference_number", $data_detail)) {
								$request_reference_number = "\\n Ref# : ".$data_detail["request_reference_number"];
							}

							if ($formtype == 'subform') {
								$data_id_remove_file = $main_id.'/subform/'.$form_name.'/'.$data_detail['hash_link'];
							} else {
								$data_id_remove_file = $main_id;
							}

							$component .= "&nbsp;".anchor(site_url($this->control_name."/remove_fu/".$data_menu['url']."/".$form_field->control_name."/".$data_id_remove_file),"[Delete File]",array('title' => "Delete File", 'onClick' => "if (Delete File ? ".$request_reference_number."\\n ".$form_field->control_label." : ".$filename_string."')) {
														        return true;
														    } else {
														        return false;
														    }"));
						}
					}

					$component .= $geotag_location;
				}
			}
			//////////////////////////////////////////////////////////////////////////////////
			
			////////////// for password component ///////////////////////////////
			if ($form_field->control_type == 'password') $component .= "<input type=\"password\" ".$readonly." class=\"form-control\" id=\"".$form_field->control_name."\" name = \"".$form_field->control_name."\" placeholder=\"".$this->data_process_translate->check_vocab($language,$form_field->control_label)."\" maxlength=\"".$form_field->control_maxlength."\">";
			
			/////////////////////////////////////////////////////////////////////////////////
			
			////////////// for dropdown component //////////////////////////////
			if ($form_field->control_type == 'dropdown') {
				$options = array();
				$options[0] = NULL;

				$js_onchange_caller = "";

				if ($form_field->js_onchange_caller != "" && $form_field->js_script != "") {
					$js_onchange_caller = "onchange=\"".$form_field->js_onchange_caller."\"";
					$js_onchange_event[$js_onchange_caller] = $form_field->js_script;
				}

				if (in_array($form_field->item_source, array('manageditems','datatable','appusers'))) {
					$query = array();

					switch ($form_field->item_source) {
						case 'manageditems':
							$query = $this->dp_eform->get_selectionitems($form_field->Id);
							break;
						case 'datatable':
							$query = $this->dp_eform->get_selectiondatatableitems($form_field->Id,$data_menu,$main_id);
							$query = $query['query'];
							break;
						case 'appusers':
							$datasession = $this->session->userdata('logged_in');
							$query = $this->dp_eform->get_selectiondatataappusers($datasession['app_id'],$form_field,$data_menu,$datasession,$formtype,$main_id,$task);
							break;
						default:
							# code...
							break;
					}

					
					foreach ($query as $row) {
						//$options[$row->item_value] = $row->item_text;

						// MODIFIED ESTABLISHED 2021, APPEND PRICE IN THE DROPDOWN FOR FOOD AND BEVERAGE
						if (in_array($form_field->field_name, array('food_id','beverage_id'))) {
							$options[$row->item_value] = $row->item_text.' - Rp.'.number_format($row->price,0,'',',');
						} else {
							$options[$row->item_value] = $row->item_text;
						}
						// [END OF] MODIFIED ESTABLISHED 2021, APPEND PRICE IN THE DROPDOWN FOR FOOD AND BEVERAGE
					}
				}
				
				if ($form_field->item_source == 'monthpicker') {
					$options = $this->get_array_months();
				}

				if ($form_field->item_source == 'year_type_1') {
					$from_year = date('Y') - 0;
					$until_year = date('Y') + 1;
					while ($from_year <= $until_year) {
						$options[$from_year] = $from_year;
						$from_year++;
					}
				}
				
				if ($form_field->item_source == 'year_type_2') {
					$from_year = date('Y') - 20;
					$until_year = date('Y') + 0;
					while ($from_year <= $until_year) {
						$options[$from_year] = $from_year;
						$from_year++;
					}
				}
				
				if ($form_field->item_source == 'year_type_3') {
					$from_year = date('Y') - 60;
					$until_year = date('Y') - 18;
					while ($from_year <= $until_year) {
						$options[$from_year] = $from_year;
						$from_year++;
					}
				}

				if ($form_field->item_source == 'year_type_4') {
					$from_year = date('Y') - 5;
					$until_year = date('Y') + 5;
					while ($from_year <= $until_year) {
						$options[$from_year] = $from_year;
						$from_year++;
					}
				}

				if ($form_field->item_source == 'year_type_5') {
					$from_year = date('Y') - 0;
					$until_year = date('Y') + 20;
					while ($from_year <= $until_year) {
						$options[$from_year] = $from_year;
						$from_year++;
					}
				}
				
			//////////////////////////////////////////////////////////////////////////////
				if ($data_menu['is_insert_disable'] != 1 || $data_menu['is_edit_disable'] != 1 || $data_menu['is_delete_disable'] != 1) {

					$options_tmp = array();

					// foreach ($options as $key => $value) {
					// 	if ($data_value && $key == $data_value) {
					// 		$options_tmp[$key] = $options[$key];
					// 	}
					// }

					// if ($options_tmp) {
					// 	$options = $options_tmp;
					// }

					$tmp = form_dropdown($form_field->control_name,$options,$data_value,'id="'.$form_field->control_name.'" class="form-control select2" style="width: 100%;"'.$disabled.' '.$js_onchange_caller);
					
					///////// If function add new enable and edit disabled ///////////////
					if ($task == 'edit' && $data_menu['is_edit_disable'] == 1) {
						$tmp = "</br>".$options[$data_value];
					}
					////////////////////////////////////////////////////////////////////////////////////////
					$component .= $tmp;
				} else {
					if ($data_value != '') {
						if ($form_field->field_name == 'requestor_user_id') {
							if (!array_key_exists($data_value,$options)) {
								$options[$data_value] = $this->dp_eform->get_user_fullname('',$data_value);	
							}

							$component .= "</br>".$options[$data_value];
						} else {
							if ($data_value == "0|") {
								$data_value = 0;
							}
							$component .= "</br>".$options[$data_value];
						}
					}
				}
			}
			
			# This part is original for all projects / common
			//	////////////////////////////////////////////////
			//$components.= ($form_field->control_type != 'timepicker')? "<div class=\"form-group\">".$component."</div>" : "<div class=\"bootstrap-timepicker\"><div class=\"form-group\">".$component."</div></div>";
			//$javas .= $java;
			
			$components.= ($form_field->control_type != 'timepicker')? "<div class=\"form-group\">".$component."</div>" : "<div class=\"bootstrap-timepicker\"><div class=\"form-group\">".$component."</div></div>";

			if (in_array($form_field->field_name, array('koordinat_unit_rumah','gps_perumahan'))) {
				$components.= "<div id=\"map-canvas\"></div><br>";
			}
			
			$javas .= $java;
		}
		if ($js_onchange_event) {
			foreach ($js_onchange_event as $js) {
				$javas .= $js;
			}
		}

		$html_components['form_components'] = $components;
		$html_components['java_functions'] = $javas;

		return $html_components;
	}
	
	function process($menu_name,$formtype,$subform_name='') {

		$approval_type = '';

		if (!$this->session->userdata('logged_in')) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		} else {
			$datasession = $this->session->userdata('logged_in');
			if (!$datasession['username'] || $datasession['username'] == "" || $datasession['username'] == NULL) {
				$this->session->set_userdata('session_expired', TRUE);
				header ("Location: ".base_url());
			}
		}
		
		$subform_name = str_replace('%20',' ',$subform_name);
		
		$datasession = $this->session->userdata('logged_in');

		if (!$datasession) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		} else {
			if (!$datasession['username'] || $datasession['username'] == "" || $datasession['username'] == NULL) {
				$this->session->set_userdata('session_expired', TRUE);
				header ("Location: ".base_url());
			}
		}
		
		$data_menu = $this->dp_eform->get_data_menu($menu_name);
		
		$id = isset($_POST['id']) ? $_POST['id'] : NULL;
		$task = isset($_POST['task']) ? $_POST['task'] : NULL;
		$datapost['main_id'] = isset($_POST['main_id']) ? $_POST['main_id'] : NULL;

		$form_fields = $this->dp_eform->get_form_fields_for_data_processing($data_menu['id'],$formtype,$task,$id,$subform_name);
		
		if ($task == 'edit' || $task == 'new') {
			$msgerror = "";
			$java_alert = array();
			$control_name_by_field = array();

			if ($data_menu['is_approval'] == NULL) {
				foreach($form_fields as $form_field) {
					if ($form_field->control_type != 'fileuploader' && $form_field->control_type != 'separator') {
						$datapost[$form_field->field_name] = isset($_POST[$form_field->control_name]) ? $_POST[$form_field->control_name] : NULL;
						$control_name_by_field[$form_field->field_name] = $form_field->control_name;
						
						if ($datapost[$form_field->field_name] != NULL && $form_field->convert_to_uppercase == 1) {
							$datapost[$form_field->field_name] = strtoupper($datapost[$form_field->field_name]);
						}

						if (in_array($form_field->field_type, array('int','bigint'))) {
							$datapost[$form_field->field_name] = str_replace(".", "", $datapost[$form_field->field_name]);
						}
						
						if ($form_field->required == 1) {
							if ($msgerror == '' && ($datapost[$form_field->field_name]  == NULL || $datapost[$form_field->field_name] == '')) {

								$msgerror = $this->data_process_translate->check_vocab($datasession['language'],$form_field->control_label)." ".$this->data_process_translate->check_vocab($datasession['language'],"cannot be empty");

								$java_alert['msg'] = $msgerror;
								$java_alert['form_control_name'] = $control_name_by_field[$form_field->field_name];

							}
						}		
					}
				}
			}
			
			$submission = isset($_POST['submission']) ? $_POST['submission'] : NULL;
			if ($data_menu['is_workflowdata'] == 1 && ($submission == 'Draft' || $submission == NULL)) {
				if ($msgerror != "") {
					$msgerror = "";
					$java_alert = array();
				}
			}

			$checkbox_fields = array();
			$checkbox_control_name = array();
			$checkbox_selections_origin_table = array();
			$checkbox_tables = array();

			$validate_revise = TRUE;
			$review_array = array();
			
			if ($msgerror == '') {
				
				foreach($form_fields as $form_field) {
					if ($form_field->control_type == 'checkbox') {
						$checkbox_tables[] = $form_field->field_name;
						$checkbox_selections_origin_table[] = $this->dp_eform->get_checkbox_selections_origin_table($form_field->Id);
					}
					
					if ($data_menu['is_approval'] == NULL) {
						if ($form_field->control_type == 'fileuploader') {
						$file = $_FILES[$form_field->control_name];
						
						if (basename($file['name'] != '')) {
							
							$username_file_string = $datasession['username'];
							$username_file_string = str_replace(" ", "", $username_file_string);
							$username_file_string = str_replace(".", "", $username_file_string);
							$username_file_string .= "_";

							$current_datetime_string = $this->datetime->get_current_datetime();
							$current_datetime_string = str_replace("/", "", $current_datetime_string);
							$current_datetime_string = str_replace(" ", "", $current_datetime_string);
							$current_datetime_string = str_replace(":", "", $current_datetime_string);
							
							$filename_string = $username_file_string.$current_datetime_string.'__'.basename($file['name']);

							$app_init = $this->app_init->app_init();
							$file_dir = $app_init['file_upload_dir'];
							$target_dir = $file_dir.$form_field->upload_path.'/';
							$target_file = $target_dir.$filename_string;
							$origin_file = $file['tmp_name'];
							
							$file_is_valid = TRUE;
							
							//// check file upload size allowed /////////////////////////////
							$max_size_allowed = $form_field->upload_max_size * 1024;
							if ($file['size'] > $max_size_allowed) {
								$file_is_valid = FALSE;
								$msgerror = $this->data_process_translate->check_vocab($datasession['language'],"allowed file size is exceeded")." : ".$form_field->control_label;
								$java_alert['msg'] = $msgerror;
								$java_alert['form_control_name'] = $form_field->control_name;
							}
							/////////////////////////////////////////////////////////////////////////////
							
							//// check file type allowed ///////////////////////////////////////
							if ($file_is_valid) {
								$file_type = pathinfo($target_file,PATHINFO_EXTENSION);
								$allowed_file_types = explode('|',$form_field->upload_types);
								foreach($allowed_file_types as $allowed_file_type) {
									if (strtoupper($file_type) != strtoupper($allowed_file_type)) {
										$file_is_valid = FALSE;
										$msgerror  = $this->data_process_translate->check_vocab($datasession['language'],"invalid file type")." : ".$form_field->control_label;
										//echo $msgerror;exit;
										$java_alert['msg'] = $msgerror;
										$java_alert['form_control_name'] = $form_field->control_name;
									} else {
										$file_is_valid = TRUE;
										$msgerror  = "";
										$java_alert['msg'] = $msgerror;
										$java_alert['form_control_name'] = "";
										break;
									}
								}
							}							
							/////////////////////////////////////////////////////////////////////////////
							
							if ($file_is_valid) {
								move_uploaded_file($origin_file,$target_file);
								$datapost[$form_field->field_name] = $filename_string;

							} else {
								$full_table_name = NULL;
								if ($formtype == 'mainform') {
									$full_table_name = $data_menu['full_table_name'];
								}
								if ($formtype == 'subform') {
									$full_table_name = $this->dp_eform->get_subform_full_table_name($data_menu['id'],$subform_name);
								}
								$data_before = $this->dp_eform->get_data_prior_change($full_table_name,$form_fields,$id);	
								$datapost[$form_field->field_name] = $data_before[$form_field->field_name];
							}
						} else {
							$full_table_name = NULL;
							if ($formtype == 'mainform') {
								$full_table_name = $data_menu['full_table_name'];
							}
							if ($formtype == 'subform') {
								$full_table_name = $this->dp_eform->get_subform_full_table_name($data_menu['id'],$subform_name);
							}
							$data_before = $this->dp_eform->get_data_prior_change($full_table_name,$form_fields,$this->dp_eform->get_data_id_from_hash_link($id,$full_table_name));
							$datapost[$form_field->field_name] = $data_before[$form_field->field_name];
						}
						
						}	
					}
					
				}	
			}			
			
			if ($data_menu['is_approval'] == 1) {
				$submission = isset($_POST['submission']) ? $_POST['submission'] : NULL;

				if (in_array($submission,array("revise","reject"))) {
					if ($msgerror == "") {
						$comm_msg = isset($_POST['wf_msg']) ? $_POST['wf_msg'] : NULL;

						if ($comm_msg == NULL || $comm_msg == "") {
							if ($submission == "revise") {
								$msgerror = "Please input message to Requestor for this request to be revised";	
							}

							if ($submission == "reject") {
								$msgerror = "Please input message to Requestor for this request to be rejected";
							}

							$java_alert['msg'] = $msgerror;
							$java_alert['form_control_name'] = "wf_msg";
							$validate_revise = FALSE;
						}
					}
				}
			}
			
			if ($msgerror == '') {
				if ($task == 'edit') {
					$submission = isset($_POST['submission']) ? $_POST['submission'] : NULL;

					if ($data_menu['is_approval'] == NULL) {
						$this->dp_eform->update_data($datasession,$data_menu,$form_fields,$datapost,$id,$formtype,$subform_name);

						if ($data_menu['is_workflowdata'] == 1) {
							$this->dp_eform->auto_insert_extended_data_id_handling($data_menu,$id,$datasession);
						}

						if ($checkbox_tables) {
							foreach ($checkbox_tables as $key => $value) {
								if ($datapost[$value]) {
									$this->dp_eform->clear_existing_data_checkbox($formtype,$value,$data_menu,$id);
									$this->dp_eform->new_data_checkbox($datasession,$formtype,$value,$data_menu,$id,$checkbox_selections_origin_table[$key],$datapost[$value]);
								}
							}
						}
						
						$msgerror = $this->data_process_translate->check_vocab($datasession['language'],"Data Changes have been saved")." ".anchor($this->control_name.'/index/'.$menu_name, "[".$this->data_process_translate->check_vocab($datasession['language'],"Return")."]");
					}
					
					////////// Submission Process ////////////////////////////////////
					if ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1) {
						if (!$this->session->userdata('logged_in')) {
							$this->session->set_userdata('session_expired', TRUE);
							header ("Location: ".base_url());
						} else {
							$datasession = $this->session->userdata('logged_in');
							if (!$datasession['username'] || $datasession['username'] == "" || $datasession['username'] == NULL) {
								$this->session->set_userdata('session_expired', TRUE);
								header ("Location: ".base_url());
							}
						}
		
						if ($submission == 1 || $submission == 'revise' || $submission == 'reject' || $submission == 'approve') {
							$comm_msg = isset($_POST['wf_msg']) ? $_POST['wf_msg'] : NULL;
							switch ($submission) {
								case 'revise':
									$review_status = isset($_POST['review_status']) ? $_POST['review_status'] : NULL;
									$revised_fields_id = $this->dp_eform->get_fields_id_to_revise($data_menu,$review_status);
									$review_note = isset($_POST['review_note']) ? $_POST['review_note'] : NULL;

									if ($review_status) {
										$this->session->set_userdata('review_status', $review_status);
									}
									if ($review_note) {
										$this->session->set_userdata('review_note', $review_note);
									}

									$empty_review_note_field_id = NULL;
									foreach ($revised_fields_id as $row) {
										if ($review_note[$row->Id] == '' || $review_note[$row->Id] == NULL || !isset($review_note[$row->Id])) {
											$validate_revise = FALSE;
											$review_array['review_status'] = $review_status;
											$review_array['review_note'] = $review_note;
											$empty_review_note_field_id = $row->Id;
											break;
										}
									}

									if ($validate_revise) {
										$this->dp_eform->update_log_field_review($id,$data_menu,$revised_fields_id,$review_note);
									}
									
									$approval_type = 'Revise';
									$approval_string = "This request is need to revised";
									break;
								case 'reject':
									$approval_type = 'Reject';
									$approval_string = "This request has been rejected";
									break;
								case 'approve':
									$approval_type = 'Approve';
									$approval_string = "This request has been approved";
									break;
								default:
									$approval_type = 'Submit';
									$this->wf->generate_approval_sequence($datasession,$data_menu,$id,$datapost,$comm_msg);
									$this->wf->sync_goa_to_approval_sequence();
									$approval_string = "This request has been submitted for approval";
									break;
							}

							if ($validate_revise) {

								$this->wf->insert_wf_log($data_menu,$id,$approval_type,$comm_msg,$datasession);
								
								$this->wf->update_status_submit($data_menu,$id,$submission,$approval_type,$datasession,$comm_msg);

								//$this->wf->insert_wf_log($data_menu,$id,$approval_type,$comm_msg,$datasession);

								$msgerror = $this->data_process_translate->check_vocab($datasession['language'],$approval_string)." ".anchor($this->control_name.'/index/'.$menu_name, "[".$this->data_process_translate->check_vocab($datasession['language'],"Return")."]");
							} else {
								$msgerror = $this->data_process_translate->check_vocab($datasession['language'],'Isian review tidak bisa kosong (atau beri tanda cek pada kotak disamping pertanyaan untuk menyetujui isian data)');
								$java_alert['msg'] = $msgerror;
								$java_alert['form_control_name'] = 'review_note['.$empty_review_note_field_id.']';
							}
						}
					}
					/////////////////////////////////////////////////////////////////////////////
					
					if ($data_menu['is_workflowdata'] == 1 && $formtype == 'mainform' && $approval_type == 'Submit') {

						$request_reference_number = $this->dp_eform->get_request_reference_number($data_menu,$this->dp_eform->get_data_id_from_hash_link($id,$data_menu['full_table_name']));

						if ($request_reference_number != '') {
							$msgerror .= "<script type='text/javascript'>alert('Request has been submitted \\x Reference number: ".$request_reference_number." \\x next process: ".$this->dp_eform->get_next_approver($data_menu,$this->dp_eform->get_data_id_from_hash_link($id,$data_menu['full_table_name']))."');</script>";
						}
					}
					
					$this->show_interface($java_alert,$validate_revise,$review_array,$menu_name,$id,'edit',$formtype,$subform_name,$datapost['main_id'],$msgerror);
					
					if ($formtype == 'subform') {
						redirect($this->control_name.'/edit/'.$menu_name.'/'.$datapost['main_id']);
					}
				}
				
				if ($task == 'new') {
					$datapost['draft_id'] = isset($_POST['draft_id']) ? $_POST['draft_id'] : NULL;
					
					if ($this->dp_eform->check_if_menu_has_extended_data($data_menu)) {
						$extended_field_id = $this->dp_eform->get_data_extend_from_table($data_menu)['full_table_name'].'_id';
						$datapost[$extended_field_id] = $this->session->userdata('selection_id');
						$this->session->unset_userdata('selection_id');
					}
					
					$this->dp_eform->new_data($datasession,$data_menu,$form_fields,$datapost,$formtype,$subform_name);
					if ($formtype != 'subform') {
						if ($datapost['draft_id'] != NULL || ($data_menu['is_masterdata'] == 1 && $this->dp_eform->get_subform_data($data_menu))) {
							$data_id = $this->dp_eform->get_data_id_from_draft($data_menu,$datapost['draft_id']);

							if ($checkbox_tables) {
								foreach ($checkbox_tables as $key => $value) {
									if ($datapost[$value]) {
										$this->dp_eform->new_data_checkbox($datasession,$formtype,$value,$data_menu,$data_id,$checkbox_selections_origin_table[$key],$datapost[$value]);
									}
								}
							}
							
							$this->dp_eform->auto_insert_extended_data_id_handling($data_menu,$data_id,$datasession);

							redirect($this->control_name.'/edit/'.$menu_name.'/'.$data_id);
						} else {
							redirect($this->control_name.'/index/'.$menu_name);		
						}
					} else {
						redirect($this->control_name.'/edit/'.$menu_name.'/'.$datapost['main_id']);	
					}
				}
			} else {
				$this->show_interface($java_alert,$validate_revise,$review_array,$menu_name,$id,$task,$formtype,$subform_name,$datapost['main_id'],$msgerror,$datapost);
			}	
		}
		
		if ($task == 'delete') {
			$this->dp_eform->delete_data($datasession,$data_menu,$id,$formtype,$subform_name);
			if ($formtype != 'subform') {
				redirect($this->control_name.'/index/'.$menu_name);	
			} else {
				redirect($this->control_name.'/edit/'.$menu_name.'/'.$datapost['main_id']);	
			}
		}
	}
	
	function subform($act,$menu_name,$subform_name,$main_id,$data_id=0) {
		$datasession = $this->session->userdata('logged_in');
		
		$language = $datasession['language'];
		$data['fullname'] = $datasession['fullname'];
		$data['app_title'] = $datasession['app_title'];
		$menu['user'] = $datasession['username'];
		$menu['app_id'] = $datasession['app_id'];
		
		$data_menu = $this->dp_eform->get_data_menu($menu_name);
		$function_access = $this->dp_eform->get_function_access_data($menu_name,$menu);
		$data_menu['is_insert_disable'] = $function_access['is_insert_disable'];
		$data_menu['is_edit_disable'] = $function_access['is_edit_disable'];
		$data_menu['is_delete_disable'] = $function_access['is_delete_disable'];
		
		/*if ($data_menu['is_insert_disable'] == 1) {
			if ($data_menu['is_approval'] != 1 && $data_menu['is_transdata'] != 1)	{
				redirect($this->control_name.'/index/'.$menu_name);	
			}
		}*/
		
		if ($act == 'add') { $task = 'new';}
		if ($act == 'edit') { $task = 'edit';}
		if ($act == 'remove') { $task = 'delete';}
		$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task,'subform',$subform_name,$main_id);	
	}
	
	function show_interface($java_alert = array(),$validate_revise,$review_array,$menu_name,$data_id,$task,$formtype,$subform_name='',$main_id = 0,$msgerror = '', $datapost = array()) {

			$datasession = $this->session->userdata('logged_in');
			
			if (!($this->user->check_allow_access_page($datasession,$menu_name))) {
				if (!$this->session->userdata('bypass_masterevent')) {
					redirect('accessrestricted');
				}
			}
			
			$language = $datasession['language'];
			$data['fullname'] = $datasession['fullname'];
			$data['app_title'] = $datasession['app_title'];
			$menu['user'] = $datasession['username'];
			$menu['app_id'] = $datasession['app_id'];
			
			$data_menu = $this->dp_eform->get_data_menu($menu_name);
			$function_access = $this->dp_eform->get_function_access_data($menu_name,$menu);
			$data_menu['is_insert_disable'] = $function_access['is_insert_disable'];
			$data_menu['is_edit_disable'] = $function_access['is_edit_disable'];
			$data_menu['is_delete_disable'] = $function_access['is_delete_disable'];
			
			$data['menu'] = $this->menu->generatemenu($menu_name,$menu);
			
			$form_fields = $this->dp_eform->get_form_fields($data_menu['id'],$formtype,$task,$data_id,$subform_name);
			$data_detail = array();
			$data_detail = $this->dp_eform->get_data_detail($data_menu,$form_fields,$data_id,$formtype,$subform_name);

			$data_menu['subform_title'] = ($subform_name != "") ? str_replace("%20", " ", $subform_name) : "";
			
			$data['content_header'] = $this->content->content_header($language,$data_menu,TRUE,$data_detail);

			$data['breadcrumb'] = $this->content->breadcrumb($language,$data_menu);

			if ($formtype == 'mainform') { $main_id = $data_id; }
			
			if ($this->wf->lock_data_editing($data_menu,$main_id)) {
				$data_menu['is_insert_disable'] = 1;
				$data_menu['is_edit_disable'] = 1;
				$data_menu['is_delete_disable'] = 1;
			}
			
			if (!$validate_revise) {
				$review_array['review_status'] = $this->session->userdata('review_status');
				$review_array['review_note'] = $this->session->userdata('review_note');
				$this->session->unset_userdata('review_status');
				$this->session->unset_userdata('review_note');
			}

			$formtype_tmp = array();
			switch ($formtype) {
				case 'mainform':
					$formtype_tmp['form_name'] = $data_menu['title'];
					break;
				case 'subform':
					$formtype_tmp['form_name'] = $subform_name;
					break;
				default:
					$formtype_tmp['form_name'] = '';;
					break;
			}
			$formtype_tmp['formtype'] = $formtype;
			$formtype = $formtype_tmp;
			
			$html_components = $this->get_form_components($java_alert,$validate_revise,$review_array,$formtype,$language,$form_fields,$data_detail,$task,$datapost,$data_menu,$main_id);
			$form_components = $html_components['form_components'];

			$draft_id = 0;
			$subformgrid = "";
			$btn_add_disabled = "";
			$datapost['search'] = "";
			$btn_submit_disabled = "";
			$btn_add_subform_data = "";
			
			if ($formtype['formtype'] == 'mainform') {
				if ($data_menu['is_workflowdata'] == 1 && $task == 'new') {
					$btn_submit_disabled = "disabled";
				}

				//////////////// Render data grid from its subform //////////////////////////////////////////////////////////////////////////
			
				if ($data_menu['is_masterdata'] == 1 || $data_menu['is_workflowdata'] == 1 || $data_menu['is_transdata'] == 1 || $data_menu['is_approval'] == 1) {
					if ($task == 'new') {
						$draft_id = $this->dp_eform->get_draft_id($data_menu['full_table_name']);	
						$btn_add_disabled = "disabled";
					}
					
					$query = $this->dp_eform->get_data_subform($data_menu['id']);
					foreach($query as $row) {
						
						////// validate subform data if required//////////////////////
						if ($row->is_required == 1 && $btn_submit_disabled == "") {
							 $btn_submit_disabled = $this->dp_eform->validate_subform_data($data_id,$data_menu,$row,$task);
						}

						//////////////////////////////////////////////////////////////////////////
						
						$mandatory_subform_data = ($row->is_required == 1) ? "<font color=\"red\">*</font>" : "";
						
						$btn_add_subform_data = "";
						if ($task != 'delete') {
							if (($task == 'new' && $data_menu['is_insert_disable'] != 1) || ($task == 'edit' && $data_menu['is_edit_disable'] != 1)) {

								$btn_add_subform_data_js = "";
								
								if ($formtype['formtype'] == 'mainform') {
									#set session via js
									if ($row->subform_name == 'BPOM / Government Process Supporting Documents') {
										$btn_add_subform_data_js = "
											var txtregulatorynotes = document.getElementById('txtregulatorynotes').value;

											if (txtregulatorynotes != '') {
												var localStorage = window.localStorage;
												localStorage.setItem('regulatorynotes_".$main_id."', txtregulatorynotes);
											}
											";
									}
									# [END OF] set session via js
								}
								$btn_add_subform_data = "</br>
								<button type=\"button\" ".$btn_add_disabled." class=\"btn bg-orange\" onclick=\"".$btn_add_subform_data_js."location.href='".site_url($this->control_name."/subform/add/".$menu_name)."/".str_replace("/","zzz",$row->subform_name)."/".$data_id."';\"><i class=\"fa fa-plus-circle\" aria-hidden=\"true\"></i>&nbsp;".$this->data_process_translate->check_vocab($language,"Add")."</button>";		
							}

							if ($btn_add_subform_data != '' && (int) $row->is_insert_disable == 1) {
								$btn_add_subform_data = '';
							}
						}

						#separator for each subform
						$separator = "";
						if ($row->separator_title != '') {
							$separator = "<table class='table table-bordered table-striped'>
								  <tr style='background-color: ".$row->separator_bgcolor.";'>
								    <td align=\"center\"><strong>".$this->data_process_translate->check_vocab($language,$row->separator_title)."</strong></td>
								  </tr>
								</table><br>";
						}
						# [END OF] separator for each subform
							
						$subformgrid .= "<hr>".$separator."<label>".$this->data_process_translate->check_vocab($language,$row->subform_name)."</label>".$mandatory_subform_data.$btn_add_subform_data."<br>".$this->load_datagrid($data_menu,$menu_name,$datapost,1000000,0,'subform',str_replace("/", "zzz", $row->subform_name),$main_id,$task);
					}
					
					////////////////////// Approval Communication box ////////////////////////////////////////////////////
					if ($data_menu['is_workflowdata'] == 1 || $data_menu['is_approval'] == 1) {
						$approval_message_label_string = "Requisition / Approval Message";
						$approval_log_link_string = "Approval Log";

						 $data_ta = array(
							        'name'        => 'wf_msg',
							        'id'          => 'wf_msg',
							        'value'       => '',
							        'rows'        => '5',
							        'cols'        => '10',
							        'style'       => 'width:100%',
							        'class'       => 'form-control'
							    );

						if ($this->wf->show_approval_comm_box($datasession,$data_menu,$data_detail)) {
							$approval_msg_box = "<hr>
								<label>".$approval_message_label_string."</label>&nbsp;".anchor_popup(site_url('comm_log/index/'.$this->wf->get_origin_data_menu_id($data_menu).'/'.$data_id),'['.$approval_log_link_string.']',array())."</br>".form_textarea($data_ta);
						} else {
							$approval_msg_box = "<hr>
								<label>".$approval_message_label_string."</label>&nbsp;".anchor_popup(site_url('comm_log/index/'.$this->wf->get_origin_data_menu_id($data_menu).'/'.$data_id),'['.$approval_log_link_string.']',array());
							
						}

						$subformgrid .= $approval_msg_box."</div>";
					}
					///////////////////////////////////////////////////////////////////////////////////////////////////////////
				}
				///////////////////////////////////////////////////////////////////////////////////////////////////////////////
			}
			
			if ($task != 'selection') {
				$data['content'] = $this->content->load_content_form($validate_revise,$language,$this->control_name,$menu_name,$form_components,$data_id,$task,$data_detail,str_replace("\\n", "", $msgerror),$data_menu,$subformgrid,$draft_id,$btn_submit_disabled,$formtype,$subform_name,$main_id);

				if ($msgerror != '' && $data_menu['is_workflowdata'] == 1 && !strpos($msgerror, 'request has been submitted for approval') && !strpos($msgerror, 'Changes have been saved') ) {

					if (!$java_alert) {
						$html_components['java_functions'] .= "alert('".$msgerror."');";
					}
				}

				$data['java_functions'] = $html_components['java_functions'];
			} else {
				$query = array();
				$options = array();
				$query = $this->dp_eform->get_data_extend_from_table($data_menu);
				$label = $query['label'];

				$field_name = $query['field_name'];
				foreach($query['query'] as $row) {
					$options[$row->Id] = $row->$field_name;
				}

				$data['content'] = $this->content->load_content_form_selection($language,$this->control_name,$menu_name,$label,$options);
				$data['java_functions'] = '';
			}
			$data['process_msg'] = $this->data_process_translate->check_vocab($language,"Please wait while your data is being processed");
			$data['java_alert'] = $java_alert;
			$data['js_script_page'] = $data_menu['js_script_page'];
			$this->load->view($this->view_name,$data);
	}

	function select($menu_name) {
		$selection = isset($_POST['selection']) ? $_POST['selection'] : NULL;
		$this->session->set_userdata('selection_id', $selection);
		redirect($this->control_name.'/add/'.$menu_name);
	}
	
	function add($menu_name,$data_id=0) {
		
		if (!$this->session->userdata('logged_in')) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		}

		$datasession = $this->session->userdata('logged_in');
		
		$language = $datasession['language'];
		$data['fullname'] = $datasession['fullname'];
		$data['app_title'] = $datasession['app_title'];
		$menu['user'] = $datasession['username'];
		$menu['app_id'] = $datasession['app_id'];
		
		$data_menu = $this->dp_eform->get_data_menu($menu_name);
		$function_access = $this->dp_eform->get_function_access_data($menu_name,$menu);
		$data_menu['is_insert_disable'] = $function_access['is_insert_disable'];
		$data_menu['is_edit_disable'] = $function_access['is_edit_disable'];
		$data_menu['is_delete_disable'] = $function_access['is_delete_disable'];

		if ($data_menu['is_insert_disable'] == 1) redirect($this->control_name.'/index/'.$menu_name);
		
		if (!$this->dp_eform->is_extended_form($data_menu)) {
			$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task='new','mainform','',0);
		} else {
			$selection = $this->session->userdata('selection_id');

			if ($selection == NULL) {
				$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task='selection','mainform','',0);	
			} else {
				$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task='new','mainform','',0);	
			}
		}
		
	}
	
	function cancel($menu_name) {

		if (!$this->session->userdata('logged_in')) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		}

		$this->session->unset_userdata('selection_id');

		redirect($this->control_name.'/index/'.$menu_name);
	}
	
	function edit($menu_name,$data_id=0) {

		if (!$this->session->userdata('logged_in')) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		}

		$this->wf->do_send_email();
		$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task='edit','mainform','',0);
	}
	
	function remove($menu_name,$data_id) {
		
		if (!$this->session->userdata('logged_in')) {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		}

		$datasession = $this->session->userdata('logged_in');
		
		$language = $datasession['language'];
		$data['fullname'] = $datasession['fullname'];
		$data['app_title'] = $datasession['app_title'];
		$menu['user'] = $datasession['username'];
		$menu['app_id'] = $datasession['app_id'];
		
		$data_menu = $this->dp_eform->get_data_menu($menu_name);
		$function_access = $this->dp_eform->get_function_access_data($menu_name,$menu);
		$data_menu['is_insert_disable'] = $function_access['is_insert_disable'];
		$data_menu['is_edit_disable'] = $function_access['is_edit_disable'];
		$data_menu['is_delete_disable'] = $function_access['is_delete_disable'];
		
		if ($data_menu['is_delete_disable'] == 1) redirect($this->control_name.'/index/'.$menu_name);
		
		$this->show_interface(array(),TRUE,array(),$menu_name,$data_id,$task='delete','mainform','',0);	
	}
	
	function showall($menu_name) {
		$this->session->unset_userdata($menu_name.'_search');
		redirect($this->control_name.'/index/'.$menu_name);
	}
	
	function show_request($menu_name,$show_kuesioner = FALSE) {
		$show_kuesioner = $_POST['show_request'];

		if ($show_kuesioner) {
			$this->session->set_userdata($menu_name.'_show_request', str_replace("%20"," ",$show_kuesioner));
		} else {
			if ($this->session->userdata($menu_name.'_show_request')) {
				$this->session->unset_userdata($menu_name.'_show_request');
			}
		}
		redirect($this->control_name.'/index/'.$menu_name);
	}

	function index($menu_name,$offset = '0') {
		if ($this->session->userdata('logged_in')) {
			if ($offset != 'new' && $offset != 'edit' && $offset != 'remove') {

				$page_base_url = $this->control_name.'/index/'.$menu_name;

				$this->session->unset_userdata('selection_id');
				
				$datasession = $this->session->userdata('logged_in');
				
				if (!($this->user->check_allow_access_page($datasession,$menu_name))) {
					redirect('accessrestricted');
				}

				$language = $datasession['language'];
				$data['fullname'] = $datasession['fullname'];
				$data['app_title'] = $datasession['app_title'];
				$menu['user'] = $datasession['username'];
				$menu['app_id'] = $datasession['app_id'];
				
				$data_menu = $this->dp_eform->get_data_menu($menu_name);
				$function_access = $this->dp_eform->get_function_access_data($menu_name,$menu);
				$data_menu['is_insert_disable'] = $function_access['is_insert_disable'];
				$data_menu['is_edit_disable'] = $function_access['is_edit_disable'];
				$data_menu['is_delete_disable'] = $function_access['is_delete_disable'];
				$data_menu['app_id'] = $datasession['app_id'];

				$this->dp_eform->initiate_hash_link($data_menu);
				//print_r($data_menu);exit;
				#sync process for GoA
				if ($data_menu['is_workflowdata'] == 1) {
					$this->wf->sync_goa_to_approval_sequence();
				}
				
				if (isset($_POST['btnsearch'])) {
					$search['search'] = isset($_POST['txtsearch']) ? $_POST['txtsearch'] : '';
					if ($search['search'] == '') {
						$this->showall($menu_name);
					}
				}
				
				$search['search'] = isset($_POST['txtsearch']) ? $_POST['txtsearch'] : '';
				$filter = FALSE;
				
				if ($search['search'] != NULL) {
					$this->session->set_userdata($menu_name.'_search', $search);
				}
				
				$datapost = $this->session->userdata($menu_name.'_search');
				
				$page['base_url'] = site_url($page_base_url);
				//$page['total_rows'] = $this->db->count_all('('.$this->query.') A');
				//$page['total_rows'] = $this->data_process_langcountry->count_rows($datapost);
				
				$page['total_rows'] = ($data_menu['full_table_name'] !='')? $this->dp_eform->count_rows($data_menu,$datapost,'mainform','') : 0;
				
				$page['offset'] = $offset;
				$page['per_page'] = 15;
				$page['uri_segment'] = 4;
				$page['first_link'] = "<< ".$this->data_process_translate->check_vocab($language,"First")." ";
				$page['last_link'] = " ".$this->data_process_translate->check_vocab($language,"Last")." >>";
				$page['prev_link'] = "< ".$this->data_process_translate->check_vocab($language,"Previous")." ";
				$page['next_link'] = " ".$this->data_process_translate->check_vocab($language,"Next")." >";
				$this->pagination->initialize($page);
				$page['pagination'] = $this->pagination->create_links();
				$content['pagination'] = $page['pagination'];
				$content['datagrid'] = ($data_menu['full_table_name'] !='')? $this->load_datagrid($data_menu,$menu_name,$datapost,$page['per_page'],$page['offset'],'mainform','',0,'') : '';
				
				$search = array('search' => $datapost['search']);
				$content['search'] = $search;
			
				$data['menu'] = $this->menu->generatemenu($menu_name,$menu);
				$data['content_header'] = $this->content->content_header($language,$data_menu,FALSE,array());
				$data['breadcrumb'] = $this->content->breadcrumb($language,$data_menu);
				$data['content'] = $this->content->load_content($language,$this->control_name,$menu_name,$content,$data_menu);
				$data['java_functions'] = '';
				
				$data['process_msg'] = $this->data_process_translate->check_vocab($language,"Please wait while your data is being processed");

			   	$this->load->view($this->view_name,$data);			
			}
		} else {
			$this->session->set_userdata('session_expired', TRUE);
			header ("Location: ".base_url());
		}
	}
}
