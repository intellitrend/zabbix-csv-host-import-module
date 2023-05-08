<?php
/**
  * Zabbix CSV Import Frontend Module
  *
  * @version 6.0.4
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
use CControllerResponseFatal;
use CController as CAction;
use CRoleHelper;
use CUploadFile;
use API;
use CWebUser;

/**
 * CSV Host Importer module action.
 */
class CsvHostImport extends CAction {

	// maximum length of a single CSV line
	const CSV_MAX_LINE_LEN = 1024;

	// character used to separate CSV fields
	const CSV_SEPARATOR = ';';

	// defined CSV column names
	const CSV_HEADER = [
		'NAME',
		'VISIBLE_NAME',
		'HOST_GROUPS',
		'TEMPLATES',
		'AGENT_IP',
		'AGENT_DNS',
		'SNMP_IP',
		'SNMP_DNS',
		'SNMP_VERSION',
		'DESCRIPTION',
		'HOST_GROUPS',
	];

	// required CSV column names
	const CSV_HEADER_REQUIRED = [
		'NAME',
		'HOST_GROUPS',
	];

	// user-friendly messages for upload error codes
	const UPLOAD_ERRORS = [
		0 => 'There is no error, the file uploaded with success',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
	];

	private $hostlist = [];
	private $step = 0;

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
		$this->disableCsrfValidation();
	}

	/**
	 * Check and sanitize user input parameters. Method called by Zabbix core. Execution stops if false is returned.
	 *
	 * @return bool true on success, false on error.
	 */
	protected function checkInput(): bool {
		$fields = [
			'step' => 'in 0,1,2',
			'cancel' => 'string',
		];

		$ret = $this->validateInput($fields);

		return $ret;
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

	private function csvUpload($path): bool {
		// can't continue here if there was no upload
		if (!isset($_FILES['csv_file'])) {
			error(_('Missing file upload.'));
			return false;
		}

		// check if there was a problem with the upload
		$csv_file = $_FILES['csv_file'];
		if ($csv_file['error'] != UPLOAD_ERR_OK) {
			error(_(self::UPLOAD_ERRORS[$csv_file['error']]));
			return false;
		}

		move_uploaded_file($csv_file['tmp_name'], $path);
		return true;
	}

	private function csvParse($path): bool {
		try {
			$row = 1;
			$this->hostlist = [];

			if (($fp = fopen($path, 'r')) !== FALSE) {
				// get first CSV line, which is the header
				$header = fgetcsv($fp, self::CSV_MAX_LINE_LEN, self::CSV_SEPARATOR);
				if ($header === FALSE) {
					error(_('Empty CSV file.'));
					return false;
				}

				// trim and upper-case all values in the header row
				$header_count = count($header);
				for ($i = 0; $i < $header_count; $i++) {
					$header[$i] = trim(strtoupper($header[$i]));
				}

				// check if all required columns are defined (surplus columns are silently ignored)
				foreach (self::CSV_HEADER_REQUIRED as $header_required) {
					if (array_search($header_required, $header) === false) {
						error(_s('Missing column "%1$s" in CSV file.', $header_required));
						return false;
					}
				}

				// get all other records till the end of the file
				$linenum = 1; // header was already read, so start at 1
				while (($line = fgetcsv($fp, self::CSV_MAX_LINE_LEN, self::CSV_SEPARATOR)) !== FALSE) {
					$linenum++;
					$column_count = count($line);
					if ($column_count < $header_count) {
						error(_s('Missing column "%1$s" in line %2$d"', $header[$column_count], $linenum));
						continue;
					}

					$host = [];
					foreach ($line as $index => $value) {
						if ($index >= $header_count) {
							// ignore surplus columns
							break;
						}
						$host[$header[$index]] = trim($value);
					}

					foreach (self::CSV_HEADER_REQUIRED as $header_required) {
						if (empty($host[$header_required])) {
							error(_s('Empty column "%1$s" in CSV file line %2$d.', $header_required, $linenum));
							return false;
						}
					}

					$this->hostlist[] = $host;
				}
				fclose($fp);
			}
		} catch (Exception $e) {
			// catch potential parsing exceptions and display them in the view
			error($e->getMessage());
			return false;
		}

		return true;
	}

	private function importHosts(): bool {
		foreach ($this->hostlist as &$host) {
			$zbxhost = [
				'host' => $host['NAME']
			];

			if (array_key_exists('VISIBLE_NAME', $host)) {
				$zbxhost['name'] = $host['VISIBLE_NAME'];
			}

			if (array_key_exists('DESCRIPTION', $host)) {
				$zbxhost['description'] = $host['DESCRIPTION'];
			}

			if (array_key_exists('HOST_GROUPS', $host)) {
				$hostgroups = explode(',', $host['HOST_GROUPS']);
				$zbxhostgroups = [];

				foreach ($hostgroups as $hostgroup) {
					$hostgroup = trim($hostgroup);
					if (empty($hostgroup)) {
						continue;
					}

					$hostgroup = trim($hostgroup);
					$zbxhostgroup = API::HostGroup()->get([
						'output' => ['groupid'],
						'filter' => ['name' => $hostgroup],
						'limit' => 1
					]);

					if (empty($zbxhostgroup)) {
						$result = API::HostGroup()->create(['name' => $hostgroup]);
						$zbxhostgroup = [['groupid' => $result['groupids'][0]]];
					}

					$zbxhostgroups[] = $zbxhostgroup[0];
				}

				$zbxhost['groups'] = $zbxhostgroups;
			}

			if (array_key_exists('TEMPLATES', $host)) {
				$templates = explode(',', $host['TEMPLATES']);
				$zbxtemplates = [];

				foreach ($templates as $template) {
					$template = trim($template);
					if (empty($template)) {
						continue;
					}

					$zbxtemplate = API::Template()->get([
						'output' => ['templateid'],
						'filter' => ['name' => $template],
						'limit' => 1
					]);

					if (empty($zbxtemplate)) {
						error(_s('Template "%1$s" on host "%2$s" not found.', $template, $host['NAME']));
					} else {
						$zbxtemplates[] = $zbxtemplate[0];
					}
				}

				$zbxhost['templates'] = $zbxtemplates;
			}

			$zbxinterfaces = [];

			if (!empty($host['AGENT_IP']) || !empty($host['AGENT_DNS'])) {
				$zbxinterfaces[] = [
					'type' => 1,
					'main' => 1,
					'dns' => $host['AGENT_DNS'],
					'ip' => $host['AGENT_IP'],
					'useip' => !empty($host['AGENT_IP']) ? 1 : 0,
					'port' => 10050
				];
			}

			if (!empty($host['SNMP_IP']) || !empty($host['SNMP_DNS'])) {
				$zbxinterfaces[] = [
					'type' => 2,
					'main' => 1,
					'dns' => $host['SNMP_DNS'],
					'ip' => $host['SNMP_IP'],
					'useip' => !empty($host['SNMP_IP']) && !empty($host['SNMP_IP']) ? 1 : 0,
					'port' => 161,
					'details' => [
						'version' => !empty($host['SNMP_VERSION']) ? intval($host['SNMP_VERSION']) : 1,
						'community' => '{$SNMP_COMMUNITY}'
					]
				];
			}

			if ($zbxinterfaces) {
				$zbxhost['interfaces'] = $zbxinterfaces;
			}

			$result = API::Host()->create($zbxhost);
			$host['HOSTID'] = empty($result['hostids']) ? -1 : $result['hostids'][0];
		}

		unset($host);

		return true;
	}

    /**
	 * Prepare the response object for the view. Method called by Zabbix core.
	 *
	 * @return void
	 */
	protected function doAction() {
		$tmpPath = sprintf("%s/ichi.hostlist.%d.csv", sys_get_temp_dir(), CWebUser::$data['userid']);

		if ($this->hasInput('step')) {
			$this->step = intval($this->getInput('step')) & 3;
		} else {
			$this->step = 0;
		}

		// reset step if cancelled by user
		if ($this->hasInput('cancel')) {
			$this->step = 0;
		}

		switch ($this->step) {
			case 0:
				// upload
				if (file_exists($tmpPath)) {
					unlink($tmpPath);
				}
				break;
			case 1:
				// preview
				if (!$this->csvUpload($tmpPath) || !$this->csvParse($tmpPath)) {
					$this->step = 0;
				}
				break;
			case 2:
				// import
				if (!file_exists($tmpPath)) {
					error(_('Missing temporary host file.'));
					break;
				}
				if (!$this->csvParse($tmpPath)) {
					error(_('Unexpected parsing error.'));
					break;
				}
				$this->importHosts();
				unlink($tmpPath);
				break;
		}

		$response = new CControllerResponseData([
			'hostlist' => $this->hostlist,
			'step' => $this->step
		]);
		$response->setTitle(_('CSV Host Importer'));
		$this->setResponse($response);
    }
}
?>