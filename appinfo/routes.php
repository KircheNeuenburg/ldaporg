<?php
/**
 * Nextcloud - ldaporg
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Alexander Hornig 2016
 */

return [
	'routes' => [
		[ 'name' => 'Page#index', 'url' => '/', 'verb' => 'GET' ],
		[ 'name' => 'Page#loadGroups', 'url' => '/load/groups', 'verb' => 'GET' ],
		[ 'name' => 'Page#adminLoadGroups', 'url' => '/admin/load/groups', 'verb' => 'GET' ],
		[ 'name' => 'Page#loadUsers', 'url' => '/load/users', 'verb' => 'GET' ],
		[ 'name' => 'Page#adminLoadUsers', 'url' => '/admin/load/users', 'verb' => 'GET' ],
		[ 'name' => 'Page#addUserToGroup', 'url' => '/add/group/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#removeUserFromGroup', 'url' => '/remove/group/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#groupAddAdminUser', 'url' => '/add/group/user/admin', 'verb' => 'POST' ],
		[ 'name' => 'Page#groupRemoveAdminUser', 'url' => '/remove/group/user/admin', 'verb' => 'POST' ],
		[ 'name' => 'Page#show', 'url' => '/load/own', 'verb' => 'GET' ],
		[ 'name' => 'Page#canEdit', 'url' => '/canedit/{group_entry_id}', 'verb' => 'GET' ],
		[ 'name' => 'Page#isSuperUser', 'url' => '/superuser', 'verb' => 'GET' ],
		[ 'name' => 'Page#createGroup', 'url' => '/create/group', 'verb' => 'POST' ],
		[ 'name' => 'Page#deleteGroup', 'url' => '/delete/group/{group_entry_id}', 'verb' => 'GET' ],
		[ 'name' => 'Page#createUser', 'url' => '/create/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#deleteUser', 'url' => '/delete/user/{user_entry_id}', 'verb' => 'GET' ],
		[ 'name' => 'Page#sendWelcomeMail', 'url' => '/welcomemail', 'verb' => 'POST' ],
		[ 'name' => 'Page#applyForcedGroupMemberships', 'url' => '/apply/forcedMemberships', 'verb' => 'GET' ],
		[ 'name' => 'Page#exportGroupMemberDetails', 'url' => '/export/{group_entry_id}', 'verb' => 'GET' ],
		
		[ 'name' => 'Settings#getSetting', 'url' => '/setting/{key}', 'verb' => 'GET' ],
		[ 'name' => 'Settings#updateSetting', 'url' => '/setting', 'verb' => 'POST' ],
		[ 'name' => 'Settings#getSettings', 'url' => '/settings', 'verb' => 'GET' ],
		[ 'name' => 'Settings#updateSettings', 'url' => '/settings', 'verb' => 'POST' ],
		[ 'name' => 'Settings#getUserValue', 'url' => '/settings/personal/{key}', 'verb' => 'GET' ],
		[ 'name' => 'Settings#setUserValue', 'url' => '/settings/personal', 'verb' => 'POST' ],
	]
];
