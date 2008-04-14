<?php
/*
 * ThemePage type
 */
 
class WidgetPage extends Page {
	
	static $db = array(
		'WidgetVersion' => 'Text',
		'WidgetFolderName' => 'Varchar',
		'WidgetFolderNameSet' => 'Int',
	);

	static $has_one = array(
		'WidgetAuthor' => 'Member',
		'WidgetFile' => 'File',
		'WidgetScreenBack' => 'Image',
		'WidgetScreenFront' => 'Image',
	);

	static $default_parent = array('WidgetPageHolder');

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Content.Images", new ImageField("WidgetScreenFront", "Frontend Screenshot"));
		$fields->addFieldToTab("Root.Content.Images", new ImageField("WidgetScreenBack", "Backend Screenshot"));
		$fields->addFieldToTab("Root.Content.Main", new TypeDropdown('WidgetAuthorID', 'Author', 'Member'));
		$fields->addFieldToTab("Root.Content.Main", new TextField("WidgetVersion", "Version No"), "Content");
		$fields->addFieldToTab("Root.Content.Main", new FileIFrameField('WidgetFile', 'WidgetFile'));
		return $fields;
	}
	
	/**
	 * Return the name of the widget - the root folder will be "widgets_(name)"
	 */
	function Name() {
		$filename = Director::makeRelative($this->WidgetFile()->URL);
		if(file_exists("../$filename") && (!$this->WidgetFolderName || filemtime("../$filename") > $this->WidgetFolderNameSet)) {

			if(substr($filename,-3) == 'tar') $result = `tar tf ../$filename | head -n 1`;
			else if(substr($filename,-6) == 'tar.gz' || substr($filename,-3) == 'tgz') $result = `tar tzf ../$filename | head -n 1`;
			else break;
			
			$this->WidgetFolderName = str_replace('/','',trim($result));
			$this->WidgetFolderNameSet = filemtime("../$filename");
			$this->write();
		}
		return $this->WidgetFolderName;
	}
	
}

class WidgetPage_Controller extends Page_Controller {
	function init() {
		parent::init();
		Requirements::css("ssopen/css/Widgets.css");
	}	
	function AuthorLink() {
		return 'ForumMemberProfile/show/' . $this->WidgetAuthorID;
	}
	
}

?>
