<div class="cms-menu west">

	<div class="cms-header">
		<div class="cms-logo">
			<a href="http://www.silverstripe.org/" target="_blank">
				SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
			</a>
			<span>My website</span>
		</div>
		<div class="cms-login-status">
			<a href="Security/logout" class="logout-link"><% _t('LOGOUT','Log out') %></a>
			<% control CurrentMember %>
				<% _t('Hello','Hi') %> 
				<strong>
					<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link">
						<% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %>
					</a>
				</strong>
			<% end_control %>
		</div>
	</div>

	<ul class="cms-menu-list">
	<% control MainMenu %>
		<li class="$LinkingMode" id="Menu-$Code">
			<% if Title == 'Edit Page' %>
			<a href="#">$Title</a>
			<!-- TODO Hardcoded until we can configure CMSMenu through static configuration files -->
			<ul>
				<li <% if Top.class == 'CMSPageEditController' %> class="current"<% end_if %>>
					<a href="admin/page/edit/show/1">Content</a>
				</li>
				<li<% if Top.class == 'CMSPageSettingsController' %> class="current"<% end_if %>>
					<a href="admin/page/settings/show/1">Settings</a>
				</li>
				<li>
					<a href="#">Reports</a>
				</li>
				<li>
					<a href="#">History</a>
				</li>
			</ul>
			<% else %>
			<a href="$Link">$Title</a>
			<% end_if %>
		</li>
	<% end_control %>
	</ul>

</div>