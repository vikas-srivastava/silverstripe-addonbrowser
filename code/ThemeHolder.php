<?php
/*
 * ThemeHolder Page type
 */
 
class ThemeHolder extends Page {
	
	static $db = array(
	);

	static $has_one = array(
	);
	
	static $allowed_children = array(
		'ThemePage'
	);

}

class ThemeHolder_Controller extends Page_Controller {
	function init() {
		parent::init();
		Requirements::css('ssopen/css/Themes.css');
	}
	
	function xml() {
		Controller::curr()->getResponse()->addHeader("Content-type", "text/xml");
		return array();
	}
	
	function Themes() {
	  $start = (!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) ? 0 : $_GET['start'];

		return DataObject::get('ThemePage','','`Created` DESC','',"$start,27");
	}
	
	function ThemeForm() {
		$fields = new FieldSet(
			new HiddenField('ParentID', 'ParentID', $this->ID),
			new HiddenField('ID', 'ID'),
			new TextField('Title', 'Theme Name'),
			new TextField('ReleaseNum', 'Release Number', '1.0.0'),
			new SimpleImageField('Screenshot', 'Screenshot'),
			new FileField('ThemeFile', 'Theme (as a .tar.gz)', null, null, null, 'downloads/themes'),
			new TextareaField('Content', 'Description', 10, 40)
		);
		
		$submitAction = new FormAction('submittheme', 'Submit');
		$actions = new FieldSet($submitAction);
		$validator = new RequiredFields('Title', 'ReleaseNum', 'Screenshot', 'ThemeFile', 'Content');
		
		$form = new ThemeHolder_Form($this, 'ThemeForm', $fields, $actions, $validator);
		
		if(Director::urlParam('ID')) {
			$theme = DataObject::get_by_id('ThemePage', Director::urlParam('ID'));
			if($theme && (Permission::check('ADMIN') || $theme->AuthorID == Member::currentMember()->ID)) {
				$form->loadNonBlankDataFrom($theme);
				$validator = new RequiredFields('Title', 'ReleaseNum', 'Content');
				$form->setValidator($validator);
			}
		}
		
		return $form;
	}
	
	function edit() {
		if(!Member::currentMember()) {
			Security::permissionFailure($this, 'Please log in to submit a theme');
		}
		
		return array();
	}
}


class ThemeHolder_Form extends Form {
	function submittheme($data) {
		if($data['ID'] != 0) {
			$theme = DataObject::get_by_id("ThemePage", $data['ID']);
			$update = true;
		} else {
			// Create a new ThemePage and save the form into it
			$theme = new ThemePage();
			$update = false;
		}
		
		$themeFileID = $theme->ThemeFileID;
		$screenshotID = $theme->ScreenshotID;
		
		$this->saveInto($theme);
		if($theme->ThemeFileID == 0) {
			$theme->ThemeFileID = $themeFileID;
		}
		if($theme->ScreenshotID == 0) {
			$theme->ScreenshotID = $screenshotID;
		}
		
		// Open the uploaded archive and get the list of files in it
		$archive = Archive::open($theme->ThemeFile()->Filename);
		if($archive) {
			$listing = $archive->listing();
			
			// Check the archive is structured properly for a theme
			$error = '';
			if(count($listing) < 1) {
				// archive is empty
				$error = 'Archive is empty';
			}
			
			$themename = '';
			$supportedModules = array();
			foreach($listing as $name => $listItem) {
				if($listItem['type'] == 'file') {
					$error = 'There is a file $name in the root directory. Your theme should be in a folder.';
					break;
				}
				
				// Check if the current folder is a subtheme
				$underPos = strpos($name, '_');
				$tname = ($underPos === false) ? $name : substr($name, 0, $underPos);
				if($underPos !== false) {
					$supportedModules[] = substr($name, $underPos + 1);
				}
				
				// Check there is only one theme in this archive
				if($themename == '') {
					$themename = $tname;
				} else if ($themename != $tname) {
					 $error = "There appears to be more than one theme in this archive - $themename and $tname.";
					break;
				}
				
				// Check the contents of each theme folder
				foreach($listItem['listing'] as $name => $themeFolder) {
					if($listItem['type'] == 'file') {
						$error = "There is a file $name in your theme directory. Your theme directory should be layed out in three folders - templates, css and images.";
						break;
					}
					
					if($name == 'templates') {
						$error = $this->checkDirectoryFiles($themeFolder, array('ss', 'DS_Store'));
					} else if($name == 'images') {
						$error = $this->checkDirectoryFiles($themeFolder, array('png', 'jpg', 'jpeg', 'gif', 'DS_Store'));
					} else if($name == 'css') {
						$error = $this->checkDirectoryFiles($themeFolder, array('css', 'DS_Store'));
					} else {
						$error = "Unknown directory $name in your theme directory. Your theme directory should be layed out in three folders - templates, css and images.";
						break;
					}
				}
			}
			
			$SQL_themename = Convert::raw2sql($themename);
			
			$shortnameCheck = DataObject::get_one('ThemePage', "ShortName='$SQL_themename'");
			if($shortnameCheck && $shortnameCheck->ID != $theme->ID) {
				$error = "There already exists a theme with the name '$themename'";
			}
		} else {
			$error = "The theme must be a .tar.gz archive.";
		}
		
		if($error) {
			$this->Content = "<p>Your theme could not be submitted:<br />$error</p>";
			$this->Title = "Theme submission error";
			return $this->renderWith('Page');
		}
		
		$theme->ShortName = $themename;
		$theme->URLSegment = $themename;
		$theme->SupportedModules = implode(', ', $supportedModules);
		if($theme->SupportedModules == '') {
			$theme->SupportedModules = 'none';
		}
		$theme->AuthorID = Member::currentUser()->ID;
		$theme->ProvideComments = true;
		
		$theme->writeToStage('Stage');
		
		if(!$update) {
			$email = new Email('themes@silverstripe.com', 'andrew@silverstripe.com', "New theme '$themename' has been uploaded", null, null, 'will@silverstripe.com');
			$email->setBody("A new theme '$themename' has been uploaded by " . Member::currentUser()->Nickname . ". Please go to {$theme->AbsoluteLink()}?stage=Stage to review it.");
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
