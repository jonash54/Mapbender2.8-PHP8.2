<?php
# $Id: dyn_css.php 9944 2018-08-10 12:30:18Z armin11 $
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

require_once(__DIR__ . "/../../conf/mapbender.conf");
require_once(__DIR__ . "/../../lib/database-pgsql.php");
$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);

if(!isset($gui_id) && isset($_REQUEST["guiID"])){
	$gui_id = $_REQUEST["guiID"];
}
if(!isset($_REQUEST["e_id_css"]) && isset($_REQUEST["elementID"])){
	$e_id_css = $_REQUEST["elementID"];
}
if(isset($_REQUEST["e_id_css"])){
	$e_id_css = $_REQUEST["e_id_css"];
}
if(isset($gui_id) && $gui_id != ""){
	#echo $gui_id. " - ".$_REQUEST["e_id_css"];
	if (isset($e_id_css) && $e_id_css != '') {
		#
		#
		# css file
		#
		#
		$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = $1 AND fkey_gui_id = $2 and var_type='file/css'";
		$v = [$e_id_css, $gui_id];
		$t = ['s', 's'];
		$res = db_prep_query($sql,$v,$t);
		$cnt = 0;
		while($row = db_fetch_array($res)){		
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$row["var_value"]."\" />\n";   
		}
	
	
	
		#
		#
		# css text
		#
		#
		
	
		$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = $1 AND fkey_gui_id = $2 and var_type='text/css'";
		$v = [$e_id_css, $gui_id];
		$t = ['s', 's'];
		$res = db_prep_query($sql,$v,$t);
		$cnt = 0;
		$style = "";
		while($row = db_fetch_array($res)){
			$style .= $row["var_value"]."\n";
		}
		if ($style) {
			echo "<style type=\"text/css\">\n";
			echo "<!--\n";
			echo $style;
			echo "-->\n";
			echo "</style>\n";
		}
	}
	
	#
	#
	# php variables
	#
	#
	$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = $1 AND fkey_gui_id = $2 and var_type='php_var'";
   	$v = [$e_id_css, $gui_id];
	$t = ['s', 's'];
	$res = db_prep_query($sql,$v,$t);

	echo "\n";
	while($row = db_fetch_array($res))
	{
		${$row["var_name"]} = stripslashes((string) $row["var_value"]);
		echo "\n";
	}
	
	
	
	#
	#
	# JavaScript variables
	#
	#
	$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = $1 AND fkey_gui_id = $2 and var_type='var'";
   	$v = [$e_id_css, $gui_id];
	$t = ['s', 's'];
	$res = db_prep_query($sql,$v,$t);

	echo "\n";
	echo "<script type=\"text/javascript\">\n";
	echo "<!--\n";	
	$arrays = [];
	$i=-1;
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
	echo "// -->\n";
	echo "</script>\n";
}
 ?>
