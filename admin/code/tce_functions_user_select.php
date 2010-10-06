<?php
//============================================================+
// File name   : tce_functions_user_select.php
// Begin       : 2001-09-13
// Last Update : 2010-10-06
//
// Description : Functions to display and select registered user.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com S.r.l.
//               Via della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//
// License:
//    Copyright (C) 2004-2010  Nicola Asuni - Tecnick.com S.r.l.
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as
//    published by the Free Software Foundation, either version 3 of the
//    License, or (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//    Additionally, you can't remove, move or hide the original TCExam logo,
//    copyrights statements and links to Tecnick.com and TCExam websites.
//
//    See LICENSE.TXT file for more information.
//============================================================+

/**
 * Functions to display and select registered user.
 * @package com.tecnick.tcexam.admin
 * @author Nicola Asuni
 * @copyright Copyright © 2004-2010, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @link www.tecnick.com
 * @since 2001-09-13
 */

/**
 * Display user selection for using F_show_select_user function.
 * @author Nicola Asuni
 * @copyright Copyright © 2004-2010, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @link www.tecnick.com
 * @since 2001-09-13
 * @param string $order_field order by column name
 * @param string $orderdir oreder direction
 * @param string $firstrow number of first row to display
 * @param string $rowsperpage number of rows per page
 * @param int $group_id id of the group (default = 0 = no specific group selected)
 * @param string $andwhere additional SQL WHERE query conditions
 * @param string $searchterms search terms
 * @return true
 * @uses F_show_select_user
 */
function F_select_user($order_field, $orderdir, $firstrow, $rowsperpage, $group_id=0, $andwhere='', $searchterms='') {
	global $l;
	require_once('../config/tce_config.php');
	F_show_select_user($order_field, $orderdir, $firstrow, $rowsperpage, $group_id, $andwhere, $searchterms);
	return true;
}

/**
 * Display user selection XHTML table.
 * @author Nicola Asuni
 * @copyright Copyright © 2004-2010, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @link www.tecnick.com
 * @since 2001-09-13
 * @param string $order_field order by column name
 * @param int $orderdir oreder direction
 * @param int $firstrow number of first row to display
 * @param int $rowsperpage number of rows per page
 * @param int $group_id id of the group (default = 0 = no specific group selected)
 * @param string $andwhere additional SQL WHERE query conditions
 * @param string $searchterms search terms
 * @return false in case of empty database, true otherwise
 */
function F_show_select_user($order_field, $orderdir, $firstrow, $rowsperpage, $group_id=0, $andwhere='', $searchterms='') {
	global $l, $db;
	require_once('../config/tce_config.php');
	require_once('../../shared/code/tce_functions_page.php');
	require_once('../../shared/code/tce_functions_form.php');
	if ($l['a_meta_dir'] == 'rtl') {
		$txtalign = 'right';
		$numalign = 'left';
	} else {
		$txtalign = 'left';
		$numalign = 'right';
	}
	$order_field = F_escape_sql($order_field);
	$orderdir = intval($orderdir);
	if ($orderdir == 0) {
		$nextorderdir=1;
		$full_order_field = $order_field;
	} else {
		$nextorderdir=0;
		$full_order_field = $order_field.' DESC';
	}
	if (!F_count_rows(K_TABLE_USERS)) { // if the table is void (no items) display message
		F_print_error('MESSAGE', $l['m_databasempty']);
		return FALSE;
	}
	$wherequery = '';
	if ($group_id > 0) {
		$wherequery = ', '.K_TABLE_USERGROUP.' WHERE user_id=usrgrp_user_id	AND usrgrp_group_id='.intval($group_id).'';
	}
	if (empty($wherequery)) {
		$wherequery = ' WHERE';
	} else {
		$wherequery .= ' AND';
	}
	$wherequery .= ' (user_id>1)';
	if ($_SESSION['session_user_level'] < K_AUTH_ADMINISTRATOR) {
		// filter for level
		$wherequery .= ' AND ((user_level<'.$_SESSION['session_user_level'].') OR (user_id='.$_SESSION['session_user_id'].'))';
		// filter for groups
		$wherequery .= ' AND user_id IN (SELECT tb.usrgrp_user_id
			FROM '.K_TABLE_USERGROUP.' AS ta, '.K_TABLE_USERGROUP.' AS tb
			WHERE ta.usrgrp_group_id=tb.usrgrp_group_id
				AND ta.usrgrp_user_id='.intval($_SESSION['session_user_id']).'
				AND tb.usrgrp_user_id=user_id)';
	}
	if (!empty($andwhere)) {
		$wherequery .= ' AND ('.$andwhere.')';
	}
	$sql = 'SELECT * FROM '.K_TABLE_USERS.$wherequery.' ORDER BY '.$full_order_field;
	if (K_DATABASE_TYPE == 'ORACLE') {
		$sql = 'SELECT * FROM ('.$sql.') WHERE rownum BETWEEN '.$firstrow.' AND '.($firstrow + $rowsperpage).'';
	} else {
		$sql .= ' LIMIT '.$rowsperpage.' OFFSET '.$firstrow.'';
	}
	if ($r = F_db_query($sql, $db)) {
		if ($m = F_db_fetch_array($r)) {
			// -- Table structure with links:
			echo '<div class="container">';
			echo '<table class="userselect">'.K_NEWLINE;
			// table header
			echo '<tr>'.K_NEWLINE;
			echo '<th>&nbsp;</th>'.K_NEWLINE;
			$filter = '';
			if (strlen($searchterms) > 0) {
				$filter = '&amp;searchterms='.urlencode($searchterms);
			}
			echo F_user_table_header_element('user_name', $nextorderdir, $l['h_login_name'], $l['w_user'], $order_field, $group_id, $filter);
			echo F_user_table_header_element('user_lastname', $nextorderdir, $l['h_lastname'], $l['w_lastname'], $order_field, $group_id, $filter);
			echo F_user_table_header_element('user_firstname', $nextorderdir, $l['h_firstname'], $l['w_firstname'], $order_field, $group_id, $filter);
			echo F_user_table_header_element('user_regnumber', $nextorderdir, $l['h_regcode'], $l['w_regcode'], $order_field, $group_id, $filter);
			echo F_user_table_header_element('user_level', $nextorderdir, $l['h_level'], $l['w_level'], $order_field, $group_id, $filter);
			echo F_user_table_header_element('user_regdate', $nextorderdir, $l['h_regdate'], $l['w_regdate'], $order_field, $group_id, $filter);
			echo '<th title="'.$l['h_group_name'].'">'.$l['w_groups'].'</th>'.K_NEWLINE;
			echo '<th title="'.$l['t_all_results_user'].'">'.$l['w_tests'].'</th>'.K_NEWLINE;
			echo '</tr>'.K_NEWLINE;
			$itemcount = 0;
			do {
				$itemcount++;
				echo '<tr>'.K_NEWLINE;
				echo '<td>';
				echo '<input type="checkbox" name="userid'.$itemcount.'" id="userid'.$itemcount.'" value="'.$m['user_id'].'" title="'.$l['w_select'].'"';
				if (isset($_REQUEST['checkall']) AND ($_REQUEST['checkall'] == 1)) {
					echo ' checked="checked"';
				}
				echo ' />';
				echo '</td>'.K_NEWLINE;
				echo '<td style="text-align:'.$txtalign.';">&nbsp;<a href="tce_edit_user.php?user_id='.$m['user_id'].'" title="'.$l['w_edit'].'">'.htmlspecialchars($m['user_name'], ENT_NOQUOTES, $l['a_meta_charset']).'</a></td>'.K_NEWLINE;
				echo '<td style="text-align:'.$txtalign.';">&nbsp;'.htmlspecialchars($m['user_lastname'], ENT_NOQUOTES, $l['a_meta_charset']).'</td>'.K_NEWLINE;
				echo '<td style="text-align:'.$txtalign.';">&nbsp;'.htmlspecialchars($m['user_firstname'], ENT_NOQUOTES, $l['a_meta_charset']).'</td>'.K_NEWLINE;
				echo '<td style="text-align:'.$txtalign.';">&nbsp;'.htmlspecialchars($m['user_regnumber'], ENT_NOQUOTES, $l['a_meta_charset']).'</td>'.K_NEWLINE;
				echo '<td>&nbsp;'.$m['user_level'].'</td>'.K_NEWLINE;
				echo '<td>&nbsp;'.htmlspecialchars($m['user_regdate'], ENT_NOQUOTES, $l['a_meta_charset']).'</td>'.K_NEWLINE;
				// comma separated list of user's groups
				$grp = '';
				$sqlg = 'SELECT *
					FROM '.K_TABLE_GROUPS.', '.K_TABLE_USERGROUP.'
					WHERE usrgrp_group_id=group_id
						AND usrgrp_user_id='.$m['user_id'].'
					ORDER BY group_name';
				if ($rg = F_db_query($sqlg, $db)) {
					while ($mg = F_db_fetch_array($rg)) {
						$grp .= $mg['group_name'].', ';
					}
				} else {
					F_display_db_error();
				}
				echo '<td style="text-align:'.$txtalign.';">&nbsp;'.htmlspecialchars(substr($grp,0,-2), ENT_NOQUOTES, $l['a_meta_charset']).'</td>'.K_NEWLINE;

				echo '<td><a href="tce_show_allresults_users.php?user_id='.$m['user_id'].'" class="xmlbutton" title="'.$l['t_all_results_user'].'">...</a></td>'.K_NEWLINE;

				echo '</tr>'.K_NEWLINE;
			} while ($m = F_db_fetch_array($r));

			echo '</table>'.K_NEWLINE;

			echo '<br />'.K_NEWLINE;

			echo '<input type="hidden" name="order_field" id="order_field" value="'.$order_field.'" />'.K_NEWLINE;
			echo '<input type="hidden" name="orderdir" id="orderdir" value="'.$orderdir.'" />'.K_NEWLINE;
			echo '<input type="hidden" name="firstrow" id="firstrow" value="'.$firstrow.'" />'.K_NEWLINE;
			echo '<input type="hidden" name="rowsperpage" id="rowsperpage" value="'.$rowsperpage.'" />'.K_NEWLINE;

			// check/uncheck all options
			echo '<span dir="ltr">';
			echo '<input type="radio" name="checkall" id="checkall1" value="1" onclick="document.getElementById(\'form_userselect\').submit()" />';
			echo '<label for="checkall1">'.$l['w_check_all'].'</label> ';
			echo '<input type="radio" name="checkall" id="checkall0" value="0" onclick="document.getElementById(\'form_userselect\').submit()" />';
			echo '<label for="checkall0">'.$l['w_uncheck_all'].'</label>';
			echo '</span>'.K_NEWLINE;
			echo '<br />'.K_NEWLINE;
			echo '<strong style="margin:5px">'.$l['m_with_selected'].'</strong>'.K_NEWLINE;
			echo '<ul style="margin:0">';
			if ($_SESSION['session_user_level'] >= K_AUTH_DELETE_USERS) {
				// delete user
				echo '<li>';
				F_submit_button('delete', $l['w_delete'], $l['h_delete']);
				echo '</li>'.K_NEWLINE;
			}
			if ($_SESSION['session_user_level'] >= K_AUTH_ADMIN_GROUPS) {
				echo '<li>';
				// add/delete group
				echo F_user_group_select('new_group_id');
				F_submit_button('addgroup', $l['w_add'], $l['w_add']);
				if ($_SESSION['session_user_level'] >= K_AUTH_DELETE_GROUPS) {
					F_submit_button('delgroup', $l['w_delete'], $l['h_delete']);
				}
				echo '</li>'.K_NEWLINE;
				if ($_SESSION['session_user_level'] >= K_AUTH_MOVE_GROUPS) {
					// move group
					echo '<li>';
					if ($l['a_meta_dir'] == 'rtl') {
						$arr = '&larr;';
					} else {
						$arr = '&rarr;';
					}
					echo F_user_group_select('from_group_id');
					echo $arr;
					echo F_user_group_select('to_group_id');
					F_submit_button('move', $l['w_move'], $l['w_move']);
					echo '</li>'.K_NEWLINE;
				}
			}
			echo '</ul>'.K_NEWLINE;
			echo '<div class="row"><hr /></div>'.K_NEWLINE;

			// ---------------------------------------------------------------
			// -- page jumper (menu for successive pages)
			$sql = 'SELECT count(*) AS total FROM '.K_TABLE_USERS.''.$wherequery.'';
			if (!empty($order_field)) {$param_array = '&amp;order_field='.urlencode($order_field).'';}
			if (!empty($orderdir)) {$param_array .= '&amp;orderdir='.$orderdir.'';}
			if (!empty($group_id)) {$param_array .= '&amp;group_id='.intval($group_id).'';}
			if (!empty($searchterms)) {$param_array .= '&amp;searchterms='.urlencode($searchterms).'';}
			$param_array .= '&amp;submitted=1';
			F_show_page_navigator($_SERVER['SCRIPT_NAME'], $sql, $firstrow, $rowsperpage, $param_array);

			echo '<div class="row">'.K_NEWLINE;
			echo '<br />';
			echo '<a href="tce_xml_users.php" class="xmlbutton" title="'.$l['h_xml_export'].'">XML</a> ';
			echo '<a href="tce_csv_users.php" class="xmlbutton" title="'.$l['h_csv_export'].'">CSV</a>';
			echo '</div>'.K_NEWLINE;

			echo '<div class="pagehelp">'.$l['hp_select_users'].'</div>'.K_NEWLINE;
			echo '</div>'.K_NEWLINE;
		} else {
			F_print_error('MESSAGE', $l['m_search_void']);
		}
	} else {
		F_display_db_error();
	}
	return TRUE;
}

