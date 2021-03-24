<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Cl_components {

	function get_bu_title($bu_head_name) {
		$bu_title = '';
		switch ($bu_head_name) {
			case 'Denny Gunardi Michlar':
				$bu_title = 'Vice President BU Critical Care';
				break;
			case 'Ian Martin Wibawa Kloer':
				$bu_title = 'Vice president BU Broad Market & Medical Device';
				break;
			default:
				break;
		}

		return $bu_title;
	}
}