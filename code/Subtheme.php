<?php

class Subtheme extends DataObject {
	static $db = array(
		'Module' => 'Text',
		'Visible' => 'Boolean'
	);
		
	static $has_one = array(
		'Author' => 'Member',
		'SubthemeFile' => 'File',
		'Parent' => 'ThemePage'
	);
		
	function approve() {
		if(!Permission::check('ADMIN')) {
			Security::permissionFailure($this, "You do not have sufficient access privileges to approve themes.");
			return;
		}
	
		$subtheme = DataObject::get_by_id('Subtheme', Director::urlParam('ID'));
		$subtheme->Visible = true;
		$subtheme->write();
		
		echo 'Subtheme approved';
	}
}

?>