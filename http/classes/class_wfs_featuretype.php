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
require_once(__DIR__."/class_connector.php");
require_once(__DIR__."/class_administration.php");
require_once(__DIR__."/class_wfs.php");

class WfsFeatureType implements \Stringable {
	public $id;
	public $name;
	public $title;
	public $summary;
	public $searchable;
	public $inspire_download;
	public $schema; //text
	public $schema_problem; //boolean
	public $srs; // Tag DefaultSRS in wfs 1.1.0+
	public $latLonBboxArray = [];
	public $crsArray = []; //new for wfs 1.1.0+ (tag OtherSRS)
	public $metadataUrlArray = [];
	public $namespaceArray = [];
	public $elementArray = [];
	public $featuretype_keyword = [];
	public $featuretype_md_topic_category_id = [];
	public $featuretype_inspire_category_id = [];
	public $featuretype_custom_category_id = [];
	public $featuretypeOutputFormatArray = [];

	public function __construct(public $wfs)
 {
 }

	public function hasNamespace ($key, $value) {
		for ($i = 0; $i < count($this->namespaceArray); $i++) {
			if ($this->namespaceArray[$i]->name == $key && 
				$this->namespaceArray[$i]->value == $value) 
			{
				return true;
			}
		}
		return false;
	}

	public function getNamespace ($key) {
		for ($i = 0; $i < count($this->namespaceArray); $i++) {
			if ($this->namespaceArray[$i]->name == $key) {
				return $this->namespaceArray[$i]->value;
			}
		}
		return null;
	}

	public function addNamespace ($key, $value) {
		if ($this->hasNamespace($key, $value)) {
			return $this;
		}
		
		$newNamespace = new stdClass();
		$newNamespace->name = $key;
		$newNamespace->value = $value;

		array_push($this->namespaceArray, $newNamespace);

		return $this;
	}

	public function addCrs ($crs) {
		array_push($this->crsArray, $crs);
		return $this;
	}

	public function addMetadataUrl ($metadataUrl) {
		array_push($this->metadataUrlArray, $metadataUrl);
		return $this;
	}

	public function addOutputFormat ($outputFormat) {
		array_push($this->featuretypeOutputFormatArray, $outputFormat);
		return $this;
	}

	public function addElement ($name, $type) {
		$newElement = new stdClass();

		if (func_num_args() == 3) {
			$newElement->id = func_get_arg(2);
		}
		else {
			$newElement->id = null;
		}

		$newElement->name = $name;
		$newElement->type = $type;

		array_push($this->elementArray, $newElement);

		return $this;
	}

	public function toHtml () {
		
		$wfsString .= "<hr>";
		$wfsString .= "name: ". $this->name . "<br>";
		$wfsString .= "title: ". $this->title . "<br>";
		$wfsString .= "abstract: ". $this->summary . "<br>";
		$wfsString .= "srs: ". $this->srs . "<br>";

		for ($j = 0; $j < count($this->elementArray); $j++) {
			$currentElement = $this->elementArray[$j];
			$wfsString .= " element: " . $currentElement->name . 
							" - " . $currentElement->type . "<br>";
		}

		for ($j = 0; $j < count($this->namespaceArray); $j++) {
			$currentNamespace = $this->namespaceArray[$j];
			$wfsString .= " namespace: " . $currentNamespace->name . 
							" - " . $currentNamespace->value . "<br>";
		}
		return $wfsString;
	}

	public function __toString (): string {
		return (string) $this->toHtml();
	}
}
?>
