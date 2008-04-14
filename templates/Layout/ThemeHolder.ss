<div id="Content">
	<h1 class="genericTitle">$Parent.Title</h1>
	
	<% include Menu2 %>
	
	<div class="genericContent typography">
		<h2>$Title</h2>
		$Content

		<% control Themes %>
			<div class="templatePreview">
				<a href="$Link"><% control Screenshot %>$CroppedImage(230, 180)<% end_control %></a>
				<h4><a href="$Link">$Title</a></h4>
			</div>
		<% end_control %>
		<div class="themePagination">
			<% if Themes.PrevLink %>
				<p class="prevLink"><a href="$Themes.PrevLink">Previous</a></p>
			<% end_if %>
			<% if Themes.NextLink %>
				<p class="nextLink"><a href="$Themes.NextLink">Next</a></p>
			<% end_if %>
		</div>

	</div>
</div>