<?php

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
# $Id: dyn_js.php 3850 2009-04-03 09:02:12Z christoph $
# $Header: /cvsroot/mapbender/mapbender/http/classes/class_wfs.php,v 1.15 2006/03/09 13:55:46 uli_rothstein Exp $
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(__DIR__."/../classes/class_mb_exception.php");

if(isset($gui_id))
{
	$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = $1 AND fkey_gui_id = $2 and var_type='var'";
	$v = [$e_id, $gui_id];
	$t = ['s', 's'];
   	$res = db_prep_query($sql,$v,$t);
	$arrays = [];
	echo "\n";
	while($row = db_fetch_array($res))
	{
		if (mb_strpos((string) $row["var_name"], "[")) {
			$arrayname = mb_substr((string) $row["var_name"], 0, mb_strpos((string) $row["var_name"], "["));
			
			if (!in_array($arrayname, $arrays)) {
				$i++;
				$arrays[$i] = $arrayname;
				echo "var " . $arrayname  . " = new Array();\n";
			}
		}
		else {
			echo "var ";
		}
		if (is_numeric(stripslashes((string) $row["var_value"]))) {
			echo $row["var_name"]." = ".stripslashes((string) $row["var_value"]).";\n";
		}
		elseif (str_starts_with(stripslashes((string) $row["var_value"]), "[") || str_starts_with(stripslashes((string) $row["var_value"]), "{")) {
			echo $row["var_name"]." = ".stripslashes((string) $row["var_value"]).";\n";
		}
		else {
			echo $row["var_name"]." = '".str_replace(['"', "'", "\r", "\n", "\0"], ['\"', '\\\'', '\r', '\n', '\0'], stripslashes((string) $row["var_value"]))."';\n";
		}
	}
}
else {
	$e = new mb_exception("Application ID not set while retrieving element vars of module " . $e_id);
}
?>
