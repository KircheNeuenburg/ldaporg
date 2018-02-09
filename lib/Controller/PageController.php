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

namespace OCA\LdapOrg\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\Il10n;
use OCP\Mail\IMailer;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCA\LdapOrg\Controller\SettingsController;
use OCA\LdapContacts\Controller\SettingsController as ContactsSettingsController;
use OCA\LdapContacts\Controller\ContactController;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCP\IDBConnection;

class PageController extends ContactController {
	// settings controller
	protected $ldaporg_settings;
	// mail handler
	protected $mailer;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param SettingsController $settings
	 * @param ContactsSettingsController $contacts_settings
     * @param IMailer $mailer
     * @param mixed $UserId
	 */
	public function __construct( $AppName, IRequest $request, IConfig $config, SettingsController $ldaporg_settings, ContactsSettingsController $contacts_settings, IMailer $mailer, Il10n $l10n, $UserId, Manager $userManager, Helper $helper, UserMapping $userMapping, GroupMapping $groupMapping, IDBConnection $db ) {
		parent::__construct( $AppName, $request, $config, $contacts_settings, $UserId, $userManager, $helper, $userMapping, $groupMapping, $db );
		
		// translation
		$this->l2 = $l10n;
		// save the settings controller
		$this->ldaporg_settings = $ldaporg_settings;
		// save the mail handler
		$this->mailer = $mailer;
	}

	/**
	 * main app page
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse( 'ldaporg', 'main' );
	}

	/**
	 * load all groups the current user has access to
	 * 
	 * @NoAdminRequired
	 */
	public function loadGroups() {
		// get the current users LDAP data
		$data = $this->getUsers( $this->uid );
		// check if the user was found
		if( $data ) $data = $data[0];
		else return new DataResponse( [ 'data' => [], 'status' => 'success' ] );
		
		// check if the user is in one of the groups that can edit anything
		$superuser_groups = $this->ldaporg_settings->getSetting( 'superuser_groups' );
		$superuser = false;
		foreach( $superuser_groups as $superuser_group_entry_id ) {
			foreach( $data['groups'] as $user_group ) {
				if( $superuser_group_entry_id === $user_group['ldapcontacts_entry_id'] ) {
					$superuser = true;
					break;
				}
			}
			if( $superuser ) break;
		}
		
		// if the current user is a superuser, load all existing groups
		if( $superuser ) $groups = $this->getGroups( false, true );
		// otherwise, only load the groups the user has access to
		else $groups = $data['groups'];
		
		// reorder the groups array
		$tmp = [];
		foreach( $groups as $id => $group ) {
			$group['ldaporg_members'] = [];
			$tmp[ $group['ldapcontacts_entry_id'] ] = $group;
		}
		$groups = $tmp;
		
		/** add the members to every group **/
		// get all existing users
		$users = $this->getUsers();
		// go through every user and add them to their groups
		foreach( $users as $user ) {
			// add the user to each of his groups
			foreach( $user['groups'] as $group ) {
				$groups[ $group['ldapcontacts_entry_id'] ]['ldaporg_members'][ $user['ldapcontacts_entry_id'] ] = $user;
			}
		}
		
		/** mark all the admins in every group **/
		// go through every group
		foreach( $groups as &$group ) {
			// check this is an LDAP group
			if( !isset( $group['ldapcontacts_entry_id'] ) ) continue;
			
			// get the groups admin users
			$admins = $this->getGroupAdmins( $group['ldapcontacts_entry_id'] );
			
			// go through each admin
			foreach( $admins as $admin ) {
				// mark the user as an admin
				$group['ldaporg_members'][ $admin ]['ldaporg_admin'] = true;
			}
		}
		
		// return the loaded groups
		return new DataResponse( [ 'data' => $groups, 'status' => 'success' ] );
	}
	
	/**
	 * gets all admins for the given group
	 * 
	 * @param string $group_entry_id
	 */
	protected function getGroupAdmins( string $group_entry_id ) {
		$sql = "SELECT admin_id FROM *PREFIX*ldaporg_group_admins WHERE group_id = ?";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check for sql errors
		if( $stmt->errorCode() !== '00000' ) return [];
		
		// get all admin ids
		$tmp = [];
		while( $admin = $stmt->fetchColumn() ) {
			array_push( $tmp, $admin );
		}
		$stmt->closeCursor();
		
		// return fetched admins
		return $tmp;
	}
	