/**
 * Display table header element with order link.
 * @param string $order_field name of table field
 * @param string $orderdir order direction
 * @param string $title title field of anchor link
 * @param string $name column name
 * @param string $current_order_field current order field name
 * @param int $group_id id of the group (default = 0 = no specific group selected)
 * @param string $filter additional parameters to pass on URL
 * @return table header element string
 */
function F_user_table_header_element($order_field, $orderdir, $title, $name, $current_order_field='', $group_id=0, $filter='') {
	global $l;
	require_once('../config/tce_config.php');
	$ord = '';
	if ($order_field == $current_order_field) {
		if ($orderdir) {
			$ord = '<acronym title="'.$l['w_ascent'].'">&gt;</acronym>';
		} else {
			$ord = '<acronym title="'.$l['w_descent'].'">&lt;</acronym>';
		}
	}
	$str = '<th><a href="'.$_SERVER['SCRIPT_NAME'].'?group_id='.intval($group_id).'&amp;firstrow=0&amp;order_field='.$order_field.'&amp;orderdir='.$orderdir.''.$filter.'" title="'.$title.'">'.$name.'</a> '.$ord.'</th>'.K_NEWLINE;
	return $str;
}

/**
 * Return true if the selected test is active for the selected group
 * @param int $test_id test ID
 * @param int $group_id group ID
 * @return boolean true/false
 * @since 11.1.003 (2010-10-05)
 */
