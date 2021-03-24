<?php

/**
 * 
 */
class Menu {
	private $ci;

	private $data_childmenu_array = array();
	
	function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->library('app_initializer','','app_init');
		$this->ci->load->model('data_process_translate','',TRUE);
	}

	# initialize variables number of pending approvals per menu
	//private $total_pending = 0;
	private $total_pending = array();
	private $number_pending_per_menu = array();
	private $activity_type_per_menu = array();
	private $query_v_pending_approvers = array();
	private $other_pending_process = array();
	# 
	
	# get number of pending approvals per menu
	/*function get_pending_approvals() {
		$datasession = $this->ci->session->userdata('logged_in');
		#initialized variables of pending approvals
		$this->number_pending_per_menu['requestapproval'] = 0;
		$this->number_pending_per_menu['settlementapproval'] = 0;
		$this->number_pending_per_menu['reimburseapproval'] = 0;
		
		$this->activity_type_per_menu['requestapproval'] = array('CARE');
		$this->activity_type_per_menu['settlementapproval'] = array('CARE');
		$this->activity_type_per_menu['reimburseapproval'] = array('REIM');
		
		$this->ci->db->select('Id,activity_type,approval_form');
		$this->ci->db->where('username', $datasession['username']);
		$this->ci->db->from('v_pending_approvers');
		$query = $this->ci->db->get()->result();

		if ($query) {
			foreach ($query as $row) {
				$request_id = $row->Id;
				$activity_type = $row->activity_type;
				$approval_form = $row->approval_form;
				foreach ($this->activity_type_per_menu as $key => $value) {
					if (in_array($activity_type,$this->activity_type_per_menu[$key]) && $approval_form == $key) {
						$this->number_pending_per_menu[$key]++;
						$this->total_pending++;
					}
				}
			}
		}
	}*/
	#
	#
	# customized for Mundi, EPMAP Project 2020, set array ethical parent title on child menu
	function set_array_ethical_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['apprv_frm_opioid'] = array('activity_type' => array('opioid'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_internal_training'] = array('activity_type' => array('training_internal'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_product_material'] = array('activity_type' => array('product_name'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_other'] = array('activity_type' => array('other'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array consumer parent title on child menu
	function set_array_consumer_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['apprv_frm_key_promotional_aid'] = array('activity_type' => array('key_promo_aid'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_in_store_pos'] = array('activity_type' => array('storemedia_pos'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_social_media'] = array('activity_type' => array('social_media'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_ecommerce'] = array('activity_type' => array('ecommerce'),'parent_menu' => $parent_menu);
		$this->activity_type_per_menu['apprv_frm_gimmicks'] = array('activity_type' => array('gimmicks'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array corporate material parent title on child menu
	function set_array_corporate_material_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['apprv_frm_corporate_materials'] = array('activity_type' => array('corporate'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array speaker brief material parent title on child menu
	function set_array_speaker_brief_material_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['apprv_frm_speaker_brief'] = array('activity_type' => array('speaker_brief'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array gimmicks material parent title on child menu
	function set_array_gimmicks_material_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['apprv_frm_gimmicks'] = array('activity_type' => array('gimmicks'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array bpom process parent title on child menu
	function set_array_bpomprocess_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['trans_frm_bpomgov_status'] = array('activity_type' => array('other'),'parent_menu' => $parent_menu);
	}
	#
	# customized for Mundi, EPMAP Project 2020, set array regional process parent title on child menu
	function set_array_regional_parent_title_per_child_menu($parent_menu) {
		$this->activity_type_per_menu['trans_frm_regional_status'] = array('activity_type' => array('other'),'parent_menu' => $parent_menu);
	}
	# 
	# customized for Mundi, EPMAP Project 2020, get number of pending processes
	function get_pending_process($sub_menu) {
		$this->number_pending_per_menu[$sub_menu] = 0;
		
		$parent_menu = $this->activity_type_per_menu[$sub_menu]['parent_menu'];
		if (!array_key_exists($parent_menu, $this->total_pending)) {
			$this->total_pending[$parent_menu] = 0;
		}

		$query = array();

		switch ($sub_menu) {
			case 'trans_frm_bpomgov_status':
				$this->ci->db->select('Id');
				$this->ci->db->from('epmap_req_material_data');
				$this->ci->db->where('isdelete', 0);
				$this->ci->db->where('status', 'Full Approve');
				$this->ci->db->where('is_bpom_required', 1);
				$this->ci->db->where('bpom_process_status', 'In Process');
				$query = $this->ci->db->get()->result();

				$activity_type = 'other';
				break;
			case 'trans_frm_regional_status':
				$this->ci->db->select('Id');
				$this->ci->db->from('epmap_req_material_data');
				$this->ci->db->where('isdelete', 0);
				$this->ci->db->where('status', 'Full Approve');
				$this->ci->db->where('is_regional_required', 1);
				$this->ci->db->where('regional_status', 'In Process');
				$this->ci->db->where('(coalesce(is_bpom_required,0) = 0 or (is_bpom_required = 1 and bpom_process_status = "Approved"))', NULL);
				$query = $this->ci->db->get()->result();

				$activity_type = 'other';
				break;
			default:
				# code...
				break;
		}

		if ($query) {
			foreach ($query as $row) {
				if (array_key_exists($sub_menu, $this->activity_type_per_menu)) {
					if (in_array(strtolower($activity_type), $this->activity_type_per_menu[$sub_menu]['activity_type'])) {
								$this->number_pending_per_menu[$sub_menu]++;
								$this->total_pending[$parent_menu]++;
					}
				}
			}
		}
	}
	# 
	# customized for Mundi, EPMAP Project 2020, get number of pending approvals per menu ethical material approvals
	function get_pending_approvals() {
		$datasession = $this->ci->session->userdata('logged_in');
		#initialized variables of pending approvals
		$this->number_pending_per_menu['apprv_frm_opioid'] = 0;
		$this->number_pending_per_menu['apprv_frm_internal_training'] = 0;
		$this->number_pending_per_menu['apprv_frm_product_material'] = 0;
		$this->number_pending_per_menu['apprv_frm_speaker_brief'] = 0;
		$this->number_pending_per_menu['apprv_frm_corporate_materials'] = 0;
		$this->number_pending_per_menu['apprv_frm_other'] = 0;

		$this->number_pending_per_menu['apprv_frm_key_promotional_aid'] = 0;
		$this->number_pending_per_menu['apprv_frm_in_store_pos'] = 0;
		$this->number_pending_per_menu['apprv_frm_social_media'] = 0;
		$this->number_pending_per_menu['apprv_frm_ecommerce'] = 0;
		$this->number_pending_per_menu['apprv_frm_gimmicks'] = 0;
		
		$query = $this->query_v_pending_approvers;
		if ($query) {
			foreach ($query as $row) {
				$request_id = $row->Id;
				$activity_type = $row->activity_type;
				foreach ($this->activity_type_per_menu as $key => $value) {
					if (in_array(strtolower($activity_type), $this->activity_type_per_menu[$key]['activity_type']) && array_key_exists($key, $this->number_pending_per_menu)) {
						$this->number_pending_per_menu[$key]++;
						//$this->total_pending++;
						#customized for Mundi EPMAP Project 2020
						$parent_menu = $this->activity_type_per_menu[$key]['parent_menu'];
						if (!array_key_exists($parent_menu, $this->total_pending)) {
							$this->total_pending[$parent_menu] = 0;
						}
						$this->total_pending[$parent_menu]++;
						#
					}
				}
			}
		}
	}
	#
	function set_active_menu($control_name, $title){
		$active_status = FALSE;
		$title_parent = '';
		
		$current_conn = $this->ci->db;
		$current_db = $current_conn->database;
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->query('use '.$applat_db);

		$query = $this->ci->db->query("select menu_header.* from refmenu as menu_header inner join
															refmenu on menu_header.id = refmenu.parent_id
															where refmenu.url='" . $control_name . "'");
		$query_result = $query->result();

		$this->ci->db->query('use '.$current_db);

		foreach ($query_result as $row){
			$title_parent = $row->title;
			}
		
		if ($title_parent == $title) $active_status = TRUE;
		
		return ($active_status)? 'active':'';
	}
	
	function set_active_childmenu($control_name, $url){
		$active_status = FALSE;
		
		if (strtoupper($control_name) == strtoupper($url) ) $active_status = TRUE;

		return ($active_status)? 'class = "active"':'';
	}
	
	function generatemenu($control_name,$menu){
		$data_access = $menu;
		$datasession = $this->ci->session->userdata('logged_in');
		$language = $datasession['language'];

		//$query = $this->ci->db->query("select * from refmenu where parent_id = 0 and is_showed = 1 order by order_index");
		
		$menu = "<li class='treeview'>
              <a href='home'>
                <i class='fa fa-home'></i> <span>".$this->ci->data_process_translate->check_vocab($language,"Home")."</span></a></li>";

        if (!$this->ci->session->userdata('is_password_reset')) {

        	if (!$this->ci->session->userdata('session_parent_menu')) {
        		$app_init = $this->ci->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];
				
				$tmp = array();

				$this->ci->db->distinct();
				$this->ci->db->select('Id,title,style_class,url');
				$this->ci->db->from($applat_db.'.v_menu');
				$this->ci->db->where('parent_id', 0);
				$this->ci->db->where('is_showed', 1);
				$this->ci->db->where('isdelete', 0);
				$this->ci->db->where('app_id', $data_access['app_id']);
				$this->ci->db->where('username', $data_access['user']);
				$this->ci->db->order_by('order_index', 'ASC');
				$query = $this->ci->db->get()->result();

				$this->ci->session->set_userdata('session_parent_menu',$query);
			} else {
				$query = $this->ci->session->userdata('session_parent_menu');
			}

			$parentmenu_id = -1;
			foreach ($query as $row) {
				$parentmenu_id .= ($parentmenu_id == "") ? $row->Id : ",".$row->Id ;

				if (in_array($row->title, array('Ethical Material<br>Approvals',
											'Consumer Material<br>Approvals',
											'BPOM/GOV Process',
											'Regional Process',
											'Corporate Material<br>Approvals',
											'Speaker Brief<br>Material Approvals',
											'Gimmicks Material<br>Approvals'))) {

					if (!in_array($row->title, array('BPOM/GOV Process','Regional Process'))) {
						if (!$this->query_v_pending_approvers) {
							$this->ci->db->select('Id,activity_type');
							$this->ci->db->where('username', $datasession['username']);
							$this->ci->db->from('v_pending_approvers');

							$this->query_v_pending_approvers = $this->ci->db->get()->result();
						}

						switch ($row->title) {
							case 'Ethical Material<br>Approvals':
								$this->set_array_ethical_parent_title_per_child_menu($row->title);
								break;
							case 'Consumer Material<br>Approvals':
								$this->set_array_consumer_parent_title_per_child_menu($row->title);
								break;
							case 'Corporate Material<br>Approvals':
								$this->set_array_corporate_material_parent_title_per_child_menu($row->title);
								break;
							case 'Speaker Brief<br>Material Approvals':
								$this->set_array_speaker_brief_material_parent_title_per_child_menu($row->title);
								break;
							case 'Gimmicks Material<br>Approvals':
								$this->set_array_gimmicks_material_parent_title_per_child_menu($row->title);
								break;
							default:
								# code...
								break;
						}
					} else {
						switch ($row->title) {
							case 'BPOM/GOV Process':
								$this->set_array_bpomprocess_parent_title_per_child_menu($row->title);
								break;
							case 'Regional Process':
								$this->set_array_regional_parent_title_per_child_menu($row->title);
								break;
							default:
								# code...
								break;
						}
					}
				}
			}

			if (!$this->ci->session->userdata('session_child_menu')) {
				$app_init = $this->ci->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];
				
				$menu_child_id = "-1";

				$this->ci->db->distinct();
				$this->ci->db->select($applat_db.'.v_menu.Id');
				$this->ci->db->from($applat_db.'.v_menu');
				$this->ci->db->where($applat_db.'.v_menu.username', $data_access['user']);
				
				$menu_child_id_array = $this->ci->db->get()->result();

				foreach ($menu_child_id_array as $menu_child_id_row) {
					$menu_child_id .= ",".$menu_child_id_row->Id;
				}

				$this->ci->db->distinct();
				$this->ci->db->select($applat_db.'.refmenu.*');
				$this->ci->db->from($applat_db.'.refmenu');
				$this->ci->db->join($applat_db.'.v_menu',$applat_db.'.v_menu.Id = '.$applat_db.'.refmenu.Id');
				$this->ci->db->where($applat_db.'.refmenu.parent_id in ('.$parentmenu_id.')', NULL);
				$this->ci->db->where('refmenu.Id in ('.$menu_child_id.')', NULL);
				$this->ci->db->where($applat_db.'.refmenu.is_showed', 1);
				$this->ci->db->where($applat_db.'.refmenu.isdelete', 0);
				$this->ci->db->order_by($applat_db.'.refmenu.order_index', 'ASC');

				$query_childmenu = $this->ci->db->get()->result();

				$this->ci->session->set_userdata('session_child_menu',$query_childmenu);
			} else {
				$query_childmenu = $this->ci->session->userdata('session_child_menu');
			}

			foreach ($query_childmenu as $row) {
				$this->data_childmenu_array[$row->parent_id][$row->Id]['parent_id'] = $row->parent_id;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['title'] = $row->title;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['style_class'] = $row->style_class;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['is_masterdata'] = $row->is_masterdata;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['is_workflowdata'] = $row->is_workflowdata;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['is_transdata'] = $row->is_transdata;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['is_approval'] = $row->is_approval;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['url'] = $row->url;
				$this->data_childmenu_array[$row->parent_id][$row->Id]['formgen'] = $row->formgen;
			}
			
			foreach ($query as $row){
				$id = $row->Id;
				$title = $row->title;
				$style_class = $row->style_class;
				$url = site_url($row->url);

				$start_font_red = '';
				$end_font_red = '';
				$total_pending = '';
				
				# display number of pending approvals next to menu parent
				if (in_array($title, array('Ethical Material<br>Approvals',
											'Consumer Material<br>Approvals',
											'BPOM/GOV Process',
											'Regional Process',
											'Corporate Material<br>Approvals',
											'Speaker Brief<br>Material Approvals',
											'Gimmicks Material<br>Approvals'))) {

					if (in_array($title, array('BPOM/GOV Process','Regional Process'))) {
						foreach ($this->data_childmenu_array[$id] as $key => $value) {
							$this->get_pending_process($value['url']);
						}
					} else {
						if (in_array($title, array('Ethical Material<br>Approvals',
													'Consumer Material<br>Approvals',
												 	'Corporate Material<br>Approvals',
												 	'Speaker Brief<br>Material Approvals',
												 	'Gimmicks Material<br>Approvals'))) {

							if (!$this->total_pending) {
								$this->get_pending_approvals();
							}
						}
					}
					
					if (array_key_exists($title, $this->total_pending)) {
						if ($this->total_pending[$title] > 0) {
							//$start_font_red = "<font color='orange'>";
							//$end_font_red = "</font>";
							$total_pending = " <font color='orange'>(".$this->total_pending[$title].")</font>";
						}
					}
				}
				# 
				$menu .= "<li class='treeview ".$this->set_active_menu($control_name, $title)."'>
	              <a href='#'>
	                <i class='".$style_class."'></i> <span>".$start_font_red.$this->ci->data_process_translate->check_vocab($language,$title).$total_pending.$end_font_red."</span> <i class='fa fa-angle-left pull-right'></i>
	              </a>";
				  $menu .= $this->get_childmenu($id,$control_name,$data_access)."</li>";
			}
        }
		
		$menu .= "<li class='treeview'>
              <a href='chdpwd'>
                <i class='fa fa-key'></i><span>".$this->ci->data_process_translate->check_vocab($language,"Change Password")."</span></a></li>";
        $menu .= "<li class='treeview'>
              <a href='chdlang'>
                <i class='fa fa-book'></i><span>".$this->ci->data_process_translate->check_vocab($language,"Change Language")."</span></a></li>";	
		$menu .= "<li class='treeview'>
              <a href='logout'>
                <i class='fa fa-power-off'></i><span>".$this->ci->data_process_translate->check_vocab($language,"Logout")."</span></a></li>";	
		return $menu;
	}
	
	function get_childmenu($id, $control_name,$data_access){
			$datasession = $this->ci->session->userdata('logged_in');
			$language = $datasession['language'];

			//$query = $this->ci->db->query("select * from refmenu where parent_id = ".$id." and is_showed = 1 and isdelete = 0 order by order_index");
			$sub_menu = "";

			if (array_key_exists($id,$this->data_childmenu_array)) {
				foreach ($this->data_childmenu_array[$id] as $row) {
					$title = $row['title'];
					$style_class = $row['style_class'];
					$url = site_url(($row['is_masterdata'] == 1 || $row['is_workflowdata'] == 1 || $row['is_transdata'] == 1 || $row['is_approval'] == 1)? $row['formgen'].'/index/'.$row['url'] : $row['url']);

					$start_font_red = '';
					$end_font_red = '';
					$total_pending = '';
					# display number of pending approvals next to menu child
					if (array_key_exists($row['url'], $this->number_pending_per_menu)) {
						$child_menu_pending_approvals = $row['url'];
						if ($this->number_pending_per_menu[$child_menu_pending_approvals] > 0) {
							//$start_font_red = "<font color='orange'>";
							//$end_font_red = "</font>";
							$total_pending = " <font color='orange'>(".$this->number_pending_per_menu[$child_menu_pending_approvals].")</font>";
						}
					}
					# 
					$sub_menu .= "<li ".$this->set_active_childmenu($control_name, $row['url'])."><a href='".$url."'><i class='".$style_class."'></i>".$start_font_red.$this->ci->data_process_translate->check_vocab($language,$title).$total_pending.$end_font_red."</a></li>";
				}
			}
			
			if ($sub_menu !== "") $sub_menu = "<ul class='treeview-menu'>".$sub_menu."</ul>"; 	
			return $sub_menu;
		}
}
