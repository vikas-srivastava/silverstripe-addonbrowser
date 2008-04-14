<div id="Content">
	<h1 class="genericTitle">$Parent.Title</h1>
	
	<% include Menu2 %>
	
	<div class="genericContent typography">
		<h2>$Title</h2>
		$Content
		
		<h2>Widgets</h2>
		<% control Widgets %>
			<div class="widgetPreview">
				<a href="$Link" title="View Information on this Widget">$WidgetScreenFront.SetWidth(200)</a>
				<h3><a href="$Link" title="View Information on this Widget">$Title</a><span> by <a href="$AuthorLink">$AuthorName</a></h3>
				$Content
				<p class="moreOptions"<a href="$WidgetFile.URL" title="Download">Download Widget</a> | <a href="$Link" title="View Information on this Widget">More Info</a></p>
			</div>
			<div class="clear"><!-- --></div>
		<% end_control %>
		<div class="themePagination">
			<% if Widgets.PrevLink %>
				<p class="prevLink"><a href="$Widgets.PrevLink">Previous</a></p>
			<% end_if %>
			<% if Widgets.NextLink %>
				<p class="nextLink"><a href="$Widgets.NextLink">Next</a></p>
			<% end_if %>
		</div>

	</div>
</div>