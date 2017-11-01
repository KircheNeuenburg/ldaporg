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
use OCA\LdapContacts\Controller\ContactController;

Class PageController extends ContactController {
	// settings controller
	protected $settings;
	// mail handler
	protected $mailer;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig
	 * @param SettingsController	
	 */
	public function __construct( $AppName, IRequest $request, IConfig $config, SettingsController $settings, IMailer $mailer, Il10n $l10n, $UserId ) {
		parent::__construct( $AppName, $request, $config, $UserId );
		// translation
		$this->l2 = $l10n;
		// save the settings controller
		$this->settings = $settings;
		// load additional configuration
		$this->load_config();
		// save the mail handler
		$this->mailer = $mailer;
	}

	/**
	 * modifies configurations made in the parent ContactController from the LdapContacts App
	 * 
	 * @param string $prefix
	 */
	private function load_config() {
		// load configuration
		$ldapWrapper = new \OCA\User_LDAP\LDAP();
		$connection = new \OCA\User_LDAP\Connection( $ldapWrapper );
		$config = $connection->getConfiguration();
		// check if this is the correct server of if we have to use a prefix
		if( empty( $config['ldap_host'] ) ) {
			$connection = new \OCA\User_LDAP\Connection( $ldapWrapper, 's01' );
			$config = $connection->getConfiguration();
		}
		
		// put the needed configuration in the local variables
		$this->user_filter =  $config['ldap_userlist_filter'];
		$this->group_filter = $config['ldap_group_filter'];
		$this->group_member_assoc_attr = $config['ldap_group_member_assoc_attribute'];
		$this->group_admin_filter = '(x-kircheneuenburg-adminuid=%uid)';
		$this->group_admin_attribute = 'x-kircheneuenburg-adminuid';
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
		// get the users attribute used to associate with the groups
		$assoc_attr = $this->get_group_assoc_attribute( $this->mail );
		
		// check if the user is in the group that can edit anything
		$request = ldap_list( $this->connection, $this->group_dn, '(&' . str_replace( '%gid', $this->settings->getSetting( 'superuser_group_id' ), $this->group_filter_specific ) . '(' . $this->group_member_assoc_attr . '=' . $assoc_attr . '))' );
		$result = ldap_get_entries( $this->connection, $request );
		
		// if the user is a super user, get all groups
		if( $result['count'] != 0 )
			$groups = $this->get_groups( $this->group_filter );
		// otherwise only get the groups the user is a member of
		else
			$groups = $this->get_groups( '(&' . $this->group_filter . '(' . $this->group_member_assoc_attr . '=' . $assoc_attr . '))' );
		
		/* add all members to the group */
			// get all existing users
			$users = $this->get_users( $this->user_filter );
			// go through every group and get it's members
			foreach( $groups as &$group ) {
				// get all admins for the curent group
				$admins = $this->getGroupAdmins( $group['id'] );
				$members = array();
				// go through every existing user and add them if they are in the group
				foreach( $users as $user ) {
					// check every group if it is this group
					array_walk( $user['groups'], function( $value, $k ) use ( $group, $user, &$members, $admins ) {
						if( $value['id'] == $group['id'] ) {
							
							// check if this is one of the admins
							array_walk( $admins, function( $value, $key ) use ( &$user ) {
								// if the user was found in the groups admins, add his info and end the search
								if( $value == $user['uid'] ) {
									$user['isadmin'] = true;
									return;
								}
							});
							
							array_push( $members, $user );
							return;
						}
					});
				}
				// add the members to the group
				$group['members'] = $members;
			}
		// return the groups
		return new DataResponse( $groups );
	}
	
	/**
	 * gets all admins from the given group
	 * 
	 * @param string $group_id
	 */
	protected function getGroupAdmins( $group_id ) {
		// get all admin usernames from the group
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( $this->group_admin_attribute ) );
		$result = ldap_get_entries( $this->connection, $request );
		
		// check if request was successful and  the required values are given
		if( $result['count'] < 1 || !isset( $result[0][ $this->group_admin_attribute ] ) ) return array();
		// remove cout variable
		unset( $result[0][ $this->group_admin_attribute ]['count'] );
		
		// return all fetched admin group member assoc attributes
		return $result[0][ $this->group_admin_attribute ];
	}
	
	/**
	 * gets the user attribute associated with the groups
	 * 
	 * @param $uid		the users id
	 */
	private function get_group_assoc_attribute( $uid ) {
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ), array( $this->uname_property ) );
		$entries = ldap_get_entries( $this->connection, $request );
		// check if the request was successful
		if( $entries['count'] < 1 ) return false;
		else return $entries[0][ $this->uname_property ][0];
	}
	
	/**
	 * returns all existing users
	 * 
	 * @NoAdminRequired
	 */
	public function loadUsers() {
		$result = $this->get_users( $this->user_filter );
		// return the groups
		return new DataResponse( $result );
	}
	
	/**
	 * sets the given user as an admin in the given group, if the current user is allowed to do this
	 * 
	 * @param array $user
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function addAdminUser( $user, $group ) {
		// check if the user is allowed to edit this group
		if( !$this->userCanEdit( $group['id'] ) )return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// let the helper function handle the actual work
		$return = $this->addAdminUserHelper( $user['mail'], $group['id'] );
		
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User is now an admin' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Making user admin failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->addAdminUser( $user, $group )
	 * 
	 * @param string $user_id
	 * @param string $group_id
	 */
	private function addAdminUserHelper( $user_id, $group_id ) {
		// first add the user to the group as a normal member
		if( !$this->addUserHelper( $user_id, $group_id ) ) return false;
		// make sure the group is configured correctly
		if( !$this->fixConfig( $group_id ) ) return false;
		
		// get the users group identifier
		$uname = $this->get_uname( $user_id );
		// get the current group admins
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( $this->group_admin_attribute ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the group was found
		if( $result['count'] != 1 ) return false;
		$group = $result[0];
		
		// if there were no admins yet, create a new entry
		if( !isset( $group[ $this->group_admin_attribute ] ) ) {
			$group[ $this->group_admin_attribute ] = array();
		}
		
		// remove the count variable
		unset( $group[ $this->group_admin_attribute ]['count'] );
		
		// check if the user is already an admin in this group
		foreach( $group[ $this->group_admin_attribute ] as $key => $user ) {
			// if the user is already an admin in this group, we don't have to add him again
			if( $user == $uname ) return true;
		}
		
		// add the given user to the admins of this group
		array_push( $group[ $this->group_admin_attribute ], $uname );
		
		// save changes to the group
		return ldap_modify( $this->connection, $group['dn'], array( $this->group_admin_attribute => $group[ $this->group_admin_attribute ] ) );
	}
	
	/**
	 * removes the admin privileges for the given group from the given use
	 * 
	 * @param array $user
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function removeAdminUser( $user, $group ) {
		// check if the user is allowed to edit this group
		if( !$this->userCanEdit( $group['id'] ) )return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// let the helper function handle the actual work
		$return = $this->removeAdminUserHelper( $user['mail'], $group['id'] );
		
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User is not an admin anymore' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing admin privileges failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->removeAdminUser( $user, $group )
	 * 
	 * @param string $user_id
	 * @param string $group_id
	 */
	private function removeAdminUserHelper( $user_id, $group_id ) {
		// get the users group identifier
		$uname = $this->get_uname( $user_id );
		// get the current groups admins
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( $this->group_admin_attribute ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the group was found
		if( $result['count'] != 1 ) return false;
		$group = $result[0];
		
		// if there are no admins, we are done
		if( !isset( $group[ $this->group_admin_attribute ] ) ) return true;
		
		// remove the count variable
		unset( $group[ $this->group_admin_attribute ]['count'] );
		
		// check if the user is an admin and remove him
		$removed = false;
		foreach( $group[ $this->group_admin_attribute ] as $key => $user ) {
			// if the user was found, remove him and end the search
			if( $user == $uname ) {
				$removed = true;
				unset( $group[ $this->group_admin_attribute ][ $key ] );
				break;
			}
		}
		
		// save changes to the group, if there were any
		if( $removed ) {
			$admins = array_values( $group[ $this->group_admin_attribute ] );
			return ldap_modify( $this->connection, $group['dn'], array( $this->group_admin_attribute => $admins ) );
		}
		else
			return true;
	}
	
	/**
	 * changes the groups configuration to work with this App
	 * 
	 * @param string $group_id
	 */
	protected function fixConfig( $group_id ) {
		// get all required attributes from the group
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( 'objectclass' ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the group was found
		if( $result['count'] != 1 ) return false;
		$group = $result[0];
		
		// buffer for data that has to be replaced
		$data = array();
		
		// go through all objectClasses and check if the orgGroup is set
		$given = false;
		foreach( $group['objectclass'] as $class ) {
			if( $class == 'x-kircheneuenburg-orgGroup' ) {
				$given = true;
				break;
			}
		}
		// if the orgGroup objectClass was not found, add it
		if( !$given ) {
			array_push( $group['objectclass'], 'x-kircheneuenburg-orgGroup' );
			// remove the count variable
			unset( $group['objectclass']['count'] );
			// add the objectClasses to tha data array
			$data['objectclass'] = $group['objectclass'];
		}
		
		// check if there is something to fix
		if( empty( $data ) ) return true;
		// fix the group
		else return ldap_modify( $this->connection, $group['dn'], $data );
	}
	
	/**
	 * check if the current user is allowed to edit the given group
	 * 
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function canEdit( $group ) {
		return new DataResponse( $this->userCanEdit( $group['id'] ) );
	}
	
	/**
	 * checks if the current or given user are allowed to edit the given group
	 * 
	 * @param string $group_id
	 * @param string $user_id
	 */
	protected function userCanEdit( $group_id, $user_id = false ) {
		// if no user is given, use the current user for checking
		if( !$user_id ) $user_id = $this->mail;
		
		// check if the user is in the group that can do edit anything
		$request = ldap_list( $this->connection, $this->group_dn, '(&' . str_replace( '%gid', $this->settings->getSetting( 'superuser_group_id' ), $this->group_filter_specific ) . '(' . $this->group_member_assoc_attr . '=' . $this->get_group_assoc_attribute( $user_id ) . '))' );
		$result = ldap_get_entries( $this->connection, $request );
		// if the user is a superuser, he can edit
		if( $result['count'] != 0 ) return true;
		
		// check if the user is a group admin
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . str_replace( '%gid', $group_id, $this->group_filter_specific ) . str_replace( '%uid', $this->get_group_assoc_attribute( $user_id ), $this->group_admin_filter ) . ')' );
		$result = ldap_get_entries( $this->connection, $request );
		
		// if the user is an admin of this group, he can edit
		if( $result['count'] != 0 ) return true;
		return false;
	}
	
	/**
	 * adds the given user to the given group if the current user is allowed to do that
	 * 
	 * @param array $user
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function addUser( $user, $group ) {
		// check if the user is allowed to edit this group
		if( !$this->userCanEdit( $group['id'] ) )return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// let the helper function handle the actual work
		$return = $this->addUserHelper( $user['mail'], $group['id'] );
		
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully added' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Adding the user failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->addUser( $user, $group )
	 * 
	 * @param string $user_id
	 * @param string $group_id
	 */
	private function addUserHelper( $user_id, $group_id ) {
		// get the users group identifier
		$uname = $this->get_uname( $user_id );
		// get the current group members
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( 'memberuid' ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the group was found
		if( $result['count'] != 1 ) return false;
		$group = $result[0];
		
		// if there were no members yet, create a new entry
		if( !isset( $group['memberuid'] ) ) {
			$group['memberuid'] = array();
		}
		
		// remove the count variable
		unset( $group['memberuid']['count'] );
		
		// check if the user is already a member of this group
		foreach( $group['memberuid'] as $user ) {
			// if the user is already a member of this group, we don't have to add him again
			if( $user == $uname ) return true;
		}
		
		// add the given user as a member of this group
		array_push( $group['memberuid'], $uname );
		
		// save changes to the group
		return ldap_modify( $this->connection, $group['dn'], array( 'memberuid' => $group['memberuid'] ) );
	}
	
	/**
	 * removes the given user from the given group if the current user is allowed to do that
	 * 
	 * @param array $user
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function removeUser( $user, $group ) {
		// check if the user is allowed to edit this group or wants to remove himself
		if( $user['mail'] != $this->mail && !$this->userCanEdit( $group['id'] ) )return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// the user can't be removed, if this is a forced group
		if( $this->isForcedGroup( $group['id'] ) ) {
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing users from this group is not possible' ) ), 'status' => 'error' ) );
		}
		
		// let the helper function handle the actual work
		$return = $this->removeUserHelper( $user['mail'], $group['id'] );
		
		// check which type of message should be shown
		if( $user['mail'] == $this->mail ) {
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
	 * helper function for $this->removeUser( $user, $group )
	 * 
	 * @param string $user_id
	 * @param string $group_id
	 */
	private function removeUserHelper( $user_id, $group_id ) {
		// remove possible admin privileges from the user
		if( !$this->removeAdminUserHelper( $user_id, $group_id ) ) return false;
		
		// get the users group identifier
		$uname = $this->get_uname( $user_id );
		// get the current group members
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group_id, $this->group_filter_specific ), array( 'memberuid' ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the group was found
		if( $result['count'] != 1 ) return false;
		$group = $result[0];
		
		// if the group has no members, we are done here
		if( !isset( $group['memberuid'] ) ) return true;
		
		// remove the count variable
		unset( $group['memberuid']['count'] );
		
		// go through all group members and try to find the given user
		foreach( $group['memberuid'] as $key => $user ) {
			// if the user was found, remove him and end the search
			if( $user == $uname ) {
				unset( $group['memberuid'][ $key ] );
				break;
			}
		}
		
		// reorder the array
		$group['memberuid'] = array_values( $group['memberuid'] );
		// save changes to the group
		return ldap_modify( $this->connection, $group['dn'], array( 'memberuid' => $group['memberuid'] ) );
	}
	
	/**
	 * checks if the current user is a super user
	 * 
	 * @NoAdminRequired
	 */
	public function isSuperUser() {
		return new DataResponse( $this->userCanEdit( $this->settings->getSetting( 'superuser_group_id' ) ) );
	}
	
	/**
	 * creates a new group if the user is allowed to do this
	 * 
	 * @param string $group_name
	 * @NoAdminRequired
	 */
	public function addGroup( $group_name ) {
		// check if the user is allowed to add group
		if( !$this->userCanEdit( $this->settings->getSetting( 'superuser_group_id' ) ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		// remove spaces from group_name
		$group_name = trim( $group_name );
		// the group_name can't be empty
		if( empty( $group_name ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( "Group name can't be empty" ) ), 'status' => 'error' ) );
		
		// check if there is already a group with the same name
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(cn=' . $group_name . '))' );
		$result = ldap_get_entries( $this->connection, $request );
		if( $result['count'] != 0 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'A group with the same name already exists' ) ), 'status' => 'error' ) );
		
		// get the highest current gidNumber
		$request = ldap_search( $this->connection, $this->group_dn, '(&' . $this->group_filter . '(gidnumber=*))', array( 'gidnumber' ) );
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
		$group['cn'] = $group_name;
		$group['gidnumber'] = $gidnumber;
		$group['objectclass'] = array( 'posixgroup', 'top', 'x-kircheneuenburg-orgGroup' );
		
		// add the group to the server
		$request = ldap_add( $this->connection, 'cn=' . $group['cn'] . ',' . $this->group_dn, $group );
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully created' ) ), 'status' => 'success', 'gid' => $gidnumber ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Creating the group failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * deletes a group from the server
	 * 
	 * @param array $group
	 * @NoAdminRequired
	 */
	public function removeGroup( $group ) {
		// check if the user is allowed to add group
		if( !$this->userCanEdit( $this->settings->getSetting( 'superuser_group_id' ) ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Permission denied' ) ), 'status' => 'error' ) );
		
		// the superuser group can't be deleted
		if( $group['id'] == $this->settings->getSetting( 'superuser_group_id' ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( "The superuser group can't be deleted" ) ), 'status' => 'error' ) );
		
		// get the groups dn
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $group['id'], $this->group_filter_specific ), array( 'dn' ) );
		$result = ldap_get_entries( $this->connection, $request );
		
		// check if the group was found
		if( $result['count'] != 1 || !isset( $result[0]['dn'] ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the group failed' ) ), 'status' => 'error' ) );
		
		// remove the group from the server
		$request = ldap_delete( $this->connection, $result[0]['dn'] );
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully removed' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the group failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * deletes a user
	 * 
	 * @param array $user
	 */
	public function deleteUser( $user ) {
		// let the helper function do the actual work
		$request = $this->deleteUserHelper( $user['mail'] );
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully deleted' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Deleting the user failed' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * helper function for $this->deleteUser( $user );
	 * 
	 * @param string $user_id
	 */
	private function deleteUserHelper( $user_id ) {
		// get the users dn
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $user_id, $this->user_filter_specific ) );
		$result = ldap_get_entries( $this->connection, $request );
		// check if the user was found
		if( $result['count'] != 1 || !isset( $result[0]['dn'] ) ) return false;
		
		// get all existing groups
		if( !$groups = $this->get_groups( $this->group_filter ) ) return false;
		
		// go through every group and remove the user as a member
		foreach( $groups as $group ) {
			if( !$this->removeUserHelper( $user_id, $group['id'] ) ) return false;
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
	public function createUser( $firstname, $lastname, $mail ) {
		$firstname = trim( $firstname );
		$lastname = trim( $lastname );
		$mail = trim( $mail );
		// none of the values can be empty
		if( empty( $firstname ) || empty( $lastname ) || empty( $mail ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'No value can be empty' ) ), 'status' => 'error' ) );
		// values can't be longer that 100 characters
		if( strlen( $firstname ) > 100 || strlen( $lastname ) > 100 || strlen( $mail ) > 100 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'No value can be longer than 100 characters' ) ), 'status' => 'error' ) );
		
		// check if there is already an account with the same email
		$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(mail=' . $mail . '))', array( 'mail' ) );
		$result = ldap_get_entries( $this->connection, $request );
		// there can't be two users with the same email adress
		if( $result['count'] != 0 ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Another user with the same email adress already exists' ) ), 'status' => 'error' ) );
		
		/** generate all the users data **/
			$user = array();
			
			/* mail */
				$user['mail'] = $mail;
			
			/* givenname */
				$user['givenname'] = $firstname;
			
			/* sn */
				$user['sn'] = $lastname;
			
			/* cn */
				$user['cn'] = $cn_orig = $firstname . ' ' . $lastname;
				$i = 1;
				// add numbers at the end of the cn as long as there is another user with the same cn
				$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(cn=' . $user['cn'] . '))', array( 'cn' ) );
				$result = ldap_get_entries( $this->connection, $request );
				// if there is another user with the same cn, add an increasing number at the end of the cn
				while( $result['count'] != 0 ) {
					$user['cn'] = $cn_orig . $i++;
					// check if there is still someone with the same cn
					$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(cn=' . $user['cn'] . '))', array( 'cn' ) );
					$result = ldap_get_entries( $this->connection, $request );
				}
			
			/* uid */
				$firstname_uid = preg_replace( "/[^a-zA-Z]+/", "", $firstname );
				$lastname_uid = preg_replace( "/[^a-zA-Z]+/", "", $lastname );
				$user['uid'] = $uid_orig = substr( strtolower( $firstname_uid ), 0, 2 ) . strtolower( $lastname_uid );
				// the uid can't be empty
				if( empty( $user['uid'] ) ) $user['uid'] = $uid_orig = 'dummy_uid';
				$i = 1;
				// add numbers at the end of the uid as long as there is another user with the same uid
				$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(uid=' . $user['uid'] . '))', array( 'uid' ) );
				$result = ldap_get_entries( $this->connection, $request );
				// if there is another user with the same uid, add an increasing number at the end of the uid
				while( $result['count'] != 0 ) {
					$user['uid'] = $uid_orig . $i++;
					// check if there is still someone with the same uid
					$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(uid=' . $user['uid'] . '))', array( 'uid' ) );
					$result = ldap_get_entries( $this->connection, $request );
				}
			
			/* homedirectory */
				$user['homedirectory'] = '/home/' . $user['uid'];
			
			/* objectclass */
				$user['objectclass'] = array( 'inetOrgPerson', 'top', 'posixAccount' );
			
			/* uidnumber */
				// get all existing uidnumbers
				$request = ldap_search( $this->connection, $this->base_dn, '(&' . $this->user_filter . '(uidnumber=*))', array( 'uidnumber' ) );
				$result = ldap_get_entries( $this->connection, $request );
				// if no user exists yet, start counting at 1000
				if( $result['count'] < 1 ) $user['uidnumber'] = 1000;
				else {
					unset( $result['count'] );
					// sort the array by uidnumber
					usort( $result, function( $a, $b ) {
						return $b['uidnumber'][0] - $a['uidnumber'][0];
					});
					// get the new uidnumber
					$user['uidnumber'] = ++$result[0]['uidnumber'][0];
				}
			
			/* gidnumber */
				$user['gidnumber'] = $this->settings->getSetting( 'user_gidnumber' );
			
			/* userpassword */
				mt_srand( microtime() * 999999 );
				$salt = pack( 'CCCC', mt_rand(), mt_rand(), mt_rand(), mt_rand() );
				$user['userpassword'] = '{SSHA}' . base64_encode( pack( 'H*', sha1( strtolower( $firstname ) . $salt ) ) . $salt );
				
		// create the user
		$request = ldap_add( $this->connection, 'cn=' . $user['cn'] . ',' . $this->base_dn, $user );
		$request = true;
		
		if( $request ) {
			// if user was created successfully, send him a welcome mail
            $this->sendWelcomeMail( $user );

			// add the user to the default group
			$this->addUserHelper( $user['mail'], $this->settings->getSetting( 'user_gidnumber') );
			
			// add the user to all forced membership groups
			$forced_groups = $this->getForcedGroupMemberships();
			foreach( $forced_groups as $group_id ) {
				$this->addUserHelper( $user['mail'], $group_id );
			}
		}
		
		// check if the request was a success or not
		if( $request ) return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'User successfully created' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Creating user failed' ) ), 'status' => 'error' ) );
	}
	
	/*
	 * send the welcome mail to the given user
	 *
	 * @param array $user		the user the mail should be send to
	 */
	protected function sendWelcomeMail( $user ) {
		$welcome_mail_message = $this->settings->getSetting( 'welcome_mail_message' );

		// check if password reset is active
		if( $this->settings->getSetting( 'pwd_reset_url_active' ) ) {
			// get the request url
			if( !empty( $get_link = $this->settings->getSetting( 'pwd_reset_url' ) ) && !empty( $get_attr = $this->settings->getSetting( 'pwd_reset_url_attr' ) ) && !empty( $get_attr_ldap_attr = $this->settings->getSetting( 'pwd_reset_url_attr_ldap_attr' ) ) ) {
				$custom_pwd_reset_link = $get_link . '&' . $get_attr . '=' . $user[ $get_attr_ldap_attr ];
			}
			// replace tag with custom reset link
			$welcome_mail_message = str_replace( $this->settings->getSetting( 'pwd_reset_tag' ), $custom_pwd_reset_link, $welcome_mail_message );
		}

		$mailer = \OC::$server->getMailer();
		$message = $mailer->createMessage();
		$message->setSubject( $this->settings->getSetting( 'welcome_mail_subject' ) );
		$message->setFrom( array( $this->settings->getSetting( 'welcome_mail_from_adress' ) => $this->settings->getSetting( 'welcome_mail_from_name' ) ) );
		$message->setTo( array( $user['mail'] => $user['cn'] ) );
		$message->setHtmlBody( $welcome_mail_message );
		return !$mailer->send( $message );
	}
	
	/*
	 * same as $this->sendWelcomeMail, just adds a data response for ajax requests
	 * 
	 * @param array $user		the user the mail should be send to
	 */
	public function resendWelcomeMail( $user ) {
		if( $this->sendWelcomeMail( $user ) ) {
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Welcome Mail has been send' ) ), 'status' => 'success' ) );
		}
		else {
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Sending the welcome mail failed' ) ), 'status' => 'error' ) );
		}
	}
	
	/**
	 * get an array of all the groups the user is forced to be a member of
	 */
	protected function getForcedGroupMemberships() {
		// get the forced groups
		$forced_groups = $this->settings->getSetting( 'forced_group_memberships' );
		
		if( empty( $forced_groups ) ) {
			// no groups given
			return [];
		}
		else {
			// return groups as array
			return explode( ',', $this->settings->getSetting( 'forced_group_memberships' ) );
		}
	}
	
	/**
	 * update the list of forced group memberships
	 * 
	 * @param array $groups		an array of all forced group memberships
	 */
	protected function updateForcedGroupMemberships( $groups ) {
		$groups = implode( ',', $groups );
		return $this->settings->updateSetting( 'forced_group_memberships', $groups );
	}

	/**
	 * returns a list of all the groups the user is forced to be a member of
	 * 
	 * @NoAdminRequired
	 */
	public function loadForcedGroupMemberships() {
		return new DataResponse( array( 'data' => $this->getForcedGroupMemberships(), 'status' => 'success' ) );
	}
	
	/**
	 * adds a group to the list of groups the user is forced to be a member of
	 * 
	 * @param string $group_id
	 */
	public function addForcedGroupMembership( $group_id ) {
		// get the current forced groups
		$forced_groups = $this->getForcedGroupMemberships();
		// only add the group if it isn't in the list alredy
		if( !array_search( $group_id, $forced_groups ) ) {
			// add the group to the list
			array_push( $forced_groups, $group_id );
			// save the list
			if( !$this->updateForcedGroupMemberships( $forced_groups ) ) {
				// something went wrong
				return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Adding the group failed' ) ), 'status' => 'error' ) );
			}
		}
		
		// group was successfully added
		return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully added' ) ), 'status' => 'success' ) );
	}
	
	/**
	 * removes a group from the list of groups the user is forced to be a member of
	 * 
	 * @param string $group_id
	 */
	public function removeForcedGroupMembership( $group_id ) {
		// get the current forced groups
		$forced_groups = $this->getForcedGroupMemberships();
		// remove the given group from the list
		if( ( $key = array_search( $group_id, $forced_groups ) ) !== false ) {
			unset( $forced_groups[ $key ] );
		}
		// save the list
		if( $this->updateForcedGroupMemberships( $forced_groups ) ) {
			// group was successfully added
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Group successfully removed' ) ), 'status' => 'success' ) );
		}
		else {
			// something went wrong
			return new DataResponse( array( 'data' => array( 'message' => $this->l2->t( 'Removing the group failed' ) ), 'status' => 'error' ) );
		}
	}
	
	/**
	 * load all groups
	 */
	public function adminLoadGroups() {
		return new DataResponse( $this->get_groups( $this->group_filter ) );
	}
	
	/**
	 * apply forced group memberships to all users
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function applyForcedGroupMemberships() {
		// get all users
		$users = $this->get_users( $this->user_filter );
		// get all forced groups
		$forced_groups = $this->getForcedGroupMemberships();
		
		// add every user to every forced group
		$error = 0;
		foreach( $forced_groups as $group_id ) {
			foreach( $users as $user ) {
				$error |= !$this->addUserHelper( $user['mail'], $group_id );
			}
		}
		
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
	 * @param int $group_id		the id of the group to be tested
	 */
	protected function isForcedGroup( $group_id ) {
		$forced_groups = $this->getForcedGroupMemberships();
		return array_search( $group_id, $forced_groups ) !== false;
	}
	
	/**
	 * exports the details for all members of the given group
	 * 
	 * @param int $group_id			the id of the group the data should be exported from
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function exportGroupMemberDetails( $group_id ) {
		// get all groups the user has access to
		$groups = $this->loadGroups()->getData();
		$given_group = false;
		// check if the given group is there
		foreach( $groups as $group ) {
			if( $group['id'] == $group_id ) {
				$given_group = $group;
				break;
			}
		}
		
		// get all available data
		$data = $this->settings->getSetting( 'contacts_available_data' );
		// create file buffer
		$file_content = [];
		
		// add header line
		$line = [];
		foreach( $data as $key => $label ) {
			array_push( $line, $label );
		}
		array_push( $file_content, $line );
		
		// add a line for every member
		foreach( $given_group['members'] as $member ) {
			$line = [];
			foreach( $data as $key => $label ) {
				array_push( $line, $member[ $key ] );
			}
			array_push( $file_content, $line );
		}
		
		// write file header
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $given_group['cn'] . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		// output the file
		$file = fopen("php://output", 'w');
		// write each line
		foreach( $file_content as $line ) {
			fputcsv( $file, $line, $this->settings->getSetting( 'csv_seperator' ) );
		}
		exit;
	}
}