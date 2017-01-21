<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Alexander Hornig 2016
 */

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\ldapcontacts\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

return [
	'routes' => [
		[ 'name' => 'Page#index', 'url' => '/', 'verb' => 'GET' ],
		[ 'name' => 'Page#loadGroups', 'url' => '/load', 'verb' => 'GET' ],
		[ 'name' => 'Page#loadUsers', 'url' => '/load/users', 'verb' => 'GET' ],
		[ 'name' => 'Page#addUser', 'url' => '/add/group/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#removeUser', 'url' => '/remove/group/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#addAdminUser', 'url' => '/add/group/user/admin', 'verb' => 'POST' ],
		[ 'name' => 'Page#removeAdminUser', 'url' => '/remove/group/user/admin', 'verb' => 'POST' ],
		[ 'name' => 'Page#show', 'url' => '/load/own', 'verb' => 'GET' ],
		[ 'name' => 'Page#canEdit', 'url' => '/canedit', 'verb' => 'POST' ],
		[ 'name' => 'Page#isSuperUser', 'url' => '/superuser', 'verb' => 'GET' ],
		[ 'name' => 'Page#addGroup', 'url' => '/add/group', 'verb' => 'POST' ],
		[ 'name' => 'Page#removeGroup', 'url' => '/remove/group', 'verb' => 'POST' ],
		[ 'name' => 'Page#deleteUser', 'url' => '/delete/user', 'verb' => 'POST' ],
		[ 'name' => 'Page#createUser', 'url' => '/create/user', 'verb' => 'POST' ],
	]
];
