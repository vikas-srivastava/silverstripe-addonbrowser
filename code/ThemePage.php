<?php
/*
 * ThemePage type
 */
 
class ThemePage extends Page {
	
	static $db = array(
		"ShortName" => "Text",
		"ReleaseNum" => "Text",
		"SupportedModules" => "Text",
		'ThemeFolderName' => 'Varchar',
		'ThemeFolderNameSet' => 'Int',
	);

	static $has_one = array(
		"Screenshot" => "Image",
		"Author" => "Member",
		"ThemeFile" => "File"
	);
		
	static $has_many = array(
		"Subthemes" => "Subtheme"
	);
		
	static $defaults = array(
		"ProvideComments" => true
	);
	
	static $default_parent = array('ThemeHolder');

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Content.Images", new ImageField("Screenshot", "Screenshot"));
		$fields->addFieldToTab("Root.Content.Main", new TypeDropdown('AuthorID', 'Author', 'Member'));
		$fields->addFieldToTab("Root.Content.Main", new TextField('ShortName', 'Short Name'), 'Content');
		$fields->addFieldToTab("Root.Content.Main", new TextField("ReleaseNum", "Version No"), "Content");
		$fields->addFieldToTab("Root.Content.Main", new FileIFrameField('ThemeFile', 'ThemeFile'));
		return $fields;
	}
	
	function publish($fromStage, $toStage, $createNewVersion = false) {
		$archive = Archive::open($this->ThemeFile()->getRelativePath());
		$archive->extractTo('/sites/ss2demo/www/themes');
		return $this->extInstance('Versioned')->publish($fromStage, $toStage, $createNewVersion);
	}

	/**
	 * Return the name of the widget - the root folder will be "widgets_(name)"
	 */
	function Name() {
		$filename = Director::makeRelative($this->ThemeFile()->URL);
		if(file_exists("../$filename") && (!$this->ThemeFolderName || filemtime("../$filename") > $this->ThemeFolderNameSet)) {

			if(substr($filename,-3) == 'tar') $result = `tar tf ../$filename | head -n 1`;
			else if(substr($filename,-6) == 'tar.gz' || substr($filename,-3) == 'tgz') $result = `tar tzf ../$filename | head -n 1`;
			else break;
			
			$this->ThemeFolderName = str_replace('/','',trim($result));
			$this->ThemeFolderNameSet = filemtime("../$filename");
			$this->write();
		}
		return $this->ThemeFolderName;
	}
}

class ThemePage_Controller extends Page_Controller {
	function init() {
		parent::init();
		Requirements::css('ssopen/css/Themes.css');
	}
	
	function ApproveLink() {
		if(Versioned::current_stage() == 'Stage' && Permission::check('ADMIN')) {
			return $this->Link('approve');
		}
	}
	
	function approve() {
		if(Permission::check('ADMIN')) {
			$this->publish('Stage', 'Live');
			$_GET['stage'] = 'Live';
			Versioned::choose_site_stage();
			return array();
		}
	}
	
	function EditLink() {
		if(Permission::check('ADMIN') || (Member::currentMember() && ($this->AuthorID == Member::currentMember()->ID))) {
			return $this->Parent()->Link() . 'edit/'. $this->ID;
		}
	}
	
	function AuthorLink() {
		return 'ForumMemberProfile/show/' . $this->AuthorID;
	}
	
	function PreviewLink() {
		return 'http://demo.silverstripe.com/?theme=' . $this->ShortName . '&flush=1';
	}
	
	
	function SubthemeForm() {
		$fields = new FieldSet(
			new HiddenField('ThemeID', 'ThemeID', $this->ID),
			new HiddenField('ID', 'ID'),
			new FileField('SubthemeFile', 'Subtheme (as a .tar.gz)')
		);
		
		$submitAction = new FormAction('submitSubtheme', 'Submit');
		$actions = new FieldSet($submitAction);
		$validator = new RequiredFields('SubthemeFile');
		
		$form = new ThemePage_Form($this, 'SubthemeForm', $fields, $actions, $validator);
		
		if(Director::urlParam('ID')) {
			$theme = DataObject::get_by_id('ThemePage', Director::urlParam('ID'));
			if($theme && (Permission::check('ADMIN') || $theme->AuthorID == Member::currentMember()->ID)) {
				$form->loadNonBlankDataFrom($theme);
				$form->setValidator(false);
			}
		}
		
		return $form;
	}
	
