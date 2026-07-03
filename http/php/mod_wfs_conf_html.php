<?php
function myNl2br ($str) {
	return preg_replace('#\r?\n#', '\\n', (string) $str);
}

$textAreaCode = <<<CODE
<textarea cols='' rows='' id=''>
</textarea>
CODE;

$datepickerCode = <<<CODE
<input type='text' id='' class='hasdatepicker' />
CODE;

$selectCode = <<<CODE
<select id=''>
 <option>Auswahl...</option>
 <option value=''></option>
 <option value=''></option>
 <option value=''></option>
</select>
CODE;

$selectWfsCode = <<<CODE
{
  "wfs_id": 1,
  "featuretype_id": 2,
  "element_ids": [1, 3],
  "select_id": "test123",
  "option_value_element_ids_indexes": [0],
  "option_text_element_ids_indexes": [0, 1],
  "option_empty": false,
  "option_text_mask": "{element_0} ({element_1})" 
}
CODE;

$checkboxCode = <<<CODE
<input type='checkbox' id='' value='1'>
CODE;

$templateOptionArray = ["none" => ["label" =>"--", "code" => ""], "datepicker" => ["label" => "Datepicker", "code" => $datepickerCode], "textarea" => ["label" => "Textarea", "code" => $textAreaCode], "select" => ["label" =>"Select", "code" => $selectCode], "selectWfs" => ["label" =>"SelectWfs", "code" => $selectWfsCode], "checkbox" => ["label" => "Checkbox", "code" => $checkboxCode]];
?>