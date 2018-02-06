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
	<h2><?php p($l->t( 'LDAP Org Settings' )); ?></h2>
	<script id="ldaporg-edit-settings-tpl" type="text/x-handlebars-template">
		<form id="ldaporg_settings_form">
			<table><tbody>
				<tr>
					<td><label for="ldaporg_superuser_groups"><?php p($l->t( 'Superuser Group' )); ?></label></td>
					<td>
						{{#each groups}}
							<span class="checkbox-wrapper">
								<input type="checkbox" id="ldaporg_superuser_group_{{@index}}" name="superuser_groups[]" value="{{ ldapcontacts_entry_id }}" {{#if ldaporg_superuser_group}}checked{{/if}}>

								<label for="ldaporg_superuser_group_{{@index}}">{{ ldapcontacts_name }}</label>
							</span>
						{{/each}}
					</td>
				</tr>
				<tr>
					<td><label><?php p($l->t( 'Use Password Reset URL' ));?></label></td>
					<td>
						{{#istrue settings.pwd_reset_url_active}}
							<input type="radio" id="ldaporg_pwd_reset_url_active_true" name="pwd_reset_url_active" value="true" checked><label for="ldaporg_pwd_reset_url_active_true"><?php p($l->t( 'Yes' )); ?></label>
							<input type="radio" id="ldaporg_pwd_reset_url_active_false" name="pwd_reset_url_active" value="false"><label for="ldaporg_pwd_reset_url_active_false"><?php p($l->t( 'No' )); ?></label>
						{{else}}
							<input type="radio" id="ldaporg_pwd_reset_url_active_true" name="pwd_reset_url_active" value="true"><label for="ldaporg_pwd_reset_url_active_true"><?php p($l->t( 'Yes' )); ?></label>
							<input type="radio" id="ldaporg_pwd_reset_url_active_false" name="pwd_reset_url_active" value="false" checked><label for="ldaporg_pwd_reset_url_active_false"><?php p($l->t( 'No' )); ?></label>
						{{/istrue}}
					</td>
				</tr>
				<tr class="pwd_reset_url" {{#isfalse settings.pwd_reset_url_active}}style="display: none"{{/isfalse}}>
					<td><label for="ldaporg_pwd_reset_url"><?php p($l->t( 'Password Reset URL' ));?></label></td>
					<td><input type="url" id="ldaporg_pwd_reset_url" name="pwd_reset_url" value="{{ settings.pwd_reset_url }}" placeholder="<?php p($l->t( 'Password Reset URL' )); ?>"></td>
				</tr>
				<tr class="pwd_reset_url" {{#isfalse settings.pwd_reset_url_active}}style="display: none"{{/isfalse}}>
					<td><label for="ldaporg_pwd_reset_url_attr"><?php p($l->t( 'Password Reset Attribute' ));?></label></td>
					<td><input type="text" id="ldaporg_pwd_reset_url_attr" name="pwd_reset_url_attr" value="{{ settings.pwd_reset_url_attr }}" placeholder="<?php p($l->t( 'Password Reset Attribute' )); ?>"></td>
				</tr>
				<tr class="pwd_reset_url" {{#isfalse settings.pwd_reset_url_active}}style="display: none"{{/isfalse}}>
					<td><label for="ldaporg_pwd_reset_url_attr_ldap_attr"><?php p($l->t( 'Corresponding LDAP Attribute' ));?></label></td>
					<td><input type="text" id="ldaporg_pwd_reset_url_attr_ldap_attr" name="pwd_reset_url_attr_ldap_attr" value="{{ settings.pwd_reset_url_attr_ldap_attr }}" placeholder="<?php p($l->t( 'LDAP Attribute' )); ?>"></td>
				</tr>
				<tr class="pwd_reset_url" {{#isfalse settings.pwd_reset_url_active}}style="display: none"{{/isfalse}}>
					<td><label for="ldaporg_pwd_reset_tag"><?php p($l->t( 'Link tag to be replaced' ));?></label></td>
					<td><input type="text" id="ldaporg_pwd_reset_tag" name="pwd_reset_tag" value="{{ settings.pwd_reset_tag }}" placeholder="<?php p($l->t( 'Link tag to be replaced' )); ?>"></td>
				</tr>
				<tr>
					<td><label for="ldaporg_welcome_mail_subject"><?php p($l->t( 'Welcome Mail Subject' ));?></label></td>
					<td><input type="text" id="ldaporg_welcome_mail_subject" name="welcome_mail_subject" value="{{ settings.welcome_mail_subject }}" placeholder="<?php p($l->t( 'Welcome Mail Subject' )); ?>"></td>
				</tr>
				<tr>
					<td><label for="ldaporg_welcome_mail_from_adress"><?php p($l->t( 'Welcome Mail FROM' ));?></label></td>
					<td><input type="email" id="ldaporg_welcome_mail_from_adress" name="welcome_mail_from_adress" value="{{ settings.welcome_mail_from_adress }}" placeholder="<?php p($l->t( 'E-Mail Adress' )); ?>"></td>
				</tr>
				<tr>
					<td><label for="ldaporg_welcome_mail_from_name"><?php p($l->t( 'Welcome Mail FROM Name' ));?></label></td>
					<td><input type="text" id="ldaporg_welcome_mail_from_name" name="welcome_mail_from_name" value="{{ settings.welcome_mail_from_name }}" placeholder="<?php p($l->t( 'Name' )); ?>"></td>
				</tr>
				<tr>
					<td><label for="ldaporg_welcome_mail_message"><?php p($l->t( 'Welcome Mail Message' ));?></label></td>
					<td><textarea id="ldaporg_welcome_mail_message" name="welcome_mail_message" placeholder="<?php p($l->t( 'Message' )); ?>">{{ settings.welcome_mail_message }}</textarea></td>
				</tr>
			</tbody></table>
			<button type="submit" id="ldaporg_settings_save"><?php p($l->t( 'Save' )); ?></button><span class="msg"></span>
		</form>
		<br><br>
	</script>
	<div id="ldaporg-edit-settings"><div class="icon-loading"></div></div>
	
	<!-- force group membership section -->
	<script id="ldaporg-force-group-membership-tpl" type="text/x-handlebars-template">
		<div class="search-container">
			<span class="search"><input type="search" id="ldaporg-search-non-forced-memberships-group" placeholder="<?php p($l->t('add mandatory group')); ?>"><span class="abort"></span></span>
			<div class="search-suggestions"></div>
		</div>
		
		{{#if groups}}
			<div class="container">
				{{#each groups}}
					<span class="force-group-membership">
						<span class="name">{{ ldapcontacts_name }}</span><span class="remove" target-id="{{ ldapcontacts_entry_id }}">X</span>
					</span>
				{{/each}}
			</div>
		{{else}}
			<b><?php p($l->t("Users aren't forced to be a member of any group")); ?></b>
		{{/if}}
	</script>
	
	<br><h2><?php p($l->t('Mandatory group memberships')); ?></h2>
	<span id="ldaporg-force-group-membership-msg" class="msg"></span>
	<div id="ldaporg-force-group-membership"><div class="icon-loading"></div></div>
	<div class="button" id="ldaporg-apply-forced-group-membership"><?php p($l->t( 'Apply forced group memberships to all users' )); ?></div>
	<br><br><br>
	
	<h2><?php p($l->t( 'LDAP Users' )); ?></h2>
	<div class="button" id="ldaporg-add-user"><span class="icon icon-add"></span> <?php p($l->t( 'Create User' )); ?></div>
	<br>
	
	<script id="ldaporg-existing-users-tpl" type="text/x-handlebars-template">
		{{#each users}}
			<div class="user">
				{{ ldapcontacts_name }}
				<span class="icon icon-play" title="<?php p($l->t( 'Select' )); ?>" data-id="{{ ldapcontacts_entry_id }}"></span>
			</div>
		{{/each}}
	</script>
	<div id="ldaporg-existing-users"><div class="icon-loading"></div></div>
	
	<script id="ldaporg-user-details-tpl" type="text/x-handlebars-template">
		<div class="user">
			<h3>{{ user.ldapcontacts_name }}</h3>
			
			
			
			<table><tbody>
			{{#each_attributes user attributes}}
				<tr><td><b>{{ label }}</b></td><td>{{ data }}</td></tr>
			{{/each_attributes}}
			</table></tbody>
			<br>
			
			<div class="button resend-welcome-mail" title="<?php p($l->t( 'Resend Welcome Mail' )); ?>" data-id="{{ user.ldapcontacts_entry_id }}">
				<?php p($l->t( 'Resend Welcome Mail' )); ?>&nbsp;
				<span class="icon icon-mail"></span>
			</div>
			
			<div class="delete-user button" title="<?php p($l->t( 'Delete User' )); ?>" data-id="{{ user.ldapcontacts_entry_id }}">
				<?php p($l->t( 'Delete User' )); ?>
				<span class="icon icon-delete"></span>
			</div>
			
			<div class="msg-container"><span class="msg"></span></div>
		</div>
	</script>
	
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
		<h3><?php p($l->t( 'Do you really want to delete the user {{ user.ldapcontacts_name }}?' )); ?></h3>
		<div class="msg-container"><span class="msg"></span></div>
				
		<div>
			<button id="ldaporg-delete-user"><?php p($l->t( 'Yes' )); ?></button>
			<button id="ldaporg-abort-delete-user"><?php p($l->t( 'No' )); ?></button>
		</div>
	</script>
	<div id="ldaporg-user-content"><div class="icon-loading"></div></div>
</div>