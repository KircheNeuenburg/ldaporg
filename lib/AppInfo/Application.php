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

namespace OCA\LdapOrg\AppInfo;

use OCP\AppFramework\App;
use OCA\LdapOrg\Controller\PageController;
use OCA\LdapOrg\Controller\SettingsController;
use OCA\LdapOrg\Controller\ContactController;
use OCA\LdapOrg\Settings\Admin;

Class Application extends App {
	/**
	 * @param array $urlParams
	 */
	public function __construct( $urlParams = array() ) {
		parent::__construct( 'ldaporg', $urlParams );
		// register the apps services
		$this->registerServices();
	}
	
	/**
	 * register all required services
	 */
	private function registerServices() {
		$container = $this->getContainer();
		
		$container->registerAlias( 'SettingsController', SettingsController::class);
		$container->registerAlias( 'PageController', PageController::class);
		$container->registerAlias( 'Admin', Admin::class);
	}
	
	/**
	 * register the navigation button
	 */
	public function registerNavigation() {
		$container = $this->getContainer();
		
		// add menu entry
		$container->query('OCP\INavigationManager')->add(function () use ($container) {
			$urlGenerator = $container->query('OCP\IURLGenerator');
			$l10n = $container->query('OCP\IL10N');
			return [
				// the string under which your app will be referenced in owncloud
				'id' => 'ldaporg',

				// sorting weight for the navigation. The higher the number, the higher
				// will it be listed in the navigation
				'order' => 100,

				// the route that will be shown on startup
				'href' => $urlGenerator->linkToRoute('ldaporg.Page.index'),

				// the icon that will be shown in the navigation
				// this file needs to exist in img/
				'icon' => $urlGenerator->imagePath('ldaporg', 'settings.svg'),

				// the title of your application. This will be used in the
				// navigation or on the settings page of your app
				'name' => $l10n->t('Groups'),
			];
		});
	}
}