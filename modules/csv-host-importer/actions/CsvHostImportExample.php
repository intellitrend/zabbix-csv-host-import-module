<?php
/**
  * Zabbix CSV Import Frontend Module
  *
  * @version 6.0.3
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

namespace Modules\Ichi\Actions;

use CControllerResponseData;
use CController as CAction;
use CRoleHelper;

/**
 * Host CSV importer example page action.
 */
class CsvHostImportExample extends CAction {

	/**
	 * Initialize action. Method called by Zabbix core.
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * Disable SID (Sessoin ID) validation. Session ID validation should only be used for actions which involde data
		 * modification, such as update or delete actions. In such case Session ID must be presented in the URL, so that
		 * the URL would expire as soon as the session expired.
		 */
		$this->disableSIDvalidation();
	}

	/**
	 * Check and sanitize user input parameters. Method called by Zabbix core. Execution stops if false is returned.
	 *
	 * @return bool true on success, false on error.
	 */
	protected function checkInput(): bool {
		return true;
	}

	/**
	 * Check if the user has permission to execute this action. Method called by Zabbix core.
	 * Execution stops if false is returned.
	 *
	 * @return bool
	 */
	protected function checkPermissions(): bool {
		return $this->checkAccess(CRoleHelper::UI_ADMINISTRATION_GENERAL);
	}

	/**
	 * Prepare the response object for the view. Method called by Zabbix core.
	 *
	 * @return void
	 */
	protected function doAction() {
		$response = new CControllerResponseData([]);
		$this->setResponse($response);
	}
}