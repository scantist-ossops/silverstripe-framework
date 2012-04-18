<div class="breadcrumbs-wrapper cms-flextable-content">
	<% control Breadcrumbs %>
		<span class="crumb-wrapper">
			<% if Last %>
				<span class="cms-panel-link crumb">$Title.XML</span>
			<% else %>
				<a class="cms-panel-link crumb" href="$Link">$Title.XML</a>/
			<% end_if %>
		</span>
	<% end_control %>
</div>