<div id="Content">
	<h1 class="genericTitle">$Parent.Title</h1>
	
	<% include Menu2 %>
	
	<div class="genericContent typography">
		<div id="ThemeDetails">
			<h2 class="themePage">
				$Title
				<em> by <a href="$AuthorLink">$WidgetAuthor.Nickname</a></em>
			</h2>
		</div>
		
		<div id="ScreenShots">
			<% if WidgetScreenBack %>
			<div id="WidgetScreenBack">
				<h4>Widget Backend View</h4>
				$WidgetScreenBack.SetWidth(200)
			</div>
			<% end_if %>
			
			<% if WidgetScreenFront %>
			<div id="WidgetScreenFront">
				<h4>Widget Frontend View</h4>
				$WidgetScreenFront.SetWidth(200)
			</div>
			<% end_if %>
		</div>
		<div id="WidgetInfo">
			<h3>Template Info</h3>
			<ul id="WidgetInfo">
				<li><span>Widget Name:</span> $Title</li>
				<li><span>Download:</span> <a class="downloadButton" title="click here to download the theme" href="$WidgetFile.URL">Download Widget</a>
				<li><span>Widget Author:</span><a href="$AuthorLink">$WidgetAuthor.Nickname</a></li>
				<li><span>Released:</span> $Created.Long</li>
				<li><span>Version:</span> $WidgetVersion</li>
			</ul>
		</div>
		<div class="clear"><!--  --></div>
		<p>
			$Content
		</p>
			
	</div>
</div>