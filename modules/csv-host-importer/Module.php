<?php
/**
  * Zabbix CSV Import Frontend Module
  *
  * @version 6.2.1
  * @author Wolfgang Alper <wolfgang.alper@intellitrend.de>
  * @copyright IntelliTrend GmbH, https://www.intellitrend.de
  * @license GNU Lesser General Public License v3.0
  *
  * You can redistribute this library and/or modify it under the terms of
  * the GNU LGPL as published by the Free Software Foundation,
  * either version 3 of the License, or any later version.
  * However you must not change author and copyright information.
  */

declare(strict_types = 1);

namespace Modules\ICHI;

use APP;
use CController as CAction;
use CWebUser;
use CMenuItem;

// alias for Zabbix 6.0
if (!class_exists('Zabbix\Core\CModule') && class_exists('Core\CModule')) {
	class_alias('Core\CModule', 'Zabbix\Core\CModule');
}

use Zabbix\Core\CModule;

/**
 * Please see Core\CModule class for additional reference.
 */
class Module extends CModule {

	/**
	 * Initialize module.
	 */
	public function init(): void {
		// Only super admins should see the menu entry
		if (CWebUser::getType() != USER_TYPE_SUPER_ADMIN) {
			return;
		}

		if (substr(ZABBIX_VERSION, 0, 3) == "6.0") {
			$menu = _('Configuration');
		} else {
			$menu = _('Data collection');
		}

		// Initialize main menu (CMenu class instance).
		APP::Component()->get('menu.main')->findOrAdd($menu)->getSubmenu()->add((new CMenuItem(_('Host CSV Importer')))->setAction('ichi.import'));
	}

	/**
	 * Event handler, triggered before executing the action.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onBeforeAction(CAction $action): void {
	}

	/**
	 * Event handler, triggered on application exit.
	 *
	 * @param CAction $action  Action instance responsible for current request.
	 */
	public function onTerminate(CAction $action): void {
	}
}