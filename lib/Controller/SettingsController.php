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

use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class SettingsController extends Controller {
	protected $AppName;private $uid;
	protected $array_settings = [ 'superuser_groups', 'forced_group_memberships' ];
	/** @var IL10N */
	private $l;
	/** @var IConfig */
	private $config;
	// default values
	private $default;
	private $user_default;
	
	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param string $UserId
	 */
	public function __construct( string $AppName, IRequest $request, IL10N $l10n, IConfig $config, string $UserId ) {
		// check we have a logged in user
		\OCP\User::checkLoggedIn();
		parent::__construct( $AppName, $request );
		// set class variables
		$this->AppName = $AppName;
		$this->config = $config;
        // load translation files
		$this->l = $l10n;
		// get the current users id
		$this->uid = $UserId;
		// fill default values
		$this->default = [
			'superuser_groups' => '[]',
			'user_gidnumber' => 501,
			'pwd_reset_url_active' => false,
			'pwd_reset_url' => '',
			'pwd_reset_url_attr' => 'login',
			'pwd_reset_url_attr_ldap_attr' => 'mail',
			'pwd_reset_tag' => '<reset_link>',
			'welcome_mail_subject' => $this->l->t( 'Welcome to Nextcloud' ),
			'welcome_mail_from_adress' => 'info@example.com',
			'welcome_mail_from_name' => 'Nextcloud',
			'welcome_mail_message' => $this->l->t( 'Welcome to Nextcloud! We hope you enjoy your time here.' ),
			'forced_group_memberships' => '[]',
			'csv_seperator' => ';',
			'mail_attribute' => 'mail',
			'firstname_attribute' => 'givenname',
			'lastname_attribtue' => 'sn',
		];
		$this->user_default = [
			'tutorial_state' => 0,
		];
	}
	
	/**
	 * returns the value for the given general setting
	 * 
	 * @param string $key
	 */
	public function getSetting( string $key ) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
		$setting = $this->config->getAppValue( $this->appName, $key, $this->default[ $key ] );
		// if this is an array setting, convert it
		if( in_array( $key, $this->array_settings ) ) $setting = json_decode( $setting );
		// return the setting
		return $setting;
	}
	
	/**
	 * returns all settings from this app
	 * 
	 * @NoAdminRequired
	 */
	public function getSettings() {
		// output buffer
		$data = array();
		// go through every existing setting
		foreach( $this->default as $key => $v ) {
			// get the settings value
			$data[ $key ] = $this->getSetting( $key );
		}
		// return the buffered data
		return new DataResponse( $data );
	}
	
	/*
	 * updates the given setting
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function updateSetting( string $key, $value ) {
		// check if the setting is an actual setting this app has
		if( !isset( $this->default[ $key ] ) ) return false;
		// if this is an array setting, convert it
		if( in_array( $key, $this->array_settings ) ) $value = json_encode( $value );
		// save the setting
		return !$this->config->setAppValue( $this->appName, $key, $value );
	}
	
	/**
	 * returns all settings from this app
	 * 
	 * @param string $settings
	 */
	public function updateSettings( string $settings ) {
		// parse the serialized form
		parse_str( urldecode( $settings ), $array );
		$settings = $array;
		
		$success = true;
		// go through every setting and update it
		foreach( $settings as $key => $value ) {
			// update the setting
			$success &= $this->updateSetting( $key, $value );
		}
		
		// return message
		if( $success ) return new DataResponse( [ 'data' => [ 'message' => $this->l->t( 'Settings saved' ) ], 'status' => 'success'] );
		else return new DataResponse( [ 'data' => [ 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ) ], 'status' => 'error' ] );
	}
	
	/**
	 * gets the value for the given setting
	 * 
	 * @param string $key
	 * @NoAdminRequired
	 */
	public function getUserValue( string $key ) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
		$value = $this->config->getUserValue( $this->uid, $this->appName, $key, $this->user_default[ $key ] );
		// if this is an array setting, convert it
		if( in_array( $key, $this->array_settings ) ) $value = json_decode( $value );
		return $value;
	}
	
	/**
	 * saves the given setting an returns a DataResponse
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @NoAdminRequired
	 */
	public function setUserValue( string $key, $value ) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
		// if this is an array setting, convert it
		if( in_array( $key, $this->array_settings ) ) $value = json_encode( $value );
		// save the value
		return $this->config->setUserValue( $this->uid, $this->appName, $key, $value );
	}
}
