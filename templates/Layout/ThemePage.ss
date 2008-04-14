<div id="Content">
	<h1 class="genericTitle">$Parent.Title</h1>
	
	<% include Menu2 %>
	
	<div class="genericContent typography">
		<div id="ThemeDetails">
			<h2 class="themePage">
				$Title
				<em> by <a href="$AuthorLink">$Author.Nickname</a></em>
			</h2>
			
			<div id="TemplateActions">
				<p>
					<% if EditLink %><a class="editButton" href="$EditLink" title="click here to edit the themes details">Edit Theme</a><% end_if %>
					<a class="downloadButton" title="click here to download the theme" href="$ThemeFile.URL">Download</a> 
					<a class="previewButton" title="click here to view the theme on our demo site" href="$PreviewLink">Preview</a>
				</p>
			</div>
		</div>
		
		<div id="ScreenShots">
			$Screenshot.SetWidth(740)
		</div>
		<div id="TemplateInfo">
			<h3>Template Info</h3>
			<ul id="TemplateInfo">
				<li><span>Theme Name:</span> $Title</li>
				<li title="Folder name for the theme"><span>Code Name:</span> $ShortName (folder name for the theme)</li>
				<li><span>Template Author:</span>
					<a href="$AuthorLink">$Author.Nickname</a>
				</li>
				<li><span>Released:</span> $Created.Long</li>
				<li><span>Version:</span> $ReleaseNum</li>
				<li><span>Supported Modules:</span> $SupportedModules</li>
			</ul>
		</div>
		<div id="Subthemes">
			<h4>Subthemes</h4>
			<% if Subthemes %>
				<ul>
					<% control Subthemes %>
						<li><a href="$SubthemeFile.URL">$Module</a> by <a href="$Author.Link">$Author.Nickname</a></li>
					<% end_control %>
				</ul>
			<% else %>
				<p>There are no subthemes for this theme.</p>
			<% end_if %>
			<p><a href="$SubthemeLink">Submit a subtheme</a></p>
		</div>
		<div class="clear"><!--  --></div>
		<p>
			$Content
		</p>
		
		
		<% if ApproveLink %>
			<p><a href="$ApproveLink">Approve this theme</a></p>
		<% end_if %>

				
		<p id="NotTheTheme">Not the Theme for you? Head back to the <a href="$Parent.Link">Themes Page</a></p>
		<p id="SupportLink">Need help? Got any questions? Then hit up our <a href="http://www.silverstripe.com/themes/">forums</a></p>
		
		$Comments
	</div>
</div>