function F_isTestOnGroup($test_id, $group_id) {
	global $l, $db;
	require_once('../config/tce_config.php');
	$sql = 'SELECT tstgrp_test_id FROM '.K_TABLE_TEST_GROUPS.' WHERE tstgrp_test_id='.intval($test_id).' AND tstgrp_group_id='.intval($group_id).' LIMIT 1';
	if ($r = F_db_query($sql, $db)) {
		if ($m = F_db_fetch_array($r)) {
			return true;
		}
	}
	return false;
}

/**
 * Return true if the selected user belongs to the selected group
 * @param int $user_id user ID
 * @param int $group_id group ID
 * @return boolean true/false
 * @since 11.1.003 (2010-10-05)
 */
function F_isUserOnGroup($user_id, $group_id) {
	global $l, $db;
	require_once('../config/tce_config.php');
	$sql = 'SELECT usrgrp_user_id FROM '.K_TABLE_USERGROUP.' WHERE usrgrp_user_id='.intval($user_id).' AND usrgrp_group_id='.intval($group_id).' LIMIT 1';
	if ($r = F_db_query($sql, $db)) {
		if ($m = F_db_fetch_array($r)) {
			return true;
		}
	}
	return false;
}

/**
 * Return true if the current user is an administrator or belongs to the group, false otherwise
 * @param int $group_id group ID
 * @return boolean true/false
 * @since 11.1.003 (2010-10-05)
 */
function F_isAuthorizedEditorForGroup($group_id) {
	global $l, $db;
	require_once('../config/tce_config.php');
	if (($_SESSION['session_user_level'] >= K_AUTH_ADMINISTRATOR) OR empty($group_id)) {
		// user is an administrator (belongs to all groups) or empty group
		return true;
	}
	return F_isUserOnGroup($_SESSION['session_user_id'], $group_id);
}

/**
 * Return true if the current user is authorized to edit the specified user
 * @param int $user_id user ID
 * @return boolean true/false
 * @since 11.1.003 (2010-10-05)
 */
