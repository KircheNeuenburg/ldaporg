<?php
/**
 * Nextcloud - ldaporg
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Hornig Software 2017
 */

/** @var \OCP\IL10N $l */

script( 'ldaporg', 'main' );
style( 'ldaporg', 'main' );
style( 'ldaporg', 'tutorial' );
// load font awesome icons
style( 'ldaporg', 'fa-4.7.0/css/font-awesome.min' );
?>
<div id="app">
	<div id="app-navigation">
		<script id="navigation-header-tpl" type="text/x-handlebars-template">
			<a href="#" id="add-group"><i class="fa fa-plus-square"></i><span><?php p($l->t( 'Add Group')); ?></span></a>
		</script>
		<div id="navigation-header"><div class="icon-loading centered"></div></div>
		
		<script id="navigation-tpl" type="text/x-handlebars-template">
			<ul>
				{{#if groups}}
					{{#each groups}}
						<li class="group {{#if active}}active{{/if}}"  data-id="{{ id }}">
							<a href="#" class="load">{{ cn }}</a>
							{{#if superuser}}<a><span class="icon icon-delete" title="<?php p($l->t( 'Delete Group' )); ?>" data-id="{{ id }}"></span></a>{{/if}}
						</li>
					{{/each}}
				{{else}}
					<li><a><?php p($l->t( "You are not a member of any group" )); ?></li>
				{{/if}}
			</ul>
		</script>
		<div id="group-navigation"><div class="icon-loading centered"></div></div>
	</div>

	<div id="app-content">
		<script id="content-tpl" type="text/x-handlebars-template">
			{{#if group}}
				<h2>{{ group.cn }}</h2>
				
				<div class="content-nav">
					{{#if group.canedit}}
						<span class="search"><input type="search" id="group_add_member" placeholder="<?php p($l->t('Add Member')); ?>"><span class="abort"></span></span>
						<div class="search-suggestions"></div>
					{{/if}}
					{{#if me}}{{#if notForcedMembership }}<a href="#" id="leave_group" class="leave"><?php p($l->t('End Group Membership')); ?></a>{{/if}}{{/if}}
				</div>
				
				<div class="msg-container"><span class="msg"></span></div>
				
				
				<h3><?php p($l->t( 'Members' )); ?></h3>
					{{#if group.members}}
						<table>
							<tbody>
								{{#if group.canedit}}
									{{#each group.members}}
										<tr class="members-menu">
											<td>{{ name }} {{#if isadmin}}<i class="fa fa-user-circle" aria-hidden="true" title="<?php p($l->t( 'Group Admin')); ?>"></i>{{/if}} </td>
											<td>
												<a href="#" class="icon icon-more"></a>
												<div class="hidden options">
													<ul>
														{{#if isadmin}}<li><a href="#" class="remove-admin" data-id="{{ id }}" data-action="removeAdmin"><i class="fa fa-user-times" aria-hidden="true"></i><span><?php p($l->t( 'Remove Admin Privileges')); ?></span></a></li>
														{{else}}<li><a href="#" class="add-admin" data-id="{{ id }}" data-action="addAdmin"><i class="fa fa-user-plus" aria-hidden="true"></i><span><?php p($l->t( 'Make Admin')); ?></span></a></li>
														{{/if}}
														<li><a href="#" class="remove" data-id="{{ id }}" data-action="remove"><span class="icon icon-delete"></span><span><?php p($l->t( 'Remove')); ?></span></a></li>
													</ul>
												</div>
											</td>
										</tr>
									{{/each}}
								{{else}}
									{{#each group.members}}
										<tr>
											<td>{{ name }} {{#if isadmin}}<i class="fa fa-user-circle" aria-hidden="true" title="<?php p($l->t( 'Group Admin')); ?>"></i>{{/if}} </td>
											<td>{{#if isadmin}}<span class="admin"></span>{{/if}}</td>
										</tr>
									{{/each}}
								{{/if}}
							</tbody>
						</table>
					{{else}}
						<h4><?php p($l->t('There are no members in this group yet')); ?></h4>
					{{/if}}
			{{else}}
				<h3><?php p($l->t('Select a group from the list to view details')); ?></h3><span class="msg"></span>
			{{/if}}
		</script>
		
		<script id="add-group-tpl" type="text/x-handlebars-template">
			<form id="add-group-form">
				<label for="add-group-name"><?php p($l->t( 'Group Name' )); ?></label> <input type="text" name="group_name" id="add-group-name" placeholder="<?php p($l->t( 'Group Name')); ?>">
				<button id="add-group-create" type="submit"><?php p($l->t( 'Add Group' )); ?></button>
				<div><span class="msg"></span></div>
			</form>
		</script>
		
		<script id="remove-group-tpl" type="text/x-handlebars-template">
			<div>
				<h2><?php p($l->t( 'Do you really want to remove the group "{{ group.cn }}"?' )); ?></h2>
				<div><span class="msg"></span></div>
				
				<div>
					<button id="remove-group"><?php p($l->t( 'Yes' )); ?></button>
					<button id="abort-remove-group"><?php p($l->t( 'No' )); ?></button>
				</div>
			</div>
		</script>
		<div id="info"><div class="icon-loading centered"></div></div>
	</div>
</div>

<script id="loading-tpl" type="text/x-handlebars-template"><div class="icon-loading centered"></div></script>


<script id="tutorial-tpl" type="text/x-handlebars-template">
	<div id="tutorial-container" style="display: none">
		<div class="body">
			{{ message }}
		</div>
		<div class="footer">
			<button id="tutorial-next"><?php p($l->t( 'Got it' )); ?></button>
		</div>
	</div>
</script>

<div id="tutorial-translations" style="display: none">
	<p><?php p($l->t( 'For creating a group click here' )); ?></p>
	<p><?php p($l->t( 'Select a group from the list to view details' )); ?></p>
	<p><?php p($l->t( 'Be careful! This button will delete a group' )); ?></p>
	<p><?php p($l->t( 'Search for a user here to add him to this group' )); ?></p>
	<p><?php p($l->t( 'Here you can leave this group' )); ?></p>
	<p><?php p($l->t( 'All members of this group are listed here' )); ?></p>
</div>
