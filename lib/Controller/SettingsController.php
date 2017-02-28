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

Class SettingsController extends Controller {
	protected $appName;
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
	 */
	public function __construct( $AppName, IRequest $request, IL10N $l10n, IConfig $config ) {
		// check we have a logged in user
		\OCP\User::checkLoggedIn();
		parent::__construct( $AppName, $request );
		$this->appName = $AppName;
		$this->l = $l10n;
		$this->config = $config;
		// get the current users id
		$this->uid = \OC::$server->getUserSession()->getUser()->getUID();
		// fill default values
		$this->default = array(
			'superuser_group_id' => 500,
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
		);
		$this->user_default = array(
			'tutorial_state' => 0,
		);
	}
	
	/**
	 * returns the value for the given general setting
	 */
	public function getSetting( $key ) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
		return $this->config->getAppValue( $this->appName, $key, $this->default[ $key ] );
	}
	
	/**
	 * returns all settings from this app
	 * @NoCSRFRequired
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
	public function updateSetting( $key, $value ) {
		// check if the setting is an actual setting this app has
		if( !isset( $this->default[ $key ] ) ) return false;
		// save the setting
		return !$this->config->setAppValue( $this->appName, $key, $value );
	}
	
	/**
	 * returns all settings from this app
	 * 
	 * @param array $settings
	 * @NoCSRFRequired
	 */
	public function updateSettings( $settings ) {
		$success = true;
		// go through every setting and update it
		 foreach( $settings as $array ) {
			 $success &= $this->updateSetting( $array['name'], $array['value'] );
		 }
		 // return message
		 if( $success ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Settings saved' ) ), 'status' => 'success' ) );
		 else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Saving settings failed' ) ), 'status' => 'error	' ) );
	}
	
	/**
	 * gets the value for the given setting
	 * 
	 * @param string $key
	 * @NoAdminRequired
	 */
	public function getUserValue( $key ) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
		return $this->config->getUserValue( $this->uid, $this->appName, $key, $this->user_default[ $key ] );
	}
	
	/**
	 * saves the given setting an returns a DataResponse
	 * 
	 * @param string $key
	 * @param string $value
	 * @NoAdminRequired
	 */
	public function setUserValue( $key, $value ) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
		return $this->config->setUserValue( $this->uid, $this->appName, $key, $value );
	}
}
