<?php
# $Id$
# http://www.mapbender.org/index.php/class_cat_record
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
require_once(__DIR__."/class_user.php");
require_once(__DIR__."/class_administration.php");

/**
 * CSW main class to hold catalog object
 * @author nazgul
 *
 */
class cswrecord{
	
	public $getrecords_status;
	public $getrecords_exception;
	public $getrecords_exception_text;
	public $elementSet;
	public $numberOfRecordsMatched;
	
	//Store GetRecords response XML for future caching needs
	public $getRecordsDoc;
	
	//Array of cswSummaryRecord Objects
	public $SummaryRecordsArray = [];
	
	//Constructor
	function __construct(){
		
	}
        
        /**
        * Old constructor to keep PHP downward compatibility
        */
        function cswrecord(){
		self::__construct();
	}
	
	// Public Methods
	
	/**
	 * Create cswrecord object from GetRecords XML response
	 * @return unknown_type
	 * @param $url URL of getrecords
	 * @param $xml Post, SOAP XML
	 * @todo handle XML for POST,SOAP
	 */
	public function createCSWRecordFromXML($url,$xml=null)
	{
		//create connector
		$data=null;
		//@todo handle post,soap
		if($xml != null){
			$connection = new connector();
        	$connection->set("httpType", "post");
        	$connection->set("httpContentType", "text/xml");
        	$connection->set("httpPostData", $xml);
        	$data = $connection->load($url);
        	//$e = new mb_exception("class_cswrecord:url:".$url);
	        $e = new mb_exception("class_cswrecord:xml:".$xml);
	        //$e = new mb_exception("class_cswrecord:data:".$data);
	        $e = new mb_exception("class_cswrecord.php: responded data: ".$data);
		}
		else{
			$x = new connector($url);
			//$e = new mb_exception("class_cswrecord.php: requested url: ".$url);
			$data = $x->file;
			$e = new mb_exception("class_cswrecord.php: responded data: ".$data);
		}
        
		
		
		if(!$data){
			$this->getrecords_status=false;
			$e = new mb_exception("CAT getrecords returned no result: " . $url . "\n" . $postData);
			return false;
		}
		else {
			$this->getrecords_status=true;
		}
		//check if returned string has an exeption defined
		$testException = strpos((string) $data, "ows:Exception");
		if ($testException === false) {
			
		}
		else {
			$this->getrecords_status=true;
			$this->getrecords_exception=true;
			$this->getrecords_exception_text = urlencode((string) $data);
			$e = new mb_exception("CAT getrecords returned an ows:exception!");
			return false;
		}
		
		//arrays to hold xml struct values and index
		$value_array = null;
		$index_array = null;
		
		//operational vars
		$op_type=null; //get-capabilities, getrecords ...
		$op_sub_type=null; //get,post,....
		$op_constraint=false;
		
		//Store XML response
		//@todo cache this
		$this->getRecordsDoc = $data;
		
		$parser = xml_parser_create("");
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
		xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,CHARSET);
		xml_parse_into_struct($parser,(string) $data,$value_array,$index_array);
		
		//echo "values:".print_r($value_array);
		//echo "index:".print_r($vindex_array);
		
		$code = xml_get_error_code($parser);
		if ($code) {
			$line = xml_get_current_line_number($parser); 
			$mb_exception = new mb_exception(xml_error_string($code) .  " in line " . $line);
		}
		
		xml_parser_free($parser);
		
