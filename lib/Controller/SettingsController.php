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
		// fill default values
		$this->default = array(
			'superuser_group_id' => 500,
			'user_gidnumber' => 501,
			'pwd_reset_url_active' => false,
			'pwd_reset_url' => '',
			'pwd_reset_url_attr' => 'login',
			'pwd_reset_url_attr_ldap_attr' => 'mail',
			'welcome_mail_subject' => $this->l->t( 'Welcome to Nextcloud' ),
			'welcome_mail_from_adress' => 'info@example.com',
			'welcome_mail_from_name' => 'Nextcloud',
			'welcome_mail_message' => $this->l->t( 'Welcome to Nextcloud! We hope you enjoy your time here.' ),
		);
	}
	
	/**
	 * returns the value for the given general setting
	 */
	public function getSetting( $key ) {
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
		if( !array_key_exists( $key, $this->default ) ) return false;
		// save the setting
		return $this->config->setAppValue( $this->appName, $key, $value );
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
	 * returns the value for the given setting from the given user
	 */
	public function getUserSetting( $user, $key ) {
		return $this->config->getUserValue( $user, $this->appName, $key, $this->default[ $key ] );
	}
	
	/**
	 * returns the value from the LdapContacts App for the given setting from the given user
	 */
	public function getLdapContactUserSetting( $user, $key ) {
		return $this->config->getUserValue( $user, 'ldapcontacts', $key, $this->default[ $key ] );
	}
}