	function subtheme() {
		if(!Member::currentMember()) {
			Security::permissionFailure($this, 'Please log in to submit a subtheme');
		}
		
		return array();
	}
	
	function SubthemeLink() {
		return $this->Link('subtheme');
	}
}

class ThemePage_Form extends Form {
	function submitSubtheme($data) {
		$subtheme = new Subtheme();
		$update = false;
		
		$subthemeFileID = $subtheme->SubthemeFileID;
		
		$this->saveInto($subtheme);
		if($subtheme->SubthemeFileID == 0) {
			$subtheme->SubthemeFileID = $subthemeFileID;
		}
		
		// Open the uploaded archive and get the list of files in it
		$archive = Archive::open($subtheme->SubthemeFile()->Filename);
		if($archive) {
			$listing = $archive->listing();
			// Check the archive is structured properly for a subtheme
			$error = '';
			if(count($listing) < 1) {
				// archive is empty
				$error = 'Archive is empty';
			} else if(count($listing) != 1) {
				// A subtheme should just have the single subtheme folder
				$error = 'There are multiples files/directories in the archive. A subtheme should contain a single folder named theme_module, eg blackcandy_blog.';
			} else {
				$name = key($listing);
				$subthemeDir = current($listing);
				
				// Check the contents of each theme folder
				foreach($subthemeDir['listing'] as $subdirName => $subdirFolder) {
					if($subdirFolder['type'] == 'file') {
						$error = "There is a file $subdirName in your theme directory. Your theme directory should be layed out in three folders - templates, css and images.";
						break;
					}
					
					if($subdirName == 'templates') {
						$error = $this->checkDirectoryFiles($subdirFolder, array('ss', 'DS_Store'));
					} else if($subdirName == 'images') {
						$error = $this->checkDirectoryFiles($subdirFolder, array('png', 'jpg', 'jpeg', 'gif', 'DS_Store'));
					} else if($subdirName == 'css') {
						$error = $this->checkDirectoryFiles($subdirFolder, array('css', 'DS_Store'));
					} else {
						$error = "Unknown directory $subdirName in your theme directory. Your theme directory should be layed out in three folders - templates, css and images.";
						break;
					}
				}
				
				$underPos = strpos($name, '_');
				if($underPos === false) {
					$error = 'The subtheme directory should be named theme_module, eg blackcandy_blog.';
				} else {
					$themeName = substr($name, 0, $underPos);
					$SQL_themeName = Convert::raw2sql($themeName);
					$module = substr($name, $underPos + 1);
					
					$theme = DataObject::get_one('ThemePage', "ShortName='$SQL_themeName'");
					
					if(!$theme) {
						$error = "No theme named $themeName";
					}
				}
			}
		}
		else {
			$error = "The subtheme must be a .tar.gz archive.";
		}
		
		
		if($error) {
			$this->Content = "<p>Your subtheme could not be submitted:<br />$error</p>";
			$this->Title = "Subtheme submission error";
			return $this->renderWith('Page');
		} else {
			$subtheme->ParentID = $theme->ID;
			$subtheme->Visible = false;
			$subtheme->Module = $module;
			$subtheme->AuthorID = Member::currentMember()->ID;
			
			$oldSubtheme = DataObject::get_one("Subtheme", 'Module=\'' . $subtheme->Module . '\' AND AuthorID=\'' . Member::currentMember()->ID . '\'');
			if($oldSubtheme) {
				$subtheme->ID = $oldSubtheme->ID;
				$subtheme->Visible = $oldSubtheme->Visible;
				$update = true;
			}
			
			$subtheme->write();
			
			if(!$update) {
				$email = new Email('themes@silverstripe.com', 'andrew@silverstripe.com', "New subtheme '$name' has been uploaded");
				$email->setBody("A new theme '$name' has been uploaded by $subtheme->Nickname. The file is at {$subtheme->SubthemeFile()->AbsoluteLink()}, please visit " . Director::absoluteURL('Subtheme/approve/' . $subtheme->ID) . " to approve it.");
				$email->send();
				
				$this->Content = "<p><strong>Your subtheme has been submitted and is awaiting approval.</strong></p>";
				return $this->renderWith('Page');
			} else {
				Director::redirect($subtheme->Parent()->Link());
			}
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
