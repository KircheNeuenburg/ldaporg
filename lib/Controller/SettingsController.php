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
			'order_by' => 'firstname',
			'user_gidnumber' => 501,
			'pwd_reset_url_active' => true,
			'pwd_reset_url' => 'https://account.kircheneuenburg.de/?action=sendtoken',
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