	/**
	 * returns all existing users
	 * 
	 * @NoAdminRequired
	 */
	public function loadUsers() {
		$result = $this->getUsers( false, true );
		// return the groups
		if( $result ) return new DataResponse( [ 'data' => $result, 'status' => 'success' ] );
		else return new DataResponse( [ 'status' => 'error' ] );
	}
	
	/**
	 * sets the given user as an admin in the given group, if the current user is allowed to do this
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 * @NoAdminRequired
	 */
	public function groupAddAdminUser( string $user_entry_id, string $group_entry_id ) {
		// check if the user is allowed to edit this group
		if( !$this->userCanEdit( $group_entry_id ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// run sql query
		$sql = "INSERT INTO *PREFIX*ldaporg_group_admins SET group_id = ?, admin_id = ?";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->bindParam( 2, $user_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check for sql errors
		if( $stmt->errorCode() === '00000' ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User is now an admin' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Making user admin failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * removes the admin privileges for the given group from the given use
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 * @NoAdminRequired
	 */
	public function groupRemoveAdminUser( string $user_entry_id, string $group_entry_id ) {
		// check if the user is allowed to edit this group
		if( $user_entry_id !== $this->getOwnEntryId() && !$this->userCanEdit( $group_entry_id ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// run sql query
		$sql = "DELETE FROM *PREFIX*ldaporg_group_admins WHERE group_id = ? AND admin_id = ?";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->bindParam( 2, $user_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check for sql errors
		if( $stmt->errorCode() === '00000' ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User is not an admin anymore' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing admin privileges failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * check if the current user is allowed to edit the given group
	 * 
	 * @param string group_entry_id
	 * @NoAdminRequired
	 */
	public function canEdit( string $group_entry_id ) {
		return new DataResponse( [ 'data' => $this->userCanEdit( $group_entry_id ), 'status' => 'success' ] );
	}
	
	/**
	 * checks if the current or given user are allowed to edit the given group
	 * 
	 * @param string $group_entry_id
	 * @param string $user_entry_id
	 */
	protected function userCanEdit( string $group_entry_id, string $user_entry_id=NULL ) {
		// check if we have to get the current users entry id
		if( !$user_entry_id ) $user_entry_id = $this->getOwnEntryId();
		
		// check if the user is an admin for this group
		$sql = "SELECT * FROM *PREFIX*ldaporg_group_admins WHERE group_id = ? AND admin_id = ?";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->bindParam( 2, $user_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		if( $stmt->fetch() ) return true;
		
		// check if the user is a superadmin
		if( $this->isSuperUser( $user_entry_id, false ) ) return true;
		
		// the user is not allowed to edit this group
		return false;
	}
	
	/**
	 * adds the given user to the given group if the current user is allowed to do that
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 * @NoAdminRequired
	 */
	public function addUserToGroup( string $user_entry_id, string $group_entry_id ) {
		// let the helper function handle the actual work
		$return = $this->addUserToGroupHelper( $user_entry_id, $group_entry_id );
		
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully added' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Adding the user failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->addUserToGroup( string $user_entry_id, string $group_entry_id )
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 * @param bool $ignore_permissions
	 */
	private function addUserToGroupHelper( string $user_entry_id, string $group_entry_id, bool $ignore_permissions=false ) {
		// check if the user is allowed to edit this group
		if( !$ignore_permissions && !$this->userCanEdit( $group_entry_id ) ) return false;
		
		// get parameters
		$user_group_id_group_attribute = $this->settings->getSetting( 'user_group_id_group_attribute', false );
		$user_group_id_attribute = $this->settings->getSetting( 'user_group_id_attribute', false );
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		
		// get the users LDAP data
		$user = $this->getLdapEntryById( $user_entry_id );
		$user_group_id = is_array( $user[ $user_group_id_attribute ] ) ? $user[ $user_group_id_attribute ][0] : $user[ $user_group_id_attribute ];
		
		// get the current group members
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(' . $entry_id_attribute . '=' . ldap_escape ( $group_entry_id ) . '))', [ $user_group_id_group_attribute ] );
		$result = ldap_get_entries( $this->connection, $request );
		
		// check if the group was found
		if( $result['count'] !== 1 ) return false;
		$group = $result[0];
		
		// if there were no members yet, create a new entry
		if( !isset( $group[ $user_group_id_group_attribute ] ) ) {
			$group[ $user_group_id_group_attribute ] = [];
		}
		
		// remove the count variable
		unset( $group[ $user_group_id_group_attribute ]['count'] );
		
		// check if the user is already a member of this group
		foreach( $group[ $user_group_id_group_attribute ] as $member ) {
			// if the user is already a member of this group, we don't have to add him again
			if( $member === $user_group_id ) return true;
		}
		
		// add the given user as a member of this group
		array_push( $group[ $user_group_id_group_attribute ], $user_group_id );
		
		// save changes to the group
		return ldap_modify( $this->connection, $group['dn'], [ $user_group_id_group_attribute  => $group[ $user_group_id_group_attribute ]  ] );
	}
	
	/**
	 * removes the given user from the given group if the current user is allowed to do that
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 * @NoAdminRequired
	 */
	public function removeUserFromGroup( string $user_entry_id, string $group_entry_id ) {
		// let the helper function handle the actual work
		$return = $this->removeUserFromGroupHelper( $user_entry_id, $group_entry_id );
		
		// if the helper function already created a DataResponse, return it
		if( is_a( $return, 'OCP\AppFramework\Http\DataResponse' ) ) return $return;
		
		// check which type of message should be shown
		if( $user_entry_id === $this->getOwnEntryId() ) {
			// check if the request was a success or not
			if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'You are not a member of the group anymore' ) ), 'status' => 'success' ) );
			else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Leaving the group failed' ) ), 'status' => 'error' ) );
		}
		else {
			// check if the request was a success or not
			if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully removed' ) ), 'status' => 'success' ) );
			else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the user failed' ) ), 'status' => 'error' ) );
		}
	}
	
	/**
	 * helper function for $this->removeUserFromGroup( string $user_entry_id, string $group_entry_id )
	 * 
	 * @param string $user_entry_id
	 * @param string $group_entry_id
	 */
	protected function removeUserFromGroupHelper( string $user_entry_id, string $group_entry_id ) {
		// check if the user is allowed to edit this group or wants to remove himself
		if( $user_entry_id !== $this->getOwnEntryId() && !$this->userCanEdit( $group_entry_id ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// the user can't be removed, if this is a forced group
		if( $this->isForcedGroup( $group_entry_id ) ) {
			// check which message to show
			if( $user_entry_id !== $this->getOwnEntryId() ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing users from this group is not possible' ) ), 'status' => 'error' ) );
			else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Leaving this group is not possible' ) ), 'status' => 'error' ) );
		}
		
		// remove possible admin privileges from the user
		$remove_admin_user = $this->groupRemoveAdminUser( $user_entry_id, $group_entry_id )->getData();
		
		if( $remove_admin_user['status'] !== 'success' ) return false;
		
		// get parameters
		$user_group_id_group_attribute = $this->settings->getSetting( 'user_group_id_group_attribute', false );
		$user_group_id_attribute = $this->settings->getSetting( 'user_group_id_attribute', false );
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		
		// get the users ldap data
		$user = $this->getLdapEntryById( $user_entry_id );
		$user_group_id = is_array( $user[ $user_group_id_attribute ] ) ? $user[ $user_group_id_attribute ][0] : $user[ $user_group_id_attribute ];
		
		// get the current group members
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(' . $entry_id_attribute . '=' . ldap_escape ( $group_entry_id ) . '))', [ $user_group_id_group_attribute ] );
		$result = ldap_get_entries( $this->connection, $request );
		
		// check if the group was found
		if( $result['count'] !== 1 ) return false;
		$group = $result[0];
		
		// if the group has no members, we are done here
		if( !isset( $group[ $user_group_id_group_attribute ] ) || $group[ $user_group_id_group_attribute ]['count'] === 0 ) return true;
		
		// remove the count variable
		unset( $group[ $user_group_id_group_attribute ]['count'] );
		
		// go through the groups members and look for the user
		foreach( $group[ $user_group_id_group_attribute ] as $id => $member ) {
			// if this is the user, remove him
			if( $member === $user_group_id ) {
				unset( $group[ $user_group_id_group_attribute ][ $id ] );
				break;
			}
		}
		
		// reorder the array
		$group[ $user_group_id_group_attribute ] = array_values( $group[ $user_group_id_group_attribute ] );
		
		// save changes to the group
		return ldap_modify( $this->connection, $group['dn'], [ $user_group_id_group_attribute  => $group[ $user_group_id_group_attribute ]  ] );
	}
	
	/**
	 * checks if the current user is a super user
	 * 
	 * @param string $user_entry_id
	 * @param bool $DataResponse
	 * @NoAdminRequired
	 */
	public function isSuperUser( string $user_entry_id=NULL, bool $DataResponse=true ) {
		$superuser = false;
		// check if the current user should be used
		if( !$user_entry_id ) $user_entry_id = $this->getOwnEntryId();
		// check if the user is an LDAP user
		if( $user_entry_id ) {
			// get all superuser groups
			$superuser_groups = $this->ldaporg_settings->getSetting( 'superuser_groups' );
			// get the users ldap data
			$user = $this->getLdapEntryById( $user_entry_id );
			// get the users attribute to associate him with a group
			$user_group_id_attribute = $user[ $this->settings->getSetting( 'user_group_id_attribute', false ) ];
			if( is_array( $user_group_id_attribute ) ) $user_group_id_attribute = $user_group_id_attribute[0];
			// get all groups the user is a member of
			$user_groups = $this->getGroups( $user_group_id_attribute, true );

			// reorder the users groups
			$tmp = [];
			foreach( $user_groups as $group ) {
				$tmp[ $group['ldapcontacts_entry_id'] ] = $group;
			}
			$user_groups = $tmp;

			// check if the user is in a superuser group
			foreach( $superuser_groups as $group ) {
				if( isset( $user_groups[ $group ] ) ) {
					$superuser = true;
					break;
				}
			}
		}
		
		// return info
		if( $DataResponse ) return new DataResponse( [ 'data' => $superuser, 'status' => 'success' ] );
		else return $superuser;
	}
	
	/**
	 * creates a new group if the user is allowed to do this
	 * 
	 * @param string $group_name
	 * @NoAdminRequired
	 */
	public function createGroup( string $group_name ) {
		// check if the user is allowed to add group
		if( !$this->isSuperUser( $this->getOwnEntryId() ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		// remove spaces from group_name
		$group_name = trim( $group_name );
		// the group_name can't be empty
		if( empty( $group_name ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( "Group name can't be empty" ) ), 'status' => 'error' ) );
		
		// check if there is already a group with the same name
		$group_name_attribute = $this->group_display_name;
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(' . $group_name_attribute . '=' . ldap_escape ( $group_name ) . '))' );
		$result = ldap_get_entries( $this->connection, $request );
		if( $result['count'] !== 0 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'A group with the same name already exists' ) ), 'status' => 'error' ) );
		
		// get the highest current gidNumber
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(gidnumber=*))', [ 'gidnumber' ] );
		$result = ldap_get_entries( $this->connection, $request );
		
		// if there isn't a gidnumber given yet, start counting at 500
		if( $result['count'] < 1 ) $result[0]['gidnumber'][0] = 500;
		unset( $result['count'] );
		// sort the array by gidNumber
		usort( $result, function( $a, $b ) {
			return $b['gidnumber'][0] - $a['gidnumber'][0];
		});
		// get the new gidnumber
		$gidnumber = ++$result[0]['gidnumber'][0];
		
		// generate the groups array
		$group[ $group_name_attribute ] = $group_name;
		$group['gidnumber'] = $gidnumber;
		$group['objectclass'] = array( 'posixgroup', 'top' );
		
		// add the group to the server
		$request = ldap_add( $this->connection, $group_name_attribute . '=' . $group_name . ',' . $this->group_dn, $group );
		
		// get the newly created group
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(gidnumber=' . ldap_escape( $gidnumber ) . '))', [ $entry_id_attribute ] );
		$result = ldap_get_entries( $this->connection, $request )[0];
		
		// get the groups entry id
		$entry_id = is_array( $result[ $entry_id_attribute ] ) ? $result[ $entry_id_attribute ][0] : $result[ $entry_id_attribute ];
		
		// check if the request was a success or not
		if( $entry_id ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully created' ) ), 'status' => 'success', 'entry_id' => $entry_id ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Creating the group failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * deletes a group from the server
	 * 
	 * @param string $group_entry_id
	 * @NoAdminRequired
	 */
	public function deleteGroup( string $group_entry_id ) {
		// check if the user is allowed to add group
		if( !$this->isSuperUser( $this->getOwnEntryId() ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// a superuser group can't be deleted
		$superuser_groups = $this->ldaporg_settings->getSetting( 'superuser_groups' );
		if( in_array( $group_entry_id, $superuser_groups ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( "A superuser group can't be deleted" ) ), 'status' => 'error' ) );
		
		// get the groups dn
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(' . $this->settings->getSetting( 'entry_id_attribute', false ) . '=' . ldap_escape( $group_entry_id ) . '))', [ 'dn' ] );
		$result = ldap_get_entries( $this->connection, $request );
		
		// check if the group was found
		if( $result['count'] !== 1 || !isset( $result[0]['dn'] ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the group failed' ) ), 'status' => 'error' ) );
		
		// remove the group from the server
		$request = ldap_delete( $this->connection, $result[0]['dn'] );
		
		// remove possible admin associations
		$sql = 'DELETE FROM *PREFIX*ldaporg_group_admins WHERE group_id = ?';
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// remove the database entry if the group was hidden
		$sql = "DELETE FROM *PREFIX*ldapcontacts_hidden_entries WHERE entry_id = ? AND type='group'";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $group_entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully removed' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the group failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * deletes a user
	 * 
	 * @param string $user_entry_id
	 */
	public function deleteUser( string $user_entry_id ) {
		// let the helper function handle the actual work
		$request = $this->deleteUserHelper( $user_entry_id );
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully deleted' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Deleting the user failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->deleteUser( $user );
	 * 
	 * @param string $user_entry_id
	 */
	private function deleteUserHelper( string $user_entry_id ) {
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		// get the users dn
		$request = ldap_search( $this->connection, $this->user_dn, '(&' . $this->user_filter . '(' . $entry_id_attribute . '=' . ldap_escape( $user_entry_id ) . '))', [ 'dn', $entry_id_attribute ] );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the user was found
		if( $result['count'] !== 1 || !isset( $result[0]['dn'] ) ) return false;
		
		// get all the users groups
		if( !$groups = $this->getGroups( $user_entry_id, true ) ) {
			// go through every group and remove the user as a member
			foreach( $groups as $group ) {
				$group_entry_id = is_array( $group[ $entry_id_attribute ] ) ? $group[ $entry_id_attribute ][0] : $group[ $entry_id_attribute ];
				if( !$this->removeUserFromGroupHelper( $user_entry_id, $group_entry_id ) ) return false;
			}
		}
		
		// delete the user
		return ldap_delete( $this->connection, $result[0]['dn'] );
	}
	
	/**
	 * creates a user
	 * 
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $mail
	 */
	public function createUser( string $firstname, string $lastname, string $mail ) {
		$firstname = trim( $firstname );
		$lastname = trim( $lastname );
		$name = $firstname . ' ' . $lastname;
		$mail = trim( $mail );
		// none of the values can be empty
		if( empty( $firstname ) || empty( $lastname ) || empty( $mail ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'No value can be empty' ) ), 'status' => 'error' ) );
		// values can't be longer that 100 characters
		if( strlen( $firstname ) > 100 || strlen( $lastname ) > 100 || strlen( $mail ) > 100 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'No value can be longer than 100 characters' ) ), 'status' => 'error' ) );
		
		// get parameters
		$mail_attribute = $this->ldaporg_settings->getSetting( 'mail_attribute' );
		$firstname_attribute = $this->ldaporg_settings->getSetting( 'firstname_attribute' );
		$lastname_attribtue = $this->ldaporg_settings->getSetting( 'lastname_attribtue' );
		$name_attribute = $this->user_display_name;
		
		// check if there is already an account with the same email
		$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(' . $mail_attribute . '=' . ldap_escape( $mail ) . '))' );
		$result = ldap_get_entries( $this->connection, $request );
		// there can't be two users with the same email adress
		if( $result['count'] !== 0 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Another user with the same email adress already exists' ) ), 'status' => 'error' ) );
		
		/** generate all the users data **/
			$user = array();
			
			/* mail */
				$user[ $mail_attribute ] = $mail;
			
			/* firstname */
				$user[ $firstname_attribute ] = $firstname;
			
			/* lastname */
				$user[ $lastname_attribtue ] = $lastname;
			
			/* name */
				$user[ $name_attribute ] = $name_orig = $firstname . ' ' . $lastname;
				$i = 1;
				// if there is another user with the same cn, add an increasing number at the end of the cn
				while( true ) {
					// check if there is someone with the same name
					$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(' . $name_attribute . '=' . ldap_escape( $user[ $name_attribute ] ) . '))', [ $name_attribute ] );
					$result = ldap_get_entries( $this->connection, $request );
					
					// no user with this name exists
					if( $result['count'] === 0 ) break;
					// try another name
					else $user[ $name_attribute ] = $name_orig . $i++;
				}
			
			/* uid */
				$firstname_uid = preg_replace( "/[^a-zA-Z]+/", "", $firstname );
				$lastname_uid = preg_replace( "/[^a-zA-Z]+/", "", $lastname );
				$user['uid'] = $uid_orig = substr( strtolower( $firstname_uid ), 0, 2 ) . strtolower( $lastname_uid );
				// the uid can't be empty
				if( empty( $user['uid'] ) ) $user['uid'] = $uid_orig = 'default';
				$i = 1;
				// if there is another user with the same uid, add an increasing number at the end of the uid
				while( true ) {
					// check if there is still someone with the same uid
					$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(uid=' . $user['uid'] . '))', array( 'uid' ) );
					$result = ldap_get_entries( $this->connection, $request );
					
					// no user with this uid exists
					if( $result['count'] === 0 ) break;
					// try another uid
					else $user['uid'] = $uid_orig . $i++;
				}
			
			/* objectclass */
				$user['objectclass'] = [ 'inetOrgPerson', 'top' ];
		
			/* userpassword */
				list( $usec, $sec ) = explode( ' ', microtime() );
				mt_srand( $sec + $usec * 999999 );
				$salt = pack( 'CCCC', mt_rand(), mt_rand(), mt_rand(), mt_rand() );
				$user['userpassword'] = '{SSHA}' . base64_encode( pack( 'H*', sha1( strtolower( $firstname ) . $salt ) ) . $salt );
		
		// create the user
		$dn = $name_attribute . '=' . $user[ $name_attribute] . ',' . $this->user_dn;
		$request = ldap_add( $this->connection, $dn, $user );
		
		if( $request ) {
			// get the users entry id
			$user_entry_id = $this->getEntryLdapId( $dn );
			
			// if user was created successfully, send him a welcome mail
            $this->sendWelcomeMail( $user[ $mail_attribute ], $user[ $name_attribute ], false );
			
			// add the user to all forced membership groups
			$forced_groups = $this->ldaporg_settings->getSetting( 'forced_group_memberships' );
			foreach( $forced_groups as $group_entry_id ) {
				if( empty( $group_entry_id ) ) continue;
				$this->addUserToGroupHelper( $user_entry_id, $group_entry_id, true );
			}
		}
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully created' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Creating user failed' ) ), 'status' => 'error' ) );
	}
	
	/*
	 * send the welcome mail to the given mail address
	 *
	 * @param string $mail
	 * @param string $name
	 */
	public function sendWelcomeMail( string $mail, string $name, bool $DataResponse=true ) {
		// check if the mail address is valid
		if( !filter_var( $mail, FILTER_VALIDATE_EMAIL ) ) return false;
		
		// fetch properties
		$welcome_mail_message = $this->ldaporg_settings->getSetting( 'welcome_mail_message' );
		$mail_attribute = $this->ldaporg_settings->getSetting( 'mail_attribute' );
		$name_attribute = $this->user_display_name;
		
		// check if password reset is active
		if( $this->ldaporg_settings->getSetting( 'pwd_reset_url_active' ) ) {
			// get the request url
			if( !empty( $get_link = $this->ldaporg_settings->getSetting( 'pwd_reset_url' ) ) && !empty( $get_attr = $this->ldaporg_settings->getSetting( 'pwd_reset_url_attr' ) ) && !empty( $get_attr_ldap_attr = $this->ldaporg_settings->getSetting( 'pwd_reset_url_attr_ldap_attr' ) ) ) {
				$custom_pwd_reset_link = $get_link . '&' . $get_attr . '=' . $user[ $get_attr_ldap_attr ];
				// replace tag with custom reset link
				$welcome_mail_message = str_replace( $this->ldaporg_settings->getSetting( 'pwd_reset_tag' ), $custom_pwd_reset_link, $welcome_mail_message );
			}
		}
		
		// send the mail
		$mailer = \OC::$server->getMailer();
		$message = $mailer->createMessage();
		$message->setSubject( $this->ldaporg_settings->getSetting( 'welcome_mail_subject' ) );
		$message->setFrom( [ $this->ldaporg_settings->getSetting( 'welcome_mail_from_adress' ) => $this->ldaporg_settings->getSetting( 'welcome_mail_from_name' ) ] );
		$message->setTo( [ $mail => $name ] );
		$message->setHtmlBody( $welcome_mail_message );
		$status = $mailer->send( $message );
		
		// return message
		if( $DataResponse ) {
			if( $status ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Welcome Mail has been send' ) ), 'status' => 'success' ) );
			else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Sending the welcome mail failed' ) ), 'status' => 'error' ) );
		}
		else return $status;
	}
	
	/**
	 * load all groups
	 */
	public function adminLoadGroups() {
		return new DataResponse( $this->getGroups( false, true ) );
	}
	
	/**
	 * load all users
	 */
	public function adminLoadUsers() {
		return new DataResponse( $this->getUsers( false, true ) );
	}
	
	/**
	 * apply forced group memberships to all users
	 */
	public function applyForcedGroupMemberships() {
		// get all users
		$users = $this->getUsers( false, true );
		// get all forced groups
		$forced_groups = $this->ldaporg_settings->getSetting( 'forced_group_memberships' );
		
		// add all users to every forced group
		$error = 0;
		foreach( $forced_groups as $group_entry_id ) {
			if( empty( $group_entry_id ) ) continue;
			foreach( $users as $user ) {
				if( empty( $user['ldapcontacts_entry_id'] ) ) continue;
				$error |= !$this->addUserToGroupHelper( $user['ldapcontacts_entry_id'], $group_entry_id );
			}
		}
		
		// return message
		if( $error ) {
			// something went wrong
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Applying forced group memberships failed' ) ), 'status' => 'error' ) );
		}
		else {
			// everything went fine
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Applied forced group memberships' ) ), 'status' => 'success' ) );
		}
	}
	
	/**
	 * checks if the given group has forced membership
	 * 
	 * @param string $group_entry_id
	 */
	protected function isForcedGroup( string $group_entry_id ) {
		$forced_groups = $this->ldaporg_settings->getSetting( 'forced_group_memberships' );
		return array_search( $group_entry_id, $forced_groups ) !== false;
	}
	
	/**
	 * exports the details for all members of the given group
	 * 
	 * @param string $group_entry_id
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function exportGroupMemberDetails( string $group_entry_id ) {
		// get all groups the user has access to
		$groups = $this->loadGroups()->getData()['data'];
		
		$given_group = false;
		// check if the given group is there
		foreach( $groups as $group ) {
			if( $group['ldapcontacts_entry_id'] === $group_entry_id ) {
				$given_group = $group;
				break;
			}
		}
		
		// make sure the user has access to the group
		if( !$given_group ) {
			echo '<h2>' . $this->l2->t( 'Group not found or permission denied' ) . '</h2>';
			return false;
		}
		
		// get all available user attributes
		$user_attributes = $this->settings->getSetting( 'user_ldap_attributes', false );
		// create file buffer
		$file_content = [];
		
		// add header line
		$line = [];
		foreach( $user_attributes as $key => $label ) {
			array_push( $line, $label );
		}
		array_push( $file_content, $line );
		
		// add a line for every member
		foreach( $given_group['ldaporg_members'] as $member ) {
			$line = [];
			foreach( $user_attributes as $key => $label ) {
				array_push( $line, @$member[ $key ] );
			}
			array_push( $file_content, $line );
		}
		
		// get the groups name
		$group_name = $given_group[ $this->user_display_name ];
		if( is_array( $group_name ) ) $group_name = $group_name[0];
		
		// write file header
		header( "Content-type: text/csv" );
		header( "Content-Disposition: attachment; filename=" . $group_name . ".csv" );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		
		// output the file
		$file = fopen( "php://output", 'w' );
		// write each line
		foreach( $file_content as $line ) {
			fputcsv( $file, $line, $this->ldaporg_settings->getSetting( 'csv_seperator' ) );
		}
		exit;
	}
}