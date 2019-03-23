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
script('ldaporg', 'templates');
style( 'ldaporg', 'main' );
style( 'ldaporg', 'tutorial' );
// load font awesome icons
style( 'ldaporg', 'fa-4.7.0/css/font-awesome.min' );
?>
<div id="app">
	<div id="app-navigation">
		<div id="navigation-header"><div class="icon-loading centered"></div></div>
		<div id="group-navigation"><div class="icon-loading centered"></div></div>
	</div>

	<div id="app-content">
			<script id="remove-group-tpl" type="text/x-handlebars-template">
			<div>
				<h2><?php p($l->t( 'Do you really want to remove the group "{{ group.ldapcontacts_name }}"?' )); ?></h2>
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


<div id="tutorial-translations" style="display: none">
	<p><?php p($l->t( 'For creating a group click here' )); ?></p>
	<p><?php p($l->t( 'Select a group from the list to view details' )); ?></p>
	<p><?php p($l->t( 'Be careful! This button will delete a group' )); ?></p>
	<p><?php p($l->t( 'Search for a user here to add him to this group' )); ?></p>
	<p><?php p($l->t( 'Here you can leave this group' )); ?></p>
	<p><?php p($l->t( 'All members of this group are listed here' )); ?></p>
	<p><?php p($l->t( 'Use this to export all information for every member of this group' )); ?></p>
</div>
