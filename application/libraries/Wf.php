<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wf {
	function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->model('data_process_timeset','datetime',TRUE);
		$this->ci->load->model('data_process_eform','dp_eform',TRUE);
		$this->ci->load->library('app_initializer','','app_init');
	}
	
	function allowed_data_changed($data_menu,$data_id) {
		$tmp = FALSE;
		$datasession = $this->ci->session->userdata('logged_in');

		$this->ci->db->select('Id');
		$this->ci->db->from($data_menu['full_table_name']);
		$this->ci->db->where('Id',$data_id);
		$this->ci->db->where('createby',$datasession['username']);

		$query = $this->ci->db->get()->result();

		if (count($query) >= 1) {
			$tmp = TRUE;
		}

		return $tmp;
	}

	function clear_approval_sequence($data_menu,$data_id) {
		$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->where('menu_id', $requestor_form_menu_id);
		$this->ci->db->where('data_id', $data_id);
		$this->ci->db->delete($applat_db.'.trnworkflowseq');
		
		return NULL;
	}

	function send_email_to_requestor($data_menu,$data_id,$comm_msg,$approval_type,$datasession) {
		$data_smtp = $this->get_data_smtp();
		$previous_approvers_email_address = "";
		$requestor = "";

		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		if (in_array($approval_type, array('Reject'))) {
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

			$this->ci->db->distinct();
			$this->ci->db->select('email_address');
			$this->ci->db->from($applat_db.'.refnoncoreusers');
			$this->ci->db->join($applat_db.'.trnlogworkflow',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnlogworkflow.createby');
			$this->ci->db->where($applat_db.'.trnlogworkflow.status','Approve');
			$this->ci->db->where($applat_db.'.trnlogworkflow.menu_id',$requestor_form_menu_id);
			$this->ci->db->where($applat_db.'.trnlogworkflow.data_id',$data_id);

			$query = $this->ci->db->get()->result();

			if ($query) {
				foreach ($query as $row) {
					$previous_approvers_email_address .= ($previous_approvers_email_address == "") ? $row->email_address : ",".$row->email_address ;
				}
			}
		}

		if ($previous_approvers_email_address != "") {
			$previous_approvers_email_address = ",".$previous_approvers_email_address;
		}

		if ($data_smtp['is_enable'] == 1) {
			switch ($approval_type) {
				case 'Revise':
					$approval_string = 'need to be revised';
					break;
				case 'Reject':
					$approval_string = 'rejected';
					break;
				case 'Approve':
					$approval_string = 'approved';
					break;
				case 'Full Approve':
					$approval_string = 'full approved';
					break;
				default:
					$approval_string = '';
					break;
			}

			$this->ci->db->select('username,fullname,email_address');
			$this->ci->db->from($applat_db.'.refnoncoreusers');
			$this->ci->db->join($data_menu['full_table_name'], $data_menu['full_table_name'].'.createby = '.$applat_db.'.refnoncoreusers.username');
			$this->ci->db->where($data_menu['full_table_name'].'.Id',$data_id);
			
			$query = $this->ci->db->get()->result();

			$row = $query[0];

			$content = $this->get_detail_request_for_email($data_menu,$data_id);

			#customized fot MUNDIPHARMA, ePMAP Project 2020, append body content for BPOM Process
			$bpom_status_email_string = "";
			if ($data_menu['full_table_name'] == 'epmap_req_material_data') {
				if (array_key_exists('is_bpom_required', $content) && array_key_exists('bpom_process_status', $content)) {
					if ((int) $content['is_bpom_required'] == 1 && $content['bpom_process_status'] == 'In Process') {
						$bpom_status_email_string = "<br><br><strong>System Note: </strong>This material is required by Regulatory team for BPOM/Government Agency Approval Process. The following terms is applied:<br>
							<ul>
								<li>BPOM/Gov. Agency Process WILL NOT HOLD the approval process in e-PMAP system</li>
								<li>BPOM/Gov. Agency Process will be proceeded by Regulatory team ONLY AFTER this material is FULLY APPROVED in e-PMAP system</li>
								<li>This Material CANNOT BE USED for Marketing/Sales Event Activities (such as CAMBER system), UNTIL IT IS APPROVED by BPOM/Gov. Agency (to be confirmed by Regulatory team via e-PMAP)</li>
								<li>Kindly coordinate with Regulatory team for BPOM/Government Agency Process status update</li>
							</ul>";
					}
				}
				if (array_key_exists('is_regional_required', $content) && array_key_exists('regional_status', $content)) {
					if ((int) $content['is_regional_required'] == 1 && $content['regional_status'] == 'In Process') {
						$bpom_status_email_string .= "<br><br><strong>System Note: </strong>This material is required by Regional Communication team for Approval Process. The following terms is applied:<br>
							<ul>
								<li>Regional Approval Process will be proceeded by MSL team ONLY AFTER this material is FULLY APPROVED in e-PMAP system (and FULLY APPROVED by BPOM/Gov. Agency, if requires)</li>
								<li>This Material CANNOT BE USED for Marketing/Sales Event Activities (such as CAMBER system), UNTIL IT IS APPROVED by Regional Communication (to be confirmed by MSL team via e-PMAP)</li>
								<li>Kindly coordinate with MSL for Regional Communication Process status update</li>
							</ul>";
					}
				}
			}
			#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, append body content for BPOM Process
			

			$body_mail_content = "Dear ".$row->fullname.",<br><br><br>
								Your request for the following Material is <strong>".$approval_string."</strong>
								<br><br>".$content['content_email']."
								<br><br>Reference : ".$content['reference_number']."
								<br><br>Approval Processed by:".$datasession['fullname']."
								<br><br>Approval Message:<br>".$comm_msg.$bpom_status_email_string."
								<br><br>Please kindly click ".anchor(site_url(), "here")." to log in the application and proceed your documents process or revision (if any required). Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

			$data = array();
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

			$data = array(
				        'menu_id' => $requestor_form_menu_id,
				        'data_id' => $data_id,
				        'username' => $row->username,
				        'to_email_address' => $row->email_address.$previous_approvers_email_address,
				        'email_subject' => strtoupper($approval_string).' Request#.'.$content['reference_number'],
				        'email_body_content' => $body_mail_content,
				        'createby' => $datasession['username'],
				        'createdate' => $this->ci->datetime->get_current_datetime()
					);
			
			$this->ci->db->insert($applat_db.'.refmailmanjobs', $data);

			#customized fot MUNDIPHARMA, ePMAP Project 2020, send email to Regulatory Manager for BPOM Process FULL APPROVED
			if ($data_menu['full_table_name'] == 'epmap_req_material_data' && $approval_type == 'Full Approve') {
				if (array_key_exists('is_bpom_required', $content) && array_key_exists('bpom_process_status', $content)) {
					
					$requestor_email = $row->email_address;

					if ((int) $content['is_bpom_required'] == 1 && $content['bpom_process_status'] == 'In Process') {

						$this->ci->db->select('username,fullname,email_address');
						$this->ci->db->from($data_menu['full_table_name']);
						$this->ci->db->join($applat_db.'.refnoncoreusers', $data_menu['full_table_name'].'.required_bpom_process_by = '.$applat_db.'.refnoncoreusers.username');
						$this->ci->db->where($data_menu['full_table_name'].'.Id',$data_id);
						
						$query_regulatory = $this->ci->db->get()->result();

						if ($query_regulatory) {
							$row_regulatory = $query_regulatory[0];

							$body_mail_content = "Dear ".$row_regulatory->fullname.",<br><br><br>
								Kindly proceed BPOM / Government Agency Approval Process for the following <strong>".$approval_string."</strong> Material
								<br><br>".$content['content_email']."
								<br><br>Reference : ".$content['reference_number']."
								<br><br>Please kindly click ".anchor(site_url(), "here")." to log in the application and proceed your documents process or revision (if any required). Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

							$data = array(
								        'menu_id' => $requestor_form_menu_id,
								        'data_id' => $data_id,
								        'username' => $row_regulatory->username,
								        'to_email_address' => $row_regulatory->email_address.','.$requestor_email,
								        'email_subject' => 'Proceed BPOM/Gov. Agency Process ePMAP#.'.$content['reference_number'],
								        'email_body_content' => $body_mail_content,
								        'createby' => $datasession['username'],
								        'createdate' => $this->ci->datetime->get_current_datetime()
									);
							
							$this->ci->db->insert($applat_db.'.refmailmanjobs', $data);
						}
					} else {
						if (array_key_exists('is_regional_required', $content) && array_key_exists('regional_status', $content)) {
							if ((int) $content['is_regional_required'] == 1 && $content['regional_status'] == 'In Process') {
								$this->ci->db->select('username,fullname,email_address');
								$this->ci->db->from($data_menu['full_table_name']);
								$this->ci->db->join($applat_db.'.refnoncoreusers', $data_menu['full_table_name'].'.required_regional_process_by = '.$applat_db.'.refnoncoreusers.username');
								$this->ci->db->where($data_menu['full_table_name'].'.Id',$data_id);
								
								$query_regional = $this->ci->db->get()->result();

								if ($query_regional) {
									$row_regional = $query_regional[0];

									$body_mail_content = "Dear ".$row_regional->fullname.",<br><br><br>
										Kindly proceed Regional Communication Approval Process for the following <strong>".$approval_string."</strong> Material
										<br><br>".$content['content_email']."
										<br><br>Reference : ".$content['reference_number']."
										<br><br>Please kindly click ".anchor(site_url(), "here")." to log in the application and proceed your documents process or revision (if any required). Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

									$data = array(
										        'menu_id' => $requestor_form_menu_id,
										        'data_id' => $data_id,
										        'username' => $row_regional->username,
										        'to_email_address' => $row_regional->email_address.','.$requestor_email,
										        'email_subject' => 'Proceed Regional Process ePMAP#.'.$content['reference_number'],
										        'email_body_content' => $body_mail_content,
										        'createby' => $datasession['username'],
										        'createdate' => $this->ci->datetime->get_current_datetime()
											);
									
									$this->ci->db->insert($applat_db.'.refmailmanjobs', $data);
								}
							}	
						}
					}
				}


			}
			#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, send email to Regulatory Manager for BPOM Process

			//$this->do_send_email();
		}
	}

	function get_approval_url($data_menu) {
		$url = $data_menu['url'];

		$request_approval_page = array('req_frm_opioid' => 'apprv_frm_opioid',
										'req_frm_internal_training' => 'apprv_frm_internal_training',
										'req_frm_product_material' => 'apprv_frm_product_material',
										'req_frm_speaker_brief' => 'apprv_frm_speaker_brief',
										'req_frm_corporate_materials' => 'apprv_frm_corporate_materials',
										'req_frm_other' => 'apprv_frm_other',
										'req_frm_key_promotional_aid' => 'apprv_frm_key_promotional_aid',
										'req_frm_in_store_pos' => 'apprv_frm_in_store_pos',
										'req_frm_social_media' => 'apprv_frm_social_media',
										'req_frm_ecommerce' => 'apprv_frm_ecommerce',
										'req_frm_gimmicks' => 
										'apprv_frm_gimmicks');
		if (array_key_exists($url, $request_approval_page)) {
			return $request_approval_page[$url];
		} else {
			return 'home';
		}
	}

	function update_status_submit($data_menu,$data_id,$submission,$approval_type,$datasession,$comm_msg) {
		if ($datasession) {
			if ($datasession['username'] != "") {

				$data_id =  $this->ci->dp_eform->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

				switch ($approval_type) {
					case 'Revise':
						$tmp = array(
							'last_approval_process_by' => $datasession['username'],
					        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
					        'status' => "Revise"
						);

						$data = array();
						$data = array_merge($data,$tmp);
						
						$this->ci->db->where('Id', $data_id);
						$this->ci->db->update($data_menu['full_table_name'], $data);

						$this->clear_approval_sequence($data_menu,$data_id);
						$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,$approval_type,$datasession);

						break;
					case 'Reject':
						$tmp = array(
							'last_approval_process_by' => $datasession['username'],
					        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
					        'status' => "Reject"
						);

						$data = array();
						$data = array_merge($data,$tmp);

						$this->ci->db->where('Id', $data_id);
						$this->ci->db->update($data_menu['full_table_name'], $data);

						$this->clear_approval_sequence($data_menu,$data_id);
						$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,$approval_type,$datasession);

						break;
					case 'Approve':
						$status_approve = '';
						$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);
						
						$app_init = $this->ci->app_init->app_init();
						$applat_db = $app_init['applat_db_name'];

						$this->ci->db->select('wflayer_sequence');
						$this->ci->db->from($applat_db.'.trnworkflowseq');
						$this->ci->db->where('menu_id', $requestor_form_menu_id);
						$this->ci->db->where('data_id', $data_id);
						$this->ci->db->where('username', $datasession['username']);
						$this->ci->db->where('is_data_displayed', 1);
						
						$query = $this->ci->db->get()->result();
						$wflayer_sequence = $query[0]->wflayer_sequence;

						# hide/close pending request for other approvers of the same level
						$data = array();
						$data = array(
						        'is_data_displayed' => 0
							);
						$this->ci->db->where('menu_id', $requestor_form_menu_id);
						$this->ci->db->where('data_id', $data_id);
						$this->ci->db->where('wflayer_sequence',$wflayer_sequence);
						$this->ci->db->where('username != "'.$datasession['username'].'"', NULL);
						$this->ci->db->update($applat_db.'.trnworkflowseq', $data);
						#
						
						# set approver status by the approver
						$data = array();
						$data = array(
						        'status' => 'Approve',
						        'is_data_displayed' => 0
							);
						$this->ci->db->where('menu_id', $requestor_form_menu_id);
						$this->ci->db->where('data_id', $data_id);
						$this->ci->db->where('username',$datasession['username']);
						$this->ci->db->where('is_data_displayed', 1);
						$this->ci->db->update($applat_db.'.trnworkflowseq', $data);
						#
						
						# set next approval level
						$next_sequence_number = $wflayer_sequence - 1;
						
						if ($next_sequence_number == 0) { #if there is no other level approvel, process done.
							$approval_string = "Full Approve";

							$tmp = array(
								'last_approval_process_by' => $datasession['username'],
						        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
						        'approval_id' => NULL,
						        'approval_page' => NULL,
						        'status' => $approval_string
							);

							$data = array();
							$data = array_merge($data,$tmp);
							
							#customized fot MUNDIPHARMA, ePMAP Project 2020, update request reference number & validity date
							if ($data_menu['full_table_name'] == 'epmap_req_material_data') {

								$full_approved_date = $data['last_approval_process_date'];
								$full_approved_date_array = explode(" ", $full_approved_date);
								$full_approved_date = $full_approved_date_array[0];
								$full_approved_date = new DateTime($full_approved_date);

								$pmap_validity_date = $full_approved_date;
								$interval = new DateInterval('P2Y'); #PMAP Validity period of 2 years
								$pmap_validity_date->add($interval);

								$full_approved_date_tmp = $data['last_approval_process_date'];
								$full_approved_date_array_tmp = explode(" ", $full_approved_date_tmp);
								$full_approved_date_tmp = $full_approved_date_array_tmp[0];
								$full_approved_date_tmp = new DateTime($full_approved_date_tmp);

								$this->ci->db->select('request_reference_number, coalesce(is_regional_required,0) is_regional_required');
								$this->ci->db->from($data_menu['full_table_name']);
								$this->ci->db->where('Id', $data_id);
								
								$query = $this->ci->db->get()->result();

								$month_approval_code = 100 + (int) $full_approved_date_tmp->format('m');
								$month_approval_code = (string) $month_approval_code;
								$month_approval_code = substr($month_approval_code,1);

								$year_approval_code = (string) $full_approved_date_tmp->format('Y');
								$year_approval_code = substr($year_approval_code,2);

								$request_reference_number = $query[0]->request_reference_number.'/'.$month_approval_code.$year_approval_code;

								if ($query[0]->is_regional_required == 0) {
									$tmp = array(
										'request_reference_number' => $request_reference_number,
										'full_approve_date' => $data['last_approval_process_date'],
										'pmap_validity_date' => $pmap_validity_date->format('Y-m-d')
									);
								} else {
									$tmp = array(
										'full_approve_date' => $data['last_approval_process_date'],
									);
								}
								// $tmp = array(
								// 	'request_reference_number' => $request_reference_number,
							    //     'full_approve_date' => $data['last_approval_process_date'],
							    //     'pmap_validity_date' => $pmap_validity_date->format('Y-m-d')
								// );

								$data = array_merge($data,$tmp);
							}
							#
							
							$this->ci->db->where('Id', $data_id);
							$this->ci->db->update($data_menu['full_table_name'], $data);

							$this->clear_approval_sequence($data_menu,$data_id);
							$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,$approval_string,$datasession);
						} else { #if there is a next approval level, then show the request to next approver
							$approval_string = "Waiting approval";

							$tmp = array(
								'last_approval_process_by' => $datasession['username'],
						        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
						        'status' => $approval_string
							);

							$data = array();
							$data = array_merge($data,$tmp);
							
							$this->ci->db->where('Id', $data_id);
							$this->ci->db->update($data_menu['full_table_name'], $data);

							$data = array();
							$data = array(
							        'is_data_displayed' => 1
							);

							$this->ci->db->where('menu_id', $requestor_form_menu_id);
							$this->ci->db->where('data_id', $data_id);
							$this->ci->db->where('wflayer_sequence', $next_sequence_number);
							$this->ci->db->where('status is null',NULL);
							$this->ci->db->update($applat_db.'.trnworkflowseq', $data);

							#customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is approved with changes then set to revise, if there is NO approved with changes in log then set to next approver SKIP MSL
							if ($data_menu['full_table_name'] == 'epmap_req_material_data') {

								#set required regional review by if request is corporate material
								if (in_array('Medical Scientific Liaison user group', $datasession['usergroup_name'])) {
									$data = array();
									$data = array(
									        'required_regional_process_by' => $datasession['username']
									);

									$this->ci->db->where('is_regional_required', 1);
									$this->ci->db->where('Id', $data_id);
									$this->ci->db->where('required_regional_process_by is null', NULL);
									$this->ci->db->update($data_menu['full_table_name'], $data);
								}
								#

								$this->ci->db->select('epmap_req_material_reviewer_approver_file.createdate, reviewer_approver_note, fullname');
								$this->ci->db->from('epmap_req_material_reviewer_approver_file');
								$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = epmap_req_material_reviewer_approver_file.createby');
								$this->ci->db->where('epmap_req_material_data_id', $data_id);
								$this->ci->db->where('is_approved_with_changes', 1);
								$this->ci->db->order_by('epmap_req_material_reviewer_approver_file.createdate', 'asc');
								
								$query_approved_with_changes = $this->ci->db->get()->result();

								$this->ci->db->select($applat_db.'.trnworkflowseq.Id');
								$this->ci->db->from($applat_db.'.trnworkflowseq');
								$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnworkflowseq.username');
								$this->ci->db->join($applat_db.'.refnoncoreusergroups_users',$applat_db.'.refnoncoreusergroups_users.user_id = '.$applat_db.'.refnoncoreusers.Id');
								$this->ci->db->join($applat_db.'.refnoncoreusergroups',$applat_db.'.refnoncoreusergroups_users.group_id = '.$applat_db.'.refnoncoreusergroups.Id');
								$this->ci->db->where('menu_id', $requestor_form_menu_id);
								$this->ci->db->where('data_id', $data_id);
								$this->ci->db->where('is_data_displayed', 1);
								$this->ci->db->where('wflayer_sequence', $next_sequence_number);
								$this->ci->db->where($applat_db.'.refnoncoreusergroups_users.isdelete', 0);
								$this->ci->db->where($applat_db.'.refnoncoreusergroups.usergroup_name', 'Medical Scientific Liaison user group');
								
								$query_waiting_appoval_msl = $this->ci->db->get()->result();

								if ($query_approved_with_changes) {
									
									if ($query_waiting_appoval_msl) {
										#if next approver is MSL and request is approved with changes, then clear approval sequence and set status to revise (return to requestor)
										$tmp = array(
											'last_approval_process_by' => $datasession['username'],
									        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
									        'status' => "Revise"
										);

										$data = array();
										$data = array_merge($data,$tmp);
										
										$this->ci->db->where('Id', $data_id);
										$this->ci->db->update($data_menu['full_table_name'], $data);

										$approved_with_changes_notes = "";
										foreach ($query_approved_with_changes as $row) {
											$reviewer_approver_note = html_entity_decode($row->reviewer_approver_note);
											$reviewer_approver_note_array = explode("<br><font", $reviewer_approver_note);

											if (count($reviewer_approver_note_array) > 1) {
												$reviewer_approver_note = $reviewer_approver_note_array[0];
											}

											$approved_with_changes_notes .= "<li>".$row->fullname." (".$row->createdate.") : ".$reviewer_approver_note."</li>";
										}

										if ($approved_with_changes_notes != "") {
											$comm_msg .= "<br><u>APPROVED WITH CHANGES NOTES:</u><br><ul>".$approved_with_changes_notes."</ul>";
										}

										$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Revise',$datasession);
										$this->clear_approval_sequence($data_menu,$data_id);
										#[END OF] if next approver is MSL and request is approved with changes, then clear approval sequence and set status to revise (return to requestor)
									} else {
										$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Approve',$datasession);
										$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);	
									}
								} else {
									#if next approver is MSL, skip MSL for final review in Approved with NO CHANGES
									if ($query_waiting_appoval_msl) {
										$data = array();
										$data = array(
										        'is_data_displayed' => 0
										);

										$this->ci->db->where('menu_id', $requestor_form_menu_id);
										$this->ci->db->where('data_id', $data_id);
										$this->ci->db->where('wflayer_sequence', $next_sequence_number); #skip MSL
										$this->ci->db->where('status is null',NULL);
										$this->ci->db->update($applat_db.'.trnworkflowseq', $data);

										$next_sequence_number = $next_sequence_number - 1; #show to next approver

										if ($next_sequence_number == 0) { #skip MSL, approved with NO changes and full approve
											$approval_string = "Full Approve";

											$tmp = array(
												'last_approval_process_by' => $datasession['username'],
										        'last_approval_process_date' => $this->ci->datetime->get_current_datetime(),
										        'approval_id' => NULL,
										        'approval_page' => NULL,
										        'status' => $approval_string
											);

											$data = array();
											$data = array_merge($data,$tmp);
											
											#customized fot MUNDIPHARMA, ePMAP Project 2020, update request reference number & validity date
											if ($data_menu['full_table_name'] == 'epmap_req_material_data') {

												$full_approved_date = $data['last_approval_process_date'];
												$full_approved_date_array = explode(" ", $full_approved_date);
												$full_approved_date = $full_approved_date_array[0];
												$full_approved_date = new DateTime($full_approved_date);

												$pmap_validity_date = $full_approved_date;
												$interval = new DateInterval('P2Y'); #PMAP Validity period of 2 years
												$pmap_validity_date->add($interval);

												$full_approved_date_tmp = $data['last_approval_process_date'];
												$full_approved_date_array_tmp = explode(" ", $full_approved_date_tmp);
												$full_approved_date_tmp = $full_approved_date_array_tmp[0];
												$full_approved_date_tmp = new DateTime($full_approved_date_tmp);

												$this->ci->db->select('request_reference_number');
												$this->ci->db->from($data_menu['full_table_name']);
												$this->ci->db->where('Id', $data_id);
												
												$query = $this->ci->db->get()->result();

												$month_approval_code = 100 + (int) $full_approved_date_tmp->format('m');
												$month_approval_code = (string) $month_approval_code;
												$month_approval_code = substr($month_approval_code,1);

												$year_approval_code = (string) $full_approved_date_tmp->format('Y');
												$year_approval_code = substr($year_approval_code,2);

												$request_reference_number = $query[0]->request_reference_number.'/'.$month_approval_code.$year_approval_code;

												$tmp = array(
													'request_reference_number' => $request_reference_number,
											        'full_approve_date' => $data['last_approval_process_date'],
											        'pmap_validity_date' => $pmap_validity_date->format('Y-m-d')
												);

												$data = array_merge($data,$tmp);
											}
											#
											
											$this->ci->db->where('Id', $data_id);
											$this->ci->db->update($data_menu['full_table_name'], $data);

											$this->clear_approval_sequence($data_menu,$data_id);
											$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,$approval_string,$datasession);
										} else {
											$data = array();
											$data = array(
											        'is_data_displayed' => 1
											);

											$this->ci->db->where('menu_id', $requestor_form_menu_id);
											$this->ci->db->where('data_id', $data_id);
											$this->ci->db->where('wflayer_sequence', $next_sequence_number);
											$this->ci->db->where('status is null',NULL);
											$this->ci->db->update($applat_db.'.trnworkflowseq', $data);

											$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Approve',$datasession);
											$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);
										}
									} else {
										$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Approve',$datasession);
										$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);		
									}
									#
								}
							} else {
								$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Approve',$datasession);
								$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);
							}
							#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is approved with changes then set to revise, if there is NO approved with changes in log then set to next approver SKIP MSL

							//$this->send_email_to_requestor($data_menu,$data_id,$comm_msg,'Approve',$datasession);
							//$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);
						}

						break;
					default:
						$reference_number = $this->generate_request_reference_number($data_menu,$data_id);
						$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

						$tmp = array(
						        'submit_date' => $this->ci->datetime->get_current_datetime(),
						        'reference_running_number' => $reference_number['reference_running_number'],
						        'request_reference_number' => $reference_number['request_reference_number'],
						        'product_running_number' => $reference_number['product_running_number'],
						        'approval_id' => md5($reference_number['request_reference_number']),
						        'approval_page' => $this->get_approval_url($data_menu),
						        'status' => 'Waiting approval'
							);

						$data = array();
						$data = array_merge($data,$tmp);
						
						$this->ci->db->where('Id', $data_id);
						$this->ci->db->update($data_menu['full_table_name'], $data);

						#customized fot MUNDIPHARMA, ePMAP Project 2020, update to primary epmap file log table
						$submit_date = $tmp['submit_date'];
						$primary_file_hash_link = md5($submit_date);

						$this->ci->db->select('pmap_file');
						$this->ci->db->from($data_menu['full_table_name']);
						$this->ci->db->where('Id', $data_id);
						
						$query = $this->ci->db->get()->result();

						$pmap_file = $query[0]->pmap_file;

						$tmp = array(
						        'hash_link' => $primary_file_hash_link,
						        'createby' => $datasession['username'],
						        'createdate' => $submit_date,
						        'isdelete' => 0,
						        'epmap_req_material_data_id' => $data_id,
						        'pmap_file' => $pmap_file,
						        'material_file_submit_date' => $submit_date
							);

						$data = array();
						$data = array_merge($data,$tmp);
						
						$this->ci->db->insert('epmap_req_material_primary_file_log', $data);
						#

						$app_init = $this->ci->app_init->app_init();
						$applat_db = $app_init['applat_db_name'];

						$this->ci->db->select('wflayer_sequence');
						$this->ci->db->from($applat_db.'.trnworkflowseq');
						$this->ci->db->where('menu_id', $requestor_form_menu_id);
						$this->ci->db->where('data_id', $data_id);
						$this->ci->db->where('username', $datasession['username']);
						$this->ci->db->order_by('wflayer_sequence','desc');
						
						$query = $this->ci->db->get()->result();
						
						//$wflayer_sequence = $query[0]->wflayer_sequence;
						//$next_sequence_number = $wflayer_sequence - 1;
						
						#customized fot MUNDIPHARMA, ePMAP Project 2020
						if ($query) {
							$wflayer_sequence = $query[0]->wflayer_sequence;
							$next_sequence_number = $wflayer_sequence - 1;
						} else {
							$this->ci->db->select('wflayer_sequence');
							$this->ci->db->from($applat_db.'.trnworkflowseq');
							$this->ci->db->where('menu_id', $requestor_form_menu_id);
							$this->ci->db->where('data_id', $data_id);
							$this->ci->db->order_by('wflayer_sequence','desc');
							
							$query = $this->ci->db->get()->result();

							$wflayer_sequence = $query[0]->wflayer_sequence;
							$next_sequence_number = $wflayer_sequence;
						}
						#						
						
						$this->do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence_number);

						break;
				}
			}
		}
		
		return NULL;
	}

	function get_pending_approvers_list_array($data_menu,$data_id) {
		$approvers_list_array = array();

		if ($data_id == '') {
			return $approvers_list_array;
		}

		if (is_numeric($data_menu)) {
			$requestor_form_menu_id = $data_menu;
		} else {
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);
		}
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select('data_id, fullname');
		$this->ci->db->from($applat_db.'.trnworkflowseq');
		$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnworkflowseq.username');
		//$this->ci->db->where('menu_id in ('.$requestor_form_menu_id.')',NULL);

		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, display all pending approver for bpom form page
		if (strtolower($data_menu['url']) == 'trans_frm_bpomgov_status') {
			$this->ci->db->join($applat_db.'.refmenu',$applat_db.'.trnworkflowseq.menu_id = '.$applat_db.'.refmenu.Id');
			$this->ci->db->join($applat_db.'.refapps',$applat_db.'.refmenu.app_id = '.$applat_db.'.refapps.Id');
			$this->ci->db->where($applat_db.'.refapps.app_code',$app_init['app_code']);
		} else {
			$this->ci->db->where('menu_id in ('.$requestor_form_menu_id.')',NULL);
		}
		#
		
		$this->ci->db->where('data_id in ('.$data_id.')', NULL);
		$this->ci->db->where('is_data_displayed',1);
		$this->ci->db->where('status is NULL',NULL);
		
		$query = $this->ci->db->get()->result();

		if ($query) {
			foreach ($query as $row) {
				if (!$approvers_list_array) {
					$approvers_list_array[$row->data_id] = '';
				} else {
					if (!array_key_exists($row->data_id, $approvers_list_array)) {
						$approvers_list_array[$row->data_id] = '';
					}
				}
			}

			foreach ($approvers_list_array as $key => $value) {
				$tmp = '';
				foreach ($query as $row) {
					if ($key == $row->data_id) {
						$tmp .= "<li>".$row->fullname."</li>";
					}
				}

				if ($tmp != '') {
					$approvers_list_array[$key] = "</br><ul>".$tmp."</ul>";
				}
			}
		}

		return $approvers_list_array;
	}

	function get_pending_approvers_list($data_menu,$data_id) {
		$tmp = "";

		if (is_numeric($data_menu)) {
			$requestor_form_menu_id = $data_menu;
		} else {
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);
		}
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('fullname');
		$this->ci->db->from($applat_db.'.trnworkflowseq');
		$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnworkflowseq.username');
		$this->ci->db->where('menu_id in ('.$requestor_form_menu_id.')',NULL);
		$this->ci->db->where('data_id',$data_id);
		$this->ci->db->where('is_data_displayed',1);
		$this->ci->db->where('status is NULL',NULL);
		
		$query = $this->ci->db->get()->result();

		if ($query) {
			$tmp = "</br>";
			$tmp .= "<ul>";
			foreach ($query as $row) {
				$tmp.="<li>".$row->fullname."</li>";
			}
			$tmp .= "</ul>";
		}

		return $tmp;
	}

	function get_origin_menu_id_for_grantdonation($data_menu) {
		$menu_id = NULL;

		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('Id');
		$this->ci->db->from($applat_db.'.refmenu');
		$this->ci->db->where('full_table_name',$data_menu['full_table_name']);
		$this->ci->db->where('is_workflowdata',1);		
		
		$query = $this->ci->db->get()->result();

		$menu_id = $query[0]->Id;

		return $menu_id;
	}

	function get_menu_id($url) {
		$menu_id = NULL;

		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('Id');
		$this->ci->db->from($applat_db.'.refmenu');
		$this->ci->db->where('url',$url);
		$this->ci->db->where('is_workflowdata',1);
		$this->ci->db->where('isdelete',0);		
		
		$query = $this->ci->db->get()->result();

		$menu_id = $query[0]->Id;

		return $menu_id;
	}

	function get_origin_data_menu_id($data_menu) {
		$url = $data_menu['url'];

		if ($url == 'apprv_frm_opioid') { return $this->get_menu_id('req_frm_opioid'); }
		if ($url == 'apprv_frm_internal_training') { return $this->get_menu_id('req_frm_internal_training'); }
		if ($url == 'apprv_frm_product_material') { return $this->get_menu_id('req_frm_product_material'); }
		if ($url == 'apprv_frm_speaker_brief') { return $this->get_menu_id('req_frm_speaker_brief'); }
		if ($url == 'apprv_frm_corporate_materials') { return $this->get_menu_id('req_frm_corporate_materials'); }
		if ($url == 'apprv_frm_other') { return $this->get_menu_id('req_frm_other'); }

		if ($url == 'apprv_frm_key_promotional_aid') { return $this->get_menu_id('req_frm_key_promotional_aid'); }
		if ($url == 'apprv_frm_in_store_pos') { return $this->get_menu_id('req_frm_in_store_pos'); }
		if ($url == 'apprv_frm_social_media') { return $this->get_menu_id('req_frm_social_media'); }
		if ($url == 'apprv_frm_ecommerce') { return $this->get_menu_id('req_frm_ecommerce'); }
		if ($url == 'apprv_frm_gimmicks') { return $this->get_menu_id('req_frm_gimmicks'); }

		return $data_menu['id'];

	}
	
	function check_is_data_displayed($data_menu,$data_id,$datasession) {
		$tmp = FALSE;

		$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select('Id');
		$this->ci->db->from($applat_db.'.trnworkflowseq');
		$this->ci->db->where('menu_id in ('.$requestor_form_menu_id.')',NULL);
		$this->ci->db->where('data_id',$data_id);
		$this->ci->db->where('username',$datasession['username']);
		$this->ci->db->where('status is NULL',NULL);
		$this->ci->db->where('is_data_displayed',1);
		
		$query = $this->ci->db->get()->result();
		
		if ($query) {
			$tmp = TRUE;
		}

		return $tmp;
	}

	function insert_wf_log($data_menu,$data_id,$approval_action,$comm_msg,$datasession) {

		if ($datasession) {
			if ($datasession['username'] != "") {
				$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);
				$data_id =  $this->ci->dp_eform->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

				$data = array();
				$tmp = array(
				        'menu_id' => $requestor_form_menu_id,
				        'data_id' => $data_id,
				        'status' => $approval_action,
				        'comm_msg' => $comm_msg,
				        'createby' => $datasession['username'],
				        'createdate' => $this->ci->datetime->get_current_datetime()
				);
				$data = array_merge($data,$tmp);

				$app_init = $this->ci->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];
				
				$this->ci->db->insert($applat_db.'.trnlogworkflow', $data);	
			}
		}

		return NULL;
	}
	
	function show_approval_comm_box($datasession,$data_menu,$detail_data,$data_id = 0) {
		
		$tmp = FALSE;

		if ($data_id != 0) {
			$data_id = $this->ci->dp_eform->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);
			$detail_data['Id'] = $data_id;
		}
		
		if (array_key_exists('Id', $detail_data)) {
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

			$app_init = $this->ci->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->ci->db->select('Id,status');
			$this->ci->db->from($applat_db.'.trnworkflowseq');
			$this->ci->db->where('menu_id',$requestor_form_menu_id);
			$this->ci->db->where('data_id',$detail_data['Id']);
			$this->ci->db->where('username',$datasession['username']);
			$this->ci->db->where('is_data_displayed',1);
			
			$query = $this->ci->db->get()->result();

			if ($query) {
				if ($query[0]->status == NULL) {
					$tmp = TRUE;
				}
			}else{
				if (array_key_exists('createby', $detail_data)) {
					if ($detail_data['createby'] == $datasession['username']) {
						$tmp = TRUE;

						$this->ci->db->select('status');
						$this->ci->db->from($data_menu['full_table_name']);
						$this->ci->db->where($data_menu['full_table_name'].'.Id',$detail_data['Id']);
						
						$query = $this->ci->db->get()->result();
						if ($query[0]->status == 'Reject') {
							$tmp = FALSE;
						}
					}else{
						$tmp = FALSE;
					}		
				}else{
					$tmp = FALSE;
				}
			}
		} else {
			$tmp = FALSE;
		}
		
		return $tmp;
	}

	function get_user_fullname($user_name) {
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('fullname');
		$this->ci->db->from($applat_db.'.refnoncoreusers');
		$this->ci->db->where('username', $user_name);
		
		$query = $this->ci->db->get()->result();

		return $query[0]->fullname;
	}

	function get_detail_content_body_email ($data_menu,$data_id) {
		$tmp = "";

		$this->ci->db->select();
		$this->ci->db->from('v_body_email_content');
		$this->ci->db->where('request_id',$data_id);
		
		$query = $this->ci->db->get()->result();
		
		$tmp  = "Material Name : ".$query[0]->material_name."<br>";
		$tmp  .= "Intended Audience : ".$query[0]->audience_type."<br>";
		$tmp  .= "First Use Date : ".$this->ci->datetime->convert_mysql_date_format_to_short_string($query[0]->first_use_date)."<br>";
		$tmp  .= "Material Type : ".$query[0]->material_type_name."<br>";
		
		$tmp2 = array();
		$tmp2['content_email'] = $tmp;
		$tmp2['reference_number'] = $query[0]->request_reference_number;
		$tmp2['data_id'] = $query[0]->request_id;
		$tmp2['approval_page'] = $query[0]->approval_page;
		$tmp2['approval_id'] = $query[0]->approval_id;

		#customized fot MUNDIPHARMA, ePMAP Project 2020, include bpom & regional
		$tmp2['is_bpom_required'] = $query[0]->is_bpom_required;
		$tmp2['bpom_process_status'] = $query[0]->bpom_process_status;
		$tmp2['bpom_finish_process_date'] = $query[0]->bpom_finish_process_date;
		$tmp2['is_regional_required'] = $query[0]->is_regional_required;
		$tmp2['regional_status'] = $query[0]->regional_status;
		$tmp2['regional_finish_process_date'] = $query[0]->regional_finish_process_date;

		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, include bpom

		if ($tmp2['reference_number'] == '' || $tmp2['reference_number'] == NULL) {
			$tmp2['reference_number'] = '';
		}

		return $tmp2;
	}
	
	function get_detail_request_for_email($data_menu,$data_id) {
		$content = "";
		$menu_id = $this->get_origin_data_menu_id($data_menu);
		

		if ($data_menu['full_table_name'] == 'epmap_req_material_data') {
			$content = $this->get_detail_content_body_email($data_menu,$data_id);
		}

		return $content;
	}

	function do_send_email_to_next_approvers($data_menu,$data_id,$next_sequence) {
		$datasession = $this->ci->session->userdata('logged_in');
		$data_smtp = $this->get_data_smtp();

		if ($data_smtp['is_enable'] == 1) {
			$requestor_form_menu_id = $this->get_origin_data_menu_id($data_menu);

			$app_init = $this->ci->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$this->ci->db->select('menu_id,data_id,trnworkflowseq.username,email_address,fullname,trnworkflowseq.createby,comm_msg');
			$this->ci->db->from($applat_db.'.trnworkflowseq');
			$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnworkflowseq.username');
			$this->ci->db->where($applat_db.'.trnworkflowseq.menu_id',$requestor_form_menu_id);
			$this->ci->db->where($applat_db.'.trnworkflowseq.data_id',$data_id);
			$this->ci->db->where('wflayer_sequence', $next_sequence);
			
			$query = $this->ci->db->get()->result();

			$content = $this->get_detail_request_for_email($data_menu,$data_id);
			
			$app_init = $this->ci->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];

			foreach ($query as $row) {
				$body_mail_content = "Dear ".$row->fullname.",<br><br><br>
						You have a pending approval for the following Promotional Material Review:
						<br><br>".$content['content_email']."
						<br><br>Reference : ".$content['reference_number']."
						<br><br>Requestor : ".$this->get_user_fullname($row->createby)."
						<br><br>Requestor Message : ".$row->comm_msg."
						<br><br>Please kindly click ".anchor(site_url()."landingpage/emailhandling/".$content['approval_id'], "here")." to log in the application and process the approval. Thank you.<br><br><br>Regards,<br><br>@@sender_name<br><br>*)This email is generated automatically, no need to reply.";

				$data = array();
				$data = array(
					        'menu_id' => $requestor_form_menu_id,
					        'data_id' => $data_id,
					        'username' => $row->username,
					        'to_email_address' => $row->email_address,
					        'email_subject' => "Request for Approval e-PMAP#.".$content['reference_number'],
					        'email_body_content' => $body_mail_content,
					        'createby' => $datasession['username'],
					        'createdate' => $this->ci->datetime->get_current_datetime()
						);
				
				$this->ci->db->insert($applat_db.'.refmailmanjobs', $data);				 
			}
			//$this->do_send_email();
		}
		return NULL;
	}
	
	
	function get_user_group($username) {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->distinct();
		$this->ci->db->select('usergroup_name');
		$this->ci->db->from($applat_db.'.v_menu');
		$this->ci->db->where('username',$username);

		return $this->ci->db->get()->result();
	}

	function generate_request_reference_number($data_menu,$data_id) {
		$tmp = array();
		$event_date_field_name = NULL;
		$prefix = NULL;
		$year_event = NULL;
		$max_ref_number = NULL;
		
		$event_date_field_name = 'createdate';

		#cutomized fot MUNDIPHARMA, ePMAP Project 2020
		//$this->ci->db->select('activity_type');
		$this->ci->db->select('product_prefix');
		#
		$this->ci->db->from($data_menu['full_table_name']);
		$this->ci->db->where('Id',$data_id);
		
		$query = $this->ci->db->get()->result();

		//$prefix = $query[0]->activity_type;
		$prefix = $query[0]->product_prefix;
		$reference_generator_id = $this->ci->app_init->app_init()['reference_generator_id'];

		#cutomized fot MUNDIPHARMA, ePMAP Project 2020, check if data is first request then get numbet start from master startnumber
		$this->ci->db->select('max(reference_running_number) as max_ref_number');
		$this->ci->db->from($data_menu['full_table_name']);
		$this->ci->db->where('request_reference_number is not null',NULL);
		
		$query = $this->ci->db->get()->result();
		
		if ((int) $query[0]->max_ref_number == 0) {
			$this->ci->db->select('start_number');
			$this->ci->db->from('epmap_ref_startnumber');
			
			$query = $this->ci->db->get()->result();

			$start_number = (int) $query[0]->start_number;
		} else {
			$start_number = (int) $query[0]->max_ref_number;
		}

		$start_number += 1;
		$reference_generator_id .= '-'.$start_number;
		#
		
		$this->ci->db->select('year('.$event_date_field_name.') as year_event, reference_running_number, request_reference_number,additional_running_number,product_running_number');
		$this->ci->db->from($data_menu['full_table_name']);
		$this->ci->db->where('Id',$data_id);
		
		$query = $this->ci->db->get()->result();
		if ($query[0]->request_reference_number == '' || $query[0]->request_reference_number == NULL) {
			$year_event = $query[0]->year_event;

			#cutomized fot MUNDIPHARMA, ePMAP Project 2020
			/*$this->ci->db->select('max(reference_running_number) as max_ref_number');
			$this->ci->db->from($data_menu['full_table_name']);
			$this->ci->db->where('year('.$event_date_field_name.')',$year_event);
			$this->ci->db->where('activity_type',$prefix);*/

			$this->ci->db->select('max(product_running_number) as max_ref_number');
			$this->ci->db->from($data_menu['full_table_name']);
			$this->ci->db->where('product_prefix',$prefix);
			
			
			$query = $this->ci->db->get()->result();

			if ((int) $query[0]->max_ref_number == 0) {
				$app_init = $this->ci->app_init->app_init();
				$applat_db = $app_init['applat_db_name'];

				$this->ci->db->select('epmap_start_number');
				$this->ci->db->from($applat_db.'.cmbr_ref_product');
				$this->ci->db->where('epmap_prefix',$prefix);
				
				$query = $this->ci->db->get()->result();

				$max_ref_number = $query[0]->epmap_start_number;
			} else {
				$max_ref_number = $query[0]->max_ref_number;
			}

			$max_ref_number += 1;
			#

			//$tmp['reference_running_number'] = $max_ref_number;
			$tmp['reference_running_number'] = $start_number;
			$tmp['request_reference_number'] = $reference_generator_id.'/'.strtoupper($prefix).'/'.$max_ref_number;
			$tmp['product_running_number'] = $max_ref_number;
			$tmp['additional_running_number'] = NULL;
		} else {
			$tmp['reference_running_number'] = $query[0]->reference_running_number;
			$tmp['request_reference_number'] = $query[0]->request_reference_number;
			$tmp['product_running_number'] = $query[0]->product_running_number;
			$tmp['additional_running_number'] = $query[0]->additional_running_number;
		}
		
		return $tmp;
	}

	function insert_workflow_sequences($data) {
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->insert($applat_db.'.trnworkflowseq', $data);
	}

	function get_admin_supports($requestor_username) {
		
		$this->ci->db->select('adminsupport_username');
		$this->ci->db->from('v_adminsupport_all_requestors');
		$this->ci->db->where('requestor_username',$requestor_username);

		$query = $this->ci->db->get()->result();

		return $query;
	}

	function sync_goa_to_approval_sequence() {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select('delegator_username, actor_username');
		$this->ci->db->from($applat_db.'.v_delegator_actor');
		
		$query_delegator_actor = $this->ci->db->get()->result();

		# remove in trnworkflowseq for inactive actor in v_delegator_actor
		$this->ci->db->distinct();
		$this->ci->db->select('username, delegator');
		$this->ci->db->where("delegator is not null and delegator <> ''",NULL);
		$this->ci->db->from($applat_db.'.trnworkflowseq');
		
		$query_inactive_actor = $this->ci->db->get()->result();

		foreach ($query_inactive_actor as $row_inactive_actor) {
			$inactive = TRUE;

			foreach ($query_delegator_actor as $row_delegator_actor) {
				if (strtoupper($row_delegator_actor->actor_username) == strtoupper($row_inactive_actor->username) && strtoupper($row_delegator_actor->delegator_username) == strtoupper($row_inactive_actor->delegator)) {
					$inactive = FALSE;
					break;
				}
			}

			if ($inactive) {
				$this->ci->db->where('username', $row_inactive_actor->username);
				$this->ci->db->where('delegator', $row_inactive_actor->delegator);
				$this->ci->db->delete($applat_db.'.trnworkflowseq');				
			}
		}
		# end remove in trnworkflowseq for inactive actor in v_delegator_actor
		
		# start insert from v_delegator_actor
		foreach ($query_delegator_actor as $row_delegator_actor) {
			$existing_data_id_actor_sequence = "-1";
			
			$actor_username = $row_delegator_actor->actor_username;
			$delegator_username = $row_delegator_actor->delegator_username;

			$this->ci->db->select('menu_id');
			$this->ci->db->where('username',$delegator_username);
			$this->ci->db->from($applat_db.'.trnworkflowseq');
			
			$query_menu_id_delegator_sequence = $this->ci->db->get()->result();

			foreach ($query_menu_id_delegator_sequence as $row_menu_id_delegator_sequence) {
				$menu_id_delegator =  $row_menu_id_delegator_sequence;

				$this->ci->db->select('data_id');
				$this->ci->db->where('username',$actor_username);
				$this->ci->db->where('delegator',$delegator_username);
				$this->ci->db->where('menu_id',$menu_id_delegator->menu_id);
				$this->ci->db->from($applat_db.'.trnworkflowseq');
				
				$query_existing_actor_sequence = $this->ci->db->get()->result();

				foreach ($query_existing_actor_sequence as $row_existing_actor_sequence) {
					$existing_data_id_actor_sequence .= ($existing_data_id_actor_sequence == "") ? $row_existing_actor_sequence->data_id : ",".$row_existing_actor_sequence->data_id ;
				}

				$this->ci->db->select('wfreqtype_code, menu_id, data_id, wflayer_sequence, status, is_data_displayed, createby, createdate');
				$this->ci->db->where('username',$delegator_username);
				$this->ci->db->where('menu_id',$menu_id_delegator->menu_id);
				$this->ci->db->where("data_id not in (".$existing_data_id_actor_sequence.")",NULL);
				$this->ci->db->from($applat_db.'.trnworkflowseq');

				$query_update_actor_sequence = $this->ci->db->get()->result();

				foreach ($query_update_actor_sequence as $row_update_actor_sequence) {
					$data = array(
				        'wfreqtype_code' => $row_update_actor_sequence->wfreqtype_code,
				        'menu_id' => $row_update_actor_sequence->menu_id,
				        'data_id' => $row_update_actor_sequence->data_id,
				        'username' => $actor_username,
				        'delegator' => $delegator_username,
				        'wflayer_sequence' => $row_update_actor_sequence->wflayer_sequence,
				        'status' => $row_update_actor_sequence->status,
				        'is_data_displayed' => $row_update_actor_sequence->is_data_displayed,
				        'createby' => $row_update_actor_sequence->createby,
				        'createdate' => $row_update_actor_sequence->createdate
					);
			
					$this->ci->db->insert($applat_db.'.trnworkflowseq', $data);
				}
			}
		}
		# end insert from v_delegator_actor
		
		return NULL;
	}

	function check_approvers_approved($data_menu,$data_id,$user_name) {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$tmp = array();

		$this->ci->db->select($applat_db.'.trnlogworkflow.Id');
		$this->ci->db->select($applat_db.'.trnlogworkflow.createby');
		$this->ci->db->select($applat_db.'.refnoncoreusergroups.usergroup_name');
		$this->ci->db->from($applat_db.'.trnlogworkflow');
		$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.trnlogworkflow.createby');
		$this->ci->db->join($applat_db.'.refnoncoreusergroups_users',$applat_db.'.refnoncoreusers.Id = '.$applat_db.'.refnoncoreusergroups_users.user_id');
		$this->ci->db->join($applat_db.'.refnoncoreusergroups',$applat_db.'.refnoncoreusergroups.Id = '.$applat_db.'.refnoncoreusergroups_users.group_id');
		$this->ci->db->where('menu_id',$data_menu['id']);
		$this->ci->db->where('data_id',$data_id);
		$this->ci->db->where($applat_db.'.trnlogworkflow.createby in ('.$user_name.')',NULL);
		$this->ci->db->where('status in ("Approve","Reject")',NULL);

		$query = $this->ci->db->get()->result();

		if ($query) {
			foreach ($query as $row) {
				if (!array_key_exists($row->createby, $tmp)) {
					$tmp[$row->createby] = array();
				}

				if (!in_array($row->usergroup_name, $tmp[$row->createby])) {
					array_push($tmp[$row->createby],$row->usergroup_name);
				}
			}
		}

		return $tmp;
	}

	function check_request_is_revised($menu_id, $data_id){
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$this->ci->db->select('Id');
		$this->ci->db->from($applat_db.'.trnlogworkflow');
		$this->ci->db->where('menu_id',$menu_id);
		$this->ci->db->where('data_id',$data_id);
		$this->ci->db->where('status','Revise');

		$query = $this->ci->db->get()->result();

		if (!$query) {
			$this->ci->db->select('Id');
			$this->ci->db->from('epmap_req_material_reviewer_approver_file');
			$this->ci->db->where('epmap_req_material_data_id',$data_id);
			$this->ci->db->where('is_approved_with_changes',1);

			$query = $this->ci->db->get()->result();
		}

		return $query;
	}

	function get_pic_msl_regulatory($data_menu, $data_id) {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$product_channel = "";

		#check if request product is consumer, else ethical
		$this->ci->db->select($data_menu['full_table_name'].'.Id');
		$this->ci->db->from($data_menu['full_table_name']);
		$this->ci->db->join($applat_db.'.cmbr_ref_product product','product.Id = '.$data_menu['full_table_name'].'.brand_id');
		$this->ci->db->where($data_menu['full_table_name'].'.Id',$data_id);
		$this->ci->db->like('product.product_name','consumer','both');
		
		$query = $this->ci->db->get()->result();

		if ($query) {
			$product_channel = "consumer";
		} else {
			$product_channel = "ethical";
		}

		$this->ci->db->select('msl_username, regulatory_username');
		$this->ci->db->from('v_pic_msl_regulatory');
		$this->ci->db->where('channel',$product_channel);
		
		$query = $this->ci->db->get()->result();

		return $query;
	}

	function generate_approval_sequence($datasession,$data_menu,$data_id,$datapost,$comm_msg) {
		//$wftype_code = '';
		$data_id = $this->ci->dp_eform->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);

		$pic_msl_regulatory = array();
		$pic_msl_regulatory['pic_msl'] = array();
		$pic_msl_regulatory['pic_regulatory'] = array();

		if ($data_menu['full_table_name'] == 'epmap_req_material_data' && !in_array('Medical Scientific Liaison user group', $datasession['usergroup_name'])) {
			$get_pic_msl_regulatory = $this->get_pic_msl_regulatory($data_menu, $data_id);

			if ($get_pic_msl_regulatory) {
				foreach ($get_pic_msl_regulatory as $pic_row) {
					if ($pic_row->msl_username != '') {
						array_push($pic_msl_regulatory['pic_msl'],$pic_row->msl_username);
					}

					if ($pic_row->regulatory_username != '') {
						array_push($pic_msl_regulatory['pic_regulatory'],$pic_row->regulatory_username);
					}
				}
			}
		}
		
		$url = $data_menu['url'];	
		$url_workflow_profile = array('req_frm_opioid' => 'opioid_flow',
								'req_frm_product_material' => 'product_name_flow',
								'req_frm_speaker_brief' => 'speaker_brief_flow',
								'req_frm_key_promotional_aid' => 'key_promo_flow',
								'req_frm_in_store_pos' => 'store_pos_flow',
								'req_frm_social_media' => 'social_media_flow',
								'req_frm_ecommerce' => 'ecommerce_flow',
								'req_frm_gimmicks' => 'gimmicks_flow');

		if (array_key_exists($url, $url_workflow_profile)) {
			$wftype_code = $url_workflow_profile[$url];
		} else {
			if (in_array($url, array('req_frm_internal_training',
									 'req_frm_corporate_materials',
									 'req_frm_other'))) {

				$url_workflow_profile['req_frm_internal_training']['Yes'] = 'training_product_info_flow';
				$url_workflow_profile['req_frm_internal_training']['No'] = 'training_no_product_info_flow';
				$url_workflow_profile['req_frm_corporate_materials']['Yes'] = 'corporate_product_info_flow';
				$url_workflow_profile['req_frm_corporate_materials']['No'] = 'corporate_no_product_info_flow';
				$url_workflow_profile['req_frm_other']['Yes'] = 'other_product_info_flow';
				$url_workflow_profile['req_frm_other']['No'] = 'other_no_product_info_flow';

				$this->ci->db->select('contain_product_claim_related_message');
				$this->ci->db->from($data_menu['full_table_name']);
				$this->ci->db->where('Id',$data_id);
				
				$query = $this->ci->db->get()->result();

				$wftype_code = ($query[0]->contain_product_claim_related_message == 'Yes') ? $url_workflow_profile[$url]['Yes'] : $url_workflow_profile[$url]['No'];
			} else {
				$wftype_code = '';
			}
		}
		
		$next_sequence = 0;
		$current_senquence_number = 0;
		/// get current sequence number /////////////
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('wflayer_sequence,wfreqtype_code');
		$this->ci->db->from($applat_db.'.v_wfseq');
		$this->ci->db->where('username',$datasession['username']);
		$this->ci->db->where('wfreqtype_code',$wftype_code);
		$this->ci->db->order_by('wflayer_sequence','ASC');
		
		$query = $this->ci->db->get()->result();

		foreach ($query as $row) {
			$current_senquence_number = $row->wflayer_sequence;
		}
		/////////////////////////////////////////////
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select();
		$this->ci->db->from($applat_db.'.v_wfseq');
		$this->ci->db->where('wfreqtype_code',$wftype_code);
		#cutomized fot MUNDIPHARMA, ePMAP Project 2020
		$this->ci->db->where('username !=',$datasession['username']);
		#
		$this->ci->db->order_by('wflayer_sequence','ASC');
		
		$query = $this->ci->db->get()->result();

		#customized fot MUNDIPHARMA, ePMAP Project 2020, get approvers for MSL and Regulatory Manager based on product channel
		if ($data_menu['full_table_name'] == 'epmap_req_material_data' && !in_array('Medical Scientific Liaison user group', $datasession['usergroup_name'])) {
			$tmp_query = array();

			foreach($query as $row) {
				switch ($row->usergroup_name) {
					case 'Medical Scientific Liaison user group':
						if (in_array($row->username, $pic_msl_regulatory['pic_msl'])) {
							array_push($tmp_query,$row);
						}
						break;
					case 'epmap - Regulatory Affairs Manager':
						if (in_array($row->username, $pic_msl_regulatory['pic_regulatory'])) {
							array_push($tmp_query,$row);
						}
						break;
					default:
						array_push($tmp_query,$row);
						break;
				}
			}

			$query = $tmp_query;
		}
		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, get approvers for MSL and Regulatory Manager based on product channel

		#customized fot MUNDIPHARMA, ePMAP Project 2020, get approvers who approved from log
		$check_approvers_approved = array();
		$approvers_string = "";
		foreach($query as $row) {
			$approvers_string .= ($approvers_string == "") ? "'".$row->username."'" : ",'".$row->username."'" ;
		}

		if ($approvers_string != "") {
			$check_approvers_approved = $this->check_approvers_approved($data_menu,$data_id,$approvers_string);
		}

		#customized fot MUNDIPHARMA, ePMAP Project 2020, check if request is resubmitted from revised
		$check_request_is_revised = $this->check_request_is_revised($data_menu['id'],$data_id);

		#get approval sequence for Medical Affairs Manager user group
		
		$wflayer_sequence_msl_final_review = 0;

		$this->ci->db->select($applat_db.'.v_wfseq.*');
		$this->ci->db->from($applat_db.'.v_wfseq');
		$this->ci->db->join($applat_db.'.refnoncoreusers',$applat_db.'.refnoncoreusers.username = '.$applat_db.'.v_wfseq.username');
		$this->ci->db->join($applat_db.'.refnoncoreusergroups_users',$applat_db.'.refnoncoreusers.Id = '.$applat_db.'.refnoncoreusergroups_users.user_id');
		$this->ci->db->join($applat_db.'.refnoncoreusergroups',$applat_db.'.refnoncoreusergroups.Id = '.$applat_db.'.refnoncoreusergroups_users.group_id');
		$this->ci->db->where('wfreqtype_code',$wftype_code);
		$this->ci->db->where('refnoncoreusergroups_users.isdelete',0);
		$this->ci->db->where('refnoncoreusergroups.isdelete',0);
		$this->ci->db->where('refnoncoreusergroups.usergroup_name in ("Medical Scientific Liaison user group")',NULL);
		$this->ci->db->order_by('wflayer_sequence','DESC');

		$query_medical_affairs_manager = $this->ci->db->get()->result();

		$wflayer_sequence_medical_affairs_manager = $row->wflayer_sequence;

		foreach ($query_medical_affairs_manager as $row_medical_affairs_manager) {
			$wflayer_sequence_msl_final_review = (int) $row_medical_affairs_manager->wflayer_sequence; #Get layer level for MSL final review
		}

		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, get approvers who approved from log
		
		foreach($query as $row) {
			$data = array();
			$data = array(
			        'menu_id' => $data_menu['id'],
			        'data_id' => $data_id,
			        'username' => $row->username,
			        'wflayer_sequence' => $row->wflayer_sequence,
			        'is_data_displayed' => 0,
			        'wfreqtype_code' => $row->wfreqtype_code,
			        'comm_msg' => $comm_msg,
			        'createby' => $datasession['username'],
			        'createdate' => $this->ci->datetime->get_current_datetime()
			);
			
			if ($row->username == $datasession['username']) {
				if ($current_senquence_number == $row->wflayer_sequence) {
					$tmp = array(
						        'status' => 'Submit'
						    );	
					$data = array_merge($data,$tmp);

				}
			}
			
			# insert workflow sequences
			if ($current_senquence_number > $row->wflayer_sequence || ($current_senquence_number == $row->wflayer_sequence && $row->username == $datasession['username'])) {
				
				/*$this->insert_workflow_sequences($data);
					
				if ($current_senquence_number > $row->wflayer_sequence) {
					$next_sequence = $row->wflayer_sequence;	
				}*/
				#customized fot MUNDIPHARMA, ePMAP Project 2020, check if approvers not yet approved
				if (!$check_approvers_approved) {
					$this->insert_workflow_sequences($data);
					
					if ($current_senquence_number > $row->wflayer_sequence) {
						$next_sequence = $row->wflayer_sequence;
					}
				} else {
					if (!array_key_exists($row->username, $check_approvers_approved)) {

						if (!$check_request_is_revised) {
							$this->insert_workflow_sequences($data);
							if ($current_senquence_number > $row->wflayer_sequence) {
								$next_sequence = $row->wflayer_sequence;
							}	
						} else {
							if ($row->wflayer_sequence <= $wflayer_sequence_msl_final_review) {
								$this->insert_workflow_sequences($data);

								if ($current_senquence_number > $row->wflayer_sequence) {
									$next_sequence = $row->wflayer_sequence;
								}
							}
						}
					} else {
						if (in_array('Medical Scientific Liaison user group', $check_approvers_approved[$row->username])) {
							if ($row->wflayer_sequence == $wflayer_sequence_msl_final_review) {
								$this->insert_workflow_sequences($data);
					
								if ($current_senquence_number > $row->wflayer_sequence) {
									$next_sequence = $row->wflayer_sequence;
								}
							}
						}
					}
				}
				#
			}
		}
		
		////// set data to be displayed for the next approver and insert to mail job, then send email //////////////////
		if ($next_sequence != 0) {
			
			$app_init = $this->ci->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];
			
			$data = array();
			$data = array(
			        'is_data_displayed' => 1
			);
			$this->ci->db->where('menu_id', $data_menu['id']);
			$this->ci->db->where('data_id', $data_id);
			$this->ci->db->where('wflayer_sequence', $next_sequence);
			$this->ci->db->update($applat_db.'.trnworkflowseq', $data);

		}
		//////////////////////////////////////////////////////////////////////////////////////////
		
		return NULL;	
	}
	
	function get_email_data() {
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select($applat_db.'.refmailmanjobs.Id, to_email_address, email_body_content, email_sender_name, email_subject');
		$this->ci->db->from($applat_db.'.refmailmanjobs');
		$this->ci->db->join($applat_db.'.refmenu',$applat_db.'.refmenu.Id = '.$applat_db.'.refmailmanjobs.menu_id');
		$this->ci->db->join($applat_db.'.refapps',$applat_db.'.refapps.Id = '.$applat_db.'.refmenu.app_id');
		$this->ci->db->where('process_sent_date is null',NULL);
		
		$tmp = $this->ci->db->get()->result();

		return $tmp;
	}

	function do_send_email() {
		$data_smtp = $this->get_data_smtp();

		if ($data_smtp['is_enable'] == 1) {
			$config = Array(
				        'protocol' => 'smtp',
				        'smtp_host' => $data_smtp['smtp_host'],
				        'smtp_port' => $data_smtp['smtp_port'],
				        'smtp_user' => $data_smtp['smtp_user'],
				        'smtp_pass' => $data_smtp['smtp_pwd'],
				        'mailtype' => 'html',
				        'charset'   => 'iso-8859-1'
				    	);

			$this->ci->load->library('email', $config);
			$this->ci->email->set_newline("\r\n");
			 
			$mail = $this->ci->email;

			$query = $this->get_email_data();

			$app_init = $this->ci->app_init->app_init();
			$applat_db = $app_init['applat_db_name'];

			foreach ($query as $row) {
				sleep(2);
				$mail->from($data_smtp['sender_address'],$row->email_sender_name);
				$mail->to($row->to_email_address); 
				//$mail->cc('another@another-example.com'); 
				//$mail->bcc('them@their-example.com'); 
				
				$email_body_content = str_replace('@@sender_name', $row->email_sender_name, $row->email_body_content);
				
				$mail->subject($row->email_subject);
				$mail->message($email_body_content);	
				
				$mail_success = $mail->send();

				if ($mail_success) {
					$data = array();
					$data = array(
					        'process_sent_date' => $this->ci->datetime->get_current_datetime()
					);
					$this->ci->db->where('Id', $row->Id);
					$this->ci->db->update($applat_db.'.refmailmanjobs', $data);
				}else{
					$mail->print_debugger();
				}
			}
		}
	}

	function get_data_smtp() {
		$tmp['smtp_host'] = '';
		$tmp['smtp_port'] = '';
		$tmp['smtp_user'] = '';
		$tmp['smtp_pwd'] = '';
		$tmp['sender_address'] = '';
		$tmp['is_enable'] = 0;

		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];
		
		$this->ci->db->select('smtp_host,smtp_port,smtp_user,smtp_pwd,sender_address,is_enable');
		$this->ci->db->from($applat_db.'.refmailman');
		$this->ci->db->limit(1);
		$query = $this->ci->db->get()->result();

		if (count($query) == 1) {
			$tmp['smtp_host'] = $query[0]->smtp_host;
			$tmp['smtp_port'] = $query[0]->smtp_port;
			$tmp['smtp_user'] = $query[0]->smtp_user;
			$tmp['smtp_pwd'] = $query[0]->smtp_pwd;
			$tmp['sender_address'] = $query[0]->sender_address;
			$tmp['is_enable'] = $query[0]->is_enable;
		}
		return $tmp;
	}

	function lock_data_editing($data_menu,$data_id) {
		$tmp = FALSE;
		
		$app_init = $this->ci->app_init->app_init();
		$applat_db = $app_init['applat_db_name'];

		$data_id = $this->ci->dp_eform->get_data_id_from_hash_link($data_id,$data_menu['full_table_name']);
		
		$this->ci->db->select('Id,status');
		$this->ci->db->from($applat_db.'.trnlogworkflow');
		$this->ci->db->where('menu_id',$data_menu['id']);
		$this->ci->db->where('data_id',$data_id);
		$this->ci->db->where('createby is not null',NULL);
		$this->ci->db->order_by('createdate','DESC');
		$this->ci->db->limit(1);
		
		$query = $this->ci->db->get()->result();

		if (count($query) == 1) {
			$status = $query[0]->status;
			if ($status == 'Submit' || $status == 'Approve' || $status == 'Reject' || $status == 'Cancel') {
				$tmp = TRUE;
			}
		}

		#customized fot MUNDIPHARMA, ePMAP Project 2020, enable editing for approved with changes request
		if ($data_menu['is_workflowdata'] == 1 && $data_menu['full_table_name'] == 'epmap_req_material_data') {
			if ($tmp) {
				$this->ci->db->select('Id');
				$this->ci->db->from('epmap_req_material_reviewer_approver_file');
				$this->ci->db->where('epmap_req_material_data_id',$data_id);
				$this->ci->db->where('is_approved_with_changes',1);
				
				$query = $this->ci->db->get()->result();

				if ($query) {
					$this->ci->db->select('Id');
					$this->ci->db->from($data_menu['full_table_name']);
					$this->ci->db->where('Id',$data_id);
					$this->ci->db->where('status in ("Revise","Draft")',NULL);
					
					$query = $this->ci->db->get()->result();

					if ($query) {
						$tmp = FALSE;
					}
				}
			}
		}
		#[END OF] customized fot MUNDIPHARMA, ePMAP Project 2020, enable editing for approved with changes request
		
		return $tmp;
	}
}
