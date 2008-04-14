<?php
/*
 * Widget Holder Page Type
 */
 
class WidgetPageHolder extends Page {
	
	static $db = array(	
	);

	static $has_one = array(
	);

	static $allowed_children = array('WidgetPage');
	
}

class WidgetPageHolder_Controller extends Page_Controller {
	function init() {
		parent::init();
		Requirements::css("ssopen/css/Widgets.css");
	}

	function xml() {
		Controller::curr()->getResponse()->addHeader("Content-type", "text/xml");
		return array();
	}

	function WidgetForm() {
		$fields = new FieldSet(
			new HiddenField('ParentID', 'ParentID', $this->ID),
			new HiddenField('ID', 'ID'),
			new TextField('Title', 'Widget Name'),
			new TextField('WidgetVersion', 'Release Number', '1.0.0'),
			new SimpleImageField('WidgetScreenFront', 'Front End Screenshot'),
			new SimpleImageField('WidgetScreenBack', 'Back End Screenshot'),
			new FileField('WidgetFile', 'Widget (as a .tar.gz)', null, null, null, 'downloads/widgets'),
			new TextareaField('Content', 'Description', 10, 40)
		);
		
		$submitAction = new FormAction('submitwidget', 'Submit');
		$actions = new FieldSet($submitAction);
		$validator = new RequiredFields('Title', 'Content', 'WidgetVersion', 'WidgetScreenFront', 'WidgetScreenBack', 'WidgetFile');
		
		$form = new WidgetPageHolder_Form($this, 'WidgetForm', $fields, $actions, $validator);
		
		if(Director::urlParam('ID')) {
			$widget = DataObject::get_by_id('WidgetPage', Director::urlParam('ID'));
			if($widget && (Permission::check('ADMIN') || $widget->AuthorID == Member::currentMember()->ID)) {
				$form->loadNonBlankDataFrom($widget);
				$validator = new RequiredFields('Title', 'WidgetVersion', 'Content');
				$form->setValidator($validator);
			}
		}
		
		return $form;
	}
	
	function edit() {
		if(!Member::currentMember()) {
			Security::permissionFailure($this, 'Please log in to submit a widget');
		}
		
		return array();
	}
	function Widgets() {
		$start = (!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) ? 0 : $_GET['start'];
		$widgets = DataObject::get('WidgetPage','','`Created` DESC','',"$start,50");	
	
		foreach($widgets as $widget) {
			$author = DataObject::get_by_id('Member',$widget->WidgetAuthorID);
			$widget->AuthorName = $author->Nickname;
 			$widget->AuthorLink = "ForumMemberProfile/show/". $author->ID;
		}
		
		return $widgets;
	}
}
class WidgetPageHolder_Form extends Form {
	
	function submitwidget($data) {
		if($data['ID'] != 0) {
			$widget = DataObject::get_by_id("WidgetPage", $data['ID']);
			$update = true;
		} else {
			// Create a new WidgetPage and save the form into it
			$widget = new WidgetPage();
			$update = false;
		}
		$widgetname = $widget->Title;
		$widgetFileID = $widget->WidgetFileID;
		$frontScreenshotID = $widget->WidgetScreenFrontID;
		$backScreenshotID = $widget->WidgetScreenBackID;
		$this->saveInto($widget);
		
		//Debug::show($widget);
		$widgetname = $widget->WidgetName;
		
		if($widget->WidgetFileID == 0) {
			$widget->WidgetFileID = $widgetFileID;
		}
		if($widget->WidgetScreenFrontID == 0) {
			$widget->WidgetScreenFrontID = $WidgetScreenFrontID;
		}
		if($widget->WidgetScreenBackID == 0) {
			$widget->WidgetScreenBackID = $WidgetScreenBackID;
		}

		$archive = Archive::open($widget->WidgetFile()->Filename);
		
		
		if($archive) {
			$listing = $archive->listing();
			
			$SQL_widgetname = Convert::raw2sql($widgetname);
			
			$nameCheck = DataObject::get_one('WidgetPage', "Title='$SQL_widgetname'");
			if($nameCheck && $nameCheck->ID != $widget->ID) {
				$error = "There already exists a widget with the name '$widgetname'";
			}
		} else {
			$error = "The widget must be a .tar.gz archive.";
		}
		
		if($error) {
			$this->Content = "<p>Your widget could not be submitted:<br />$error</p>";
			$this->Title = "Widget submission error";
			return $this->renderWith('Page');
		}
		$widgetname = $widget->Title;
		$widget->WidgetName = $widget->Title;
		$widget->URLSegment = $widget->Title;
		$widget->WidgetAuthorID = Member::currentUser()->ID;
	
		
		$widget->writeToStage('Stage');

		$ID = $widget->ID;
		$URL = Director::absoluteBaseURL().'admin/show/'.$ID; 
		if(!$update) {
			$email = new Email('widgets@silverstripe.com', 'matt@silverstripe.com', "New widget '$widgetname' has been uploaded", null, null, 'andrew@silverstripe.com', 'will@silverstripe.com');
			$email->setBody("A new widget '$widgetname' has been uploaded by " . Member::currentUser()->Nickname . ". Please go to the Admin Panel on <a href='$URL'>$URL</a> to review it.");
			$email->send();
			
			return array(
				'Content' => "<p><strong>Your theme has been submitted and is awaiting approval.</strong></p>"
			);
		} else {
			$theme->publish('Stage', 'Live');
			Director::redirect($theme->Link());
		}
	}
	
	function checkDirectoryFiles($directory, $allowedExtensions) {
		foreach($directory['listing'] as $name => $listing) {
			if($listing['type'] == 'directory') {
				$error = $this->checkDirectoryFiles($listing, $allowedExtensions);
				if($error) {
					return $error;
				}
			} else if(!in_array(substr($name, strrpos($name, '.') + 1), $allowedExtensions)) {
				return "File $name not allowed in $listing[path]";
			}
		}
		
		return false;
	}
}

?>