function F_isAuthorizedEditorForUser($user_id) {
	global $l, $db;
	require_once('../config/tce_config.php');
	if (($_SESSION['session_user_level'] >= K_AUTH_ADMINISTRATOR) OR empty($user_id)) {
		// user is an administrator or empty user
		return true;
	} else {
		// non-administrator can access only to users with lower level
		$sql = 'SELECT user_id,user_level FROM '.K_TABLE_USERS.' WHERE user_id='.intval($user_id).' LIMIT 1';
		if ($r = F_db_query($sql, $db)) {
			if ($m = F_db_fetch_array($r)) {
				if (intval($_SESSION['session_user_id']) == $m['user_id']) {
					// user can edit his/her own profile
					return true;
				}
				if (intval($_SESSION['session_user_level']) > $m['user_level']) {
					// non-administrator access only to users on the same group
					$sqlg = 'SELECT tb.usrgrp_user_id
						FROM '.K_TABLE_USERGROUP.' AS ta, '.K_TABLE_USERGROUP.' AS tb
						WHERE ta.usrgrp_group_id=tb.usrgrp_group_id
							AND ta.usrgrp_user_id='.intval($_SESSION['session_user_id']).'
							AND tb.usrgrp_user_id='.intval($user_id).'
						LIMIT 1';
					if ($rg = F_db_query($sqlg, $db)) {
						if ($mg = F_db_fetch_array($rg)) {
							return true;
						}
					}
				}
			}
		}
	}
	return false;
}

/**
 * Return the SQL selection query for user groups
 * @param string $where filters to add on WHERE clause
 * @return sql selection string
 * @since 11.1.003 (2010-10-05)
 */
function F_user_group_select_sql($where='') {
	global $l, $db;
	require_once('../config/tce_config.php');
	if ($_SESSION['session_user_level'] >= K_AUTH_ADMINISTRATOR) {
		// administrator access to all groups
		$sql = 'SELECT * FROM '.K_TABLE_GROUPS.'';
		if ($where !== '') {
			$sql .= ' WHERE '.$where;
		}
	} else {
		// non-administrator can access only to his/her groups
		$sql = 'SELECT group_id,group_name FROM '.K_TABLE_GROUPS.', '.K_TABLE_USERGROUP.'';
		$sql .= ' WHERE group_id=usrgrp_group_id AND usrgrp_user_id='.$_SESSION['session_user_id'].'';
		if ($where !== '') {
			$sql .= ' AND '.$where;
		}
	}
	$sql .= ' ORDER BY group_name';
	return $sql;
}

/**
 * Display select box for user groups
 * @param string $name name of the select field
 * @return table header element string
 */
function F_user_group_select($name='group_id') {
	global $l, $db;
	require_once('../config/tce_config.php');
	$str = '';
	$str .= '<select name="'.$name.'" id="'.$name.'" size="0" title="'.$l['w_group'].'">'.K_NEWLINE;
	$sql = F_user_group_select_sql();
	if ($r = F_db_query($sql, $db)) {
		$str .= '<option value="0" style="color:gray" selected="selected">'.$l['w_group'].'</option>'.K_NEWLINE;
		while ($m = F_db_fetch_array($r)) {
			$str .= '<option value="'.$m['group_id'].'">';
			$str .= ' '.htmlspecialchars($m['group_name'], ENT_NOQUOTES, $l['a_meta_charset']).'&nbsp;</option>'.K_NEWLINE;
		}
	} else {
		$str .= '</select>'.K_NEWLINE;
		F_display_db_error();
	}
	$str .= '</select>'.K_NEWLINE;
	return $str;
}

/**
 * Returns an array containing groups IDs to which the specified user belongs
 * @param int $user_id user ID
 * @return array containing user's groups IDs
 */
function F_get_user_groups($user_id) {
	global $l, $db;
	require_once('../config/tce_config.php');
	$user_id = intval($user_id);
	$groups = array();
	$sql = 'SELECT usrgrp_group_id
		FROM '.K_TABLE_USERGROUP.'
		WHERE usrgrp_user_id='.$user_id.'';
	if ($r = F_db_query($sql, $db)) {
		while ($m = F_db_fetch_array($r)) {
			$groups[] = $m['usrgrp_group_id'];
		}
	} else {
		F_display_db_error();
	}
	return $groups;
}

//============================================================+
// END OF FILE
//============================================================+
