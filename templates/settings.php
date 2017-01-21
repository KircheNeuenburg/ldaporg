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
 
/** @var array $_ */
/** @var \OCP\IL10N $l */

script( 'ldaporg', 'settings' );
style( 'ldaporg', 'settings' );
?>
<div id="LdapOrgSettings" class="section">
	<h2><?php p($l->t( 'Users' )); ?></h2>
	
	<script id="ldaporg-existing-users-tpl" type="text/x-handlebars-template">
		{{#each users}}
			<div class="user">
				{{ name }}
				<span class="icon icon-delete" title="<?php p($l->t( 'Delete User' )); ?>" data-id="{{ id }}"></span>
			</div>
		{{/each}}
	</script>
	<div id="ldaporg-existing-users">
		<div class="icon-loading"></div>
	</div>
	
	<script id="ldaporg-user-content-tpl" type="text/x-handlebars-template">
		<h2><?php p($l->t( 'Create a user' )); ?></h2>
		<div class="msg-container"><span class="msg"></span></div>
		
		<form id="ldaporg-create-user-form">
			<table><tbody>
				<tr>
					<td><label for="ldaporg-create-user-firstname"><?php p($l->t( 'Firstname' )); ?></label></td>
					<td><input type="text" id="ldaporg-create-user-firstname" name="firstname" placeholder="<?php p($l->t( 'Firstname' )); ?>"></div></td>
				</tr>
				<tr>
					<td><label for="ldaporg-create-user-lastname"><?php p($l->t( 'Lastname' )); ?></label></td>
					<td><input type="text" id="ldaporg-create-user-lastname" name="lastname" placeholder="<?php p($l->t( 'Lastname' )); ?>"></div></td>
				</tr>
				<tr>
					<td><label for="ldaporg-create-user-mail"><?php p($l->t( 'eMail Adress' )); ?></label></td>
					<td><input type="text" id="ldaporg-create-user-mail" name="mail" placeholder="<?php p($l->t( 'eMail Adress' )); ?>"></div></td>
				</tr>
			</tbody></table>
			
			<br>
			
			<div>
				<button id="ldaporg-create-user"><?php p($l->t( 'Create' )); ?></button>
				<button type="reset"><?php p($l->t( 'Abort' )); ?></button>
			</div>
		</form>
	</script>
	<script id="ldaporg-user-delete-tpl" type="text/x-handlebars-template">
		<h3><?php p($l->t( 'Do you really want to delete the user {{ user.name }}?' )); ?></h3>
		<div class="msg-container"><span class="msg"></span></div>
				
		<div>
			<button id="ldaporg-delete-user"><?php p($l->t( 'Yes' )); ?></button>
			<button id="ldaporg-abort-delete-user"><?php p($l->t( 'No' )); ?></button>
		</div>
	</script>
	<div id="ldaporg-user-content">
		<div class="icon-loading"></div>
	</div>
</div>