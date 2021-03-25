<?php

/**
 * 
 */
class Menu {
	private $ci;

	private $data_childmenu_array = array();

	# initialize variables number of pending approvals per menu
	//private $total_pending = 0;
	private $total_pending = array();
	private $number_pending_per_menu = array();
	private $activity_type_per_menu = array();
	private $query_v_pending_approvers = array();
	private $other_pending_process = array();
	#
	
	function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->library('app_initializer','','app_init');
		$this->ci->load->model('data_process_translate','',TRUE);
	}

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