		foreach($value_array as $element){
			//Version 2.0.2
			//@todo: handle other profiles
			
			if((mb_strtoupper((string) $element['tag']) == "CSW:SEARCHRESULTS" OR mb_strtoupper((string) $element['tag']) == "SEARCHRESULTS") && $element['type'] == "open"){
				$this->elementSet = $element['attributes']["ELEMENTSET"];
				$this->numberOfRecordsMatched = $element['attributes']["NUMBEROFRECORDSMATCHED"];
			}
			
			if((mb_strtoupper((string) $element['tag']) == "CSW:SUMMARYRECORD" OR mb_strtoupper((string) $element['tag']) == "SUMMARYRECORD") && $element['type'] == "open"){
				//Create SummaryRecords Object
				$summaryObj = new cswSummaryRecord();
			}
			
			//SummaryRecord elements
			
			//ID
			if((mb_strtoupper((string) $element['tag']) == "DC:IDENTIFIER" OR mb_strtoupper((string) $element['tag']) == "IDENTIFIER")){
				$summaryObj->identifier = $element['value'];
			}
			//Title
			if((mb_strtoupper((string) $element['tag']) == "DC:TITLE" OR mb_strtoupper((string) $element['tag']) == "TITLE")){
				$summaryObj->title = $element['value'];
			}
			
			//@todo handle multiple subject elements
			//Subject
			if((mb_strtoupper((string) $element['tag']) == "DC:SUBJECT" OR mb_strtoupper((string) $element['tag']) == "SUBJECT")){
				$summaryObj->subject = $element['value'];
			}
			
			//Abstract
			if((mb_strtoupper((string) $element['tag']) == "DC:ABSTRACT" OR mb_strtoupper((string) $element['tag']) == "ABSTRACT" OR mb_strtoupper((string) $element['tag']) == "DCT:ABSTRACT")){
				$summaryObj->abstract = $element['value'];
			}
			
			//Modified
			if((mb_strtoupper((string) $element['tag']) == "DC:MODIFIED" OR mb_strtoupper((string) $element['tag']) == "MODIFIED")){
				$summaryObj->modified = $element['value'];
			}
			
			//Type
			if((mb_strtoupper((string) $element['tag']) == "DC:TYPE" OR mb_strtoupper((string) $element['tag']) == "TYPE")){
				$summaryObj->type = $element['value'];
			}
			
			//Format
			if((mb_strtoupper((string) $element['tag']) == "DC:FORMAT" OR mb_strtoupper((string) $element['tag']) == "FORMAT")){
				$summaryObj->format = $element['value'];
			}
			
			if((mb_strtoupper((string) $element['tag']) == "CSW:SUMMARYRECORD" OR mb_strtoupper((string) $element['tag']) == "SUMMARYRECORD") && $element['type'] == "close"){
				//{ush SummaryRecords Object to Array
				array_push($this->SummaryRecordsArray,$summaryObj);
			}
		}
		
		//Success/Failure
		if($this->numberOfRecordsMatched==0){
			$this->getrecords_status=false;
			$e = new mb_exception("There are no records that match your criteria");
			return false;
		}
		else{
			$this->getrecords_status = true;
			$e = new mb_notice("GetRecords Results Returned");
			return true;
		}	
		
	}
			
	/**
	 * Function to handle whitespace and carriage returns
	 * Inspired by WMS code
	 * @param $string
	 * @return unknown_type
	 */
	function stripEndlineAndCarriageReturn($string) {
	  	return preg_replace("/\n/", "", preg_replace("/\r/", " ", (string) $string));
	}
	
}

/**
 * cswSummaryRecord to hold SummaryRecord Objects
 * GetRecord(1)-SummaryRecord(n) 
 * @author nazgul
 *
 */
class cswSummaryRecord{
	
	//Vars
	public $identifier;
	public $title;
	public $subject;
	public $abstract;
	public $modified;
	public $type;
	public $format;
	
	//Constructor
	function __construct(){
		
	}
        
        /**
        * Old constructor to keep PHP downward compatibility
        */
        function cswSummaryRecord(){
		self::__construct();
	}

	
	//Getters
	
	public function getIdentifier(){
		return $this->identifier;
	}
	
	public function getTitle(){
		return $this->title;
	}
	
	public function getSubject(){
		return $this->subject;
	}
	
	//return abstract
	public function getAbstract(){
		$this->abstract = substr((string) $this->abstract,0,150)."...";
		return $this->abstract;
		
	}
	
	public function getModified(){
		return $this->modified;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function getFormat(){
		return $this->format;
	}
	
	//Setters
	
	public function setIdentifier($identifier){
		return $this->identifier = $identifier;
	}
	
	public function setTitle($title){
		return $this->title = $title;
	}
	
	public function setSubject($subject){
		return $this->subject = $subject;
	}
	
	public function setAbstract($abstract){
		return $this->abstract = $abstract;
	}
	
	public function setModified($modified){
		return $this->modified = $modified;
	}
	
	public function setType($type){
		return $this->type = $type;
	}
	
	public function setFormat($format){
		return $this->format = $format;
	}
	
}



?>