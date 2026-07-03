<?php
# $Id: class_wfs.php 3094 2008-10-01 13:52:35Z christoph $
# http://www.mapbender.org/index.php/class_wfs.php
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

require_once(__DIR__."/../../core/globalSettings.php");

/**
 * An abstract class modelling an OGC web service (OWS), for example
 * Web Map Service (WMS) or Web Feature Service (WFS).
 */
abstract class Ows {
	public $id;
	public $name;
	public $title;
	public $alternate_title;
	public $summary;
	public $uploadUrl;
	public $getCapabilities;
	public $getCapabilitiesDoc;
	public $fees;
	public $accessconstraints;
	public $individualName;
	public $positionName;
	public $providerName;
	public $city;
	public $deliveryPoint;
	public $administrativeArea;
	public $postalCode;
	public $voice;
	public $facsimile;
	public $electronicMailAddress;
	public $country;
	public $termsofuse;
	public $auth = false; //array 'auth_type', 'auth_username', 'auth_password', default false
	
	/**
	 * Removes the namespace from a tag name.
	 * 
	 * Example: input is "topp:the_geom" will return "the_geom".
	 * 
	 * @return String
	 * @param $s String
	 */
	final protected function sepNameSpace($s) {
		$c = strpos((string) $s, ":"); 
		if ($c > 0) {
			return substr((string) $s, $c + 1);
		}
		return $s;
	}
	
	/**
	 * Get namespace from a tag name.
	 * 
	 * Example: input is "topp:the_geom" will return "topp".
	 * 
	 * @return String
	 * @param $s String
	 */
	final public function getNameSpace($s) {
		$c = strpos((string) $s, ":"); 
		if ($c !== false) {
			return substr((string) $s, 0, $c);
		}
		return $s;
	}
	
	/**
	 * Returns the conjunction character of an URL
	 * 
	 * @param String $url
	 * @return String the character "&", "?", or ""
	 */
	final public function getConjunctionCharacter ($url) {
		// does the URL contain "?"
		$pos = strpos($url, "?");

		// if yes, ...
		if ($pos > -1) { 
			// if the last character is "?", return ""
			if (str_ends_with($url, "?")) { 
				return "";
			}
			// if the last character is "&", return ""
			else if (str_ends_with($url, "&")) {
				return "";
			}
			// "?" exists, so the conunction character must be "&"
			return "&";
		}
		// "?" doesn't exist, so the conunction character must be "?"
		return "?";
	}
	
	final protected function get ($url) {
		if (!$this->auth) {
			$connection = new connector($url);
		} else {
			$connection = new connector($url, $this->auth);
		}
		//$e = new mb_notice("OWS REQUEST: " . $url);
		$data = $connection->file;
		if (!$data) {
			$e = new mb_exception("OWS request returned no result: " . $url);
			return null;
		}
		return $data;
	}
	
	final protected function post ($url, $postData) {
		$connection = new connector();
		$connection->set("httpType", "post");
		$connection->set("httpContentType", "text/xml");
		$connection->set("httpPostData", $postData);
		if (!$this->auth) {
			$data = $connection->load($url);
		} else {
			$data = $connection->load($url, $this->auth);
		}
		if (!$data) {
			$e = new mb_exception("OWS request returned no result: " . $url . "\n" . $postData);
			return null;
		}
		return $data;
	}

}
?>
