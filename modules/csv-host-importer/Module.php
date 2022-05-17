<?php
/**
  * Zabbix CSV Import Frontend Module
  *
  * @version 6.0.2
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

namespace Modules\Ichi;

use APP;
use CController as CAction;
use CWebUser;

/**
 * Please see Core\CModule class for additional reference.
 */
class Module extends \Core\CModule {

	/**
	 * Initialize module.
	 */
	public function init(): void {
		// Only super admins should see the menu entry
		if (CWebUser::getType() != USER_TYPE_SUPER_ADMIN) {
			return;
		}
		// Initialize main menu (CMenu class instance).
		APP::Component()->get('menu.main')->findOrAdd(_('Configuration'))->getSubmenu()->add((new \CMenuItem(_('Host CSV Importer')))->setAction('ichi.import'));
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