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

namespace Modules\ICHI\Actions;

use CControllerResponseData;
use CRoleHelper;
use API;
use CWebUser;

function startsWith($haystack, $needle ) {
	return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Host CSV importer module action.
 */
class CSVHostImport extends CSVHostImportAction {

	// maximum length of a single CSV line
	const CSV_MAX_LINE_LEN = 8192;

	// separator used for fields that can contain multiple elements
	const ELEMENT_SEPARATOR = '|';
	// separator used for elements that can have a value (tags)
	const VALUE_SEPARATOR = '=';

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

	private $csvColumns;
	private $hostlist = [];
	private $hostcols = [];
	private $step = 0;
	private $separator;

	/**
	 * Initialize action. Method called by Zabbix core.
	 *
	 * @return void
	 */
	public function init(): void {
		 // define CSV columns
		 $this->csvColumns = [
			// Technical name			Friendly name						Default	Required
			['NAME',					'Name',							'',		true],
			['VISIBLE_NAME',			'Visible name',					'',		false],
			['HOST_GROUPS',				'Host groups',					'',		true],
			['HOST_TAGS',				'Host tags',					'',		false],
			['HOST_MACROS',				'Host macros',					'',		false],
			['PROXY',					'Proxy',						'',		false],
			['PROXY_GROUP',				'Proxy Group',						'',		false],
			['STATUS',					'Host enabled status' ,			'0',		false],
			['TEMPLATES',				'Templates',					'',		false],
			['AGENT_IP',				'Agent IP',						'',		false],
			['AGENT_DNS',				'Agent DNS',					'',		false],
			['AGENT_PORT',				'Agent port',					'10050',	false],
			['SNMP_IP',					'SNMP IP',						'',		false],
			['SNMP_DNS',				'SNMP DNS',						'',		false],
			['SNMP_PORT',				'SNMP port',					'161',		false],
			['SNMP_VERSION',			'SNMP version',					'',		false],
			['SNMP_BULK',				'SNMP bulk mode',				'1',		false],
			['SNMP_COMMUNITY',			'SNMP community',				'{$SNMP_COMMUNITY}', false],
			['SNMP_MAXREP',				'SNMP max repetitions',			'10',	false],
			['SNMP_V3SECNAME',			'SNMPv3 security name',			'',		false],
			['SNMP_V3SECLEVEL',			'SNMPv3 security level',			'',		false],
			['SNMP_V3AUTHPASSPHRASE',	'SNMPv3 authentication passphrase',	'',		false],
			['SNMP_V3PRIVPASSPHRASE',	'SNMPv3 privacy passphrase',		'',		false],
			['SNMP_V3AUTHPROTOCOL',		'SNMPv3 authentication protocol',	'',		false],
			['SNMP_V3PRIVPROTOCOL',		'SNMPv3 privacy protocol',		'',		false],
			['SNMP_V3CONTEXTNAME',		'SNMPv3 context name',			'',		false],			
			['DESCRIPTION',				'Description',					'',		false],
			['JMX_IP',					'JMX IP',						'',		false],
			['JMX_DNS',					'JMX DNS',						'',		false],
			['JMX_PORT',				'JMX port',						'12345',	false],
			['INV_ALIAS',				'Alias',						'',		false],
			['INV_ASSET_TAG',			'Asset tag',					'',		false],
			['INV_CHASSIS',				'Chassis',						'',		false],
			['INV_CONTACT',				'Contact person',				'',		false],
			['INV_CONTRACT_NUMBER',		'Contract number',				'',		false],
			['INV_DATE_HW_DECOMM',		'HW decommissioning date',		'',		false],
			['INV_DATE_HW_EXPIRY',		'HW maintenance expiry date', 	'',		false],
			['INV_DATE_HW_INSTALL',		'HW installation date',			'',		false],
			['INV_DATE_HW_PURCHASE',	'HW purchase date',				'',		false],
			['INV_DEPLOYMENT_STATUS',	'Deployment status',			'',		false],
			['INV_HARDWARE',			'Hardware',						'',		false],
			['INV_HARDWARE_FULL',		'Detailed hardware',			'',		false],
			['INV_HOST_NETMASK',		'Host subnet mask',				'',		false],
			['INV_HOST_NETWORKS',		'Host networks',				'',		false],
			['INV_HOST_ROUTER',			'Host router',					'',		false],
			['INV_HW_ARCH',				'HW architecture',				'',		false],
			['INV_INSTALLER_NAME',		'Installer name',				'',		false],
			['INV_LOCATION',			'Location',						'',		false],
			['INV_LOCATION_LAT',		'Location latitude',			'',		false],
			['INV_LOCATION_LON',		'Location longitude',			'',		false],
			['INV_MACADDRESS_A',		'MAC address A',				'',		false],
			['INV_MACADDRESS_B',		'MAC address B',				'',		false],
			['INV_MODEL',				'Model',						'',		false],
			['INV_NAME',				'Name',							'',		false],
			['INV_NOTES',				'Notes',						'',		false],
			['INV_OOB_IP',				'OOB IP address',				'',		false],
			['INV_OOB_NETMASK',			'OOB host subnet mask',			'',		false],
			['INV_OOB_ROUTER',			'OOB router',					'',		false],
			['INV_OS',					'OS name',						'',		false],
			['INV_OS_FULL',				'Detailed OS name',				'',		false],
			['INV_OS_SHORT',			'Short OS name',				'',		false],
			['INV_POC_1_CELL',			'Primary POC mobile number',	'',		false],
			['INV_POC_1_EMAIL',			'Primary email',				'',		false],
			['INV_POC_1_NAME',			'Primary POC name',				'',		false],
			['INV_POC_1_NOTES',			'Primary POC notes',			'',		false],
			['INV_POC_1_PHONE_A',		'Primary POC phone A',			'',		false],
			['INV_POC_1_PHONE_B',		'Primary POC phone B',			'',		false],
			['INV_POC_1_SCREEN',		'Primary POC screen name',		'',		false],
			['INV_POC_2_CELL',			'Secondary POC mobile number',	'',		false],
			['INV_POC_2_EMAIL',			'Secondary POC email',			'',		false],
			['INV_POC_2_NAME',			'Secondary POC name',			'',		false],
			['INV_POC_2_NOTES',			'Secondary POC notes',			'',		false],
			['INV_POC_2_PHONE_A',		'Secondary POC phone A',		'',		false],
			['INV_POC_2_PHONE_B',		'Secondary POC phone B',		'',		false],
			['INV_POC_2_SCREEN',		'Secondary POC screen name',	'',		false],
			['INV_SERIALNO_A',			'Serial number A',				'',		false],
			['INV_SERIALNO_B',			'Serial number B',				'',		false],
			['INV_SITE_ADDRESS_A',		'Site address A',				'',		false],
			['INV_SITE_ADDRESS_B',		'Site address B',				'',		false],
			['INV_SITE_ADDRESS_C',		'Site address C',				'',		false],
			['INV_SITE_CITY',			'Site city',					'',		false],
			['INV_SITE_COUNTRY',		'Site country',					'',		false],
			['INV_SITE_NOTES',			'Site notes',					'',		false],
			['INV_SITE_RACK',			'Site rack location',			'',		false],
			['INV_SITE_STATE',			'Site state',					'',		false],
			['INV_SITE_ZIP',			'Site ZIP/postal code',			'',		false],
			['INV_SOFTWARE',			'Software',						'',		false],
			['INV_SOFTWARE_APP_A',		'Software application A',		'',		false],
			['INV_SOFTWARE_APP_B',		'Software application B',		'',		false],
			['INV_SOFTWARE_APP_C',		'Software application C',		'',		false],
			['INV_SOFTWARE_APP_D',		'Software application D',		'',		false],
			['INV_SOFTWARE_APP_E',		'Software application E',		'',		false],
			['INV_SOFTWARE_FULL',		'Software details',				'',		false],
			['INV_TAG',					'Tag',							'',		false],
			['INV_TYPE',				'Type',							'',		false],
			['INV_TYPE_FULL',			'Type details',					'',		false],
			['INV_URL_A',				'URL A',						'',		false],
			['INV_URL_B',				'URL B',						'',		false],
			['INV_URL_C',				'URL C',						'',		false],
			['INV_VENDOR',				'Vendor',						'',		false],
		];

		/**
		 * Disable SID (Sessoin ID) validation. Session ID validation should only be used for actions which involde data
		 * modification, such as update or delete actions. In such case Session ID must be presented in the URL, so that
		 * the URL would expire as soon as the session expired.
		 */
		if (method_exists($this, 'disableSIDvalidation')) {
			$this->disableSIDvalidation();
		} else {
			$this->disableCsrfValidation();
		}
	}

	/**
	 * Check and sanitize user input parameters. Method called by Zabbix core. Execution stops if false is returned.
	 *
	 * @return bool true on success, false on error.
	 */
	protected function checkInput(): bool {
		$fields = [
			'step' => 'in 0,1,2',
			'separator' => 'in 0,1,2',
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
			$this->hostlist = [];
			$this->hostcols = [];

			if (($fp = fopen($path, 'r')) !== FALSE) {
				// check for BOM
				if (fgets($fp, 4) !== "\xef\xbb\xbf") {
					// BOM not found - rewind pointer to start of file
					rewind($fp);
				}

				// get first CSV line, which is the header
				$header = fgetcsv($fp, self::CSV_MAX_LINE_LEN, $this->csvSeparators[$this->separator]);
				if ($header === FALSE) {
					error(_('Empty CSV file.'));
					return false;
				}

				// trim and upper-case all values in the header row
				$header_count = count($header);
				for ($i = 0; $i < $header_count; $i++) {
					$header[$i] = trim($header[$i]);
					foreach ($this->csvColumns as $csvColumn) {
						// check for technical name or friendly name
						if (strcasecmp($header[$i], $csvColumn[0]) === 0 || strcasecmp($header[$i], $csvColumn[1]) === 0) {
							// save column index
							$header[$i] = $csvColumn[0];
							// add to defined column list
							$this->hostcols[$csvColumn[0]] = $csvColumn[1];
							break;
						}
					}
				}

				// check if all required columns are defined (surplus columns are silently ignored)
				foreach ($this->csvColumns as $csvColumn) {
					if ($csvColumn[3] && array_search($csvColumn[0], $header) === false) {
						error(_s('Missing required column "%1$s" / "%2$s" in CSV file.', $csvColumn[0], $csvColumn[1]));
						return false;
					}
				}

				// get all other records till the end of the file
				$linenum = 1; // header was already read, so start at 1
				while (($line = fgetcsv($fp, self::CSV_MAX_LINE_LEN, $this->csvSeparators[$this->separator])) !== FALSE) {
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

					// make sure all columns are defined
					foreach ($this->csvColumns as $csvColumn) {
						// required coumns not only must exist but also be non-empty
						if ($csvColumn[3] && (!array_key_exists($csvColumn[0], $host) || $host[$csvColumn[0]] === '')) {
							error(_s('Empty required column "%1$s" in CSV file line %2$d.', $csvColumn[0], $linenum));
							return false;
						}

						// set default value if column is defined and empty
						if (!array_key_exists($csvColumn[0], $host)) {
							$host[$csvColumn[0]] = $csvColumn[2];
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

	private function importHost($host): int {
		// Get the current API version
		$api_version = API::APIInfo()->version([]);

		$zbxhost = [
			'host' => $host['NAME']
		];

		if ($host['VISIBLE_NAME'] !== '') {
			$zbxhost['name'] = $host['VISIBLE_NAME'];
		}

		if ($host['DESCRIPTION'] !== '') {
			$zbxhost['description'] = $host['DESCRIPTION'];
		}

		if ($host['HOST_GROUPS'] !== '') {
			$hostgroups = explode(self::ELEMENT_SEPARATOR, $host['HOST_GROUPS']);
			$zbxhostgroups = [];

			foreach ($hostgroups as $hostgroup) {
				$hostgroup = trim($hostgroup);
				if ($hostgroup === '') {
					continue;
				}

				$hostgroup = trim($hostgroup);
				$zbxhostgroup = API::HostGroup()->get([
					'output' => ['groupid'],
					'filter' => ['name' => $hostgroup],
					'limit' => 1
				]);

				if (!$zbxhostgroup) {
					$result = API::HostGroup()->create(['name' => $hostgroup]);
					$zbxhostgroup = [['groupid' => $result['groupids'][0]]];
				}

				$zbxhostgroups[] = $zbxhostgroup[0];
			}

			$zbxhost['groups'] = $zbxhostgroups;
		}

		if ($host['HOST_TAGS'] !== '') {
			$hosttags = explode(self::ELEMENT_SEPARATOR, $host['HOST_TAGS']);
			$zbxhost['tags'] = [];

			foreach ($hosttags as $hosttag) {
				if ($hosttag === '') {
					continue;
				}

				if (str_contains($hosttag, self::VALUE_SEPARATOR)) {
					$tmp = explode(self::VALUE_SEPARATOR, $hosttag, 2);
					$zbxhost['tags'][] = [
						'tag' => $tmp[0],
						'value' => $tmp[1],
					];
				} else {
					$zbxhost['tags'][] = [
						'tag' => $hosttag,
					];
				}
			}
		}

		if ($host['HOST_MACROS'] !== '') {
			$hostmacros = explode(self::ELEMENT_SEPARATOR, $host['HOST_MACROS']);
			$zbxhost['macros'] = [];

			foreach ($hostmacros as $hostmacro) {
				if ($hostmacro === '') {
					continue;
				}

				if (str_contains($hostmacro, self::VALUE_SEPARATOR)) {
					$tmp = explode(self::VALUE_SEPARATOR, $hostmacro, 2);
					$zbxhost['macros'][] = [
						'macro' => $tmp[0],
						'value' => $tmp[1],
					];
				} else {
					$zbxhost['macros'][] = [
						'macro' => $hostmacro,
						'value' => '',
					];
				}
			}
		}

		$zbxhost['inventory'] = [];
		foreach ($host as $key => $value) {
			if (startsWith($key, 'INV_') && $value !== '') {
				$invKey = strtolower(substr($key, 4));
				$zbxhost['inventory'][$invKey] = $value;
			}
		}

		if ($host['PROXY'] !== '') {
			if ($api_version >= '7.0') {
				$zbxproxy = API::Proxy()->get([
					'output' => ['proxyid'],
					'filter' => ['name' => $host['PROXY']],
					'limit' => 1
				]);
	
				if ($zbxproxy) {
					$zbxhost['proxyid'] = $zbxproxy[0]['proxyid'];
					$zbxhost['monitored_by'] = 1; // PROXY 
				} else {
					error(_s('Proxy "%1$s" on host "%2$s" not found.', $host['PROXY'], $host['NAME']));
					return -1;
				}
			} else {
				$zbxproxy = API::Proxy()->get([
					'output' => ['proxyid'],
					'filter' => ['host' => $host['PROXY']],
					'limit' => 1
				]);
	
				if ($zbxproxy) {
					$zbxhost['proxy_hostid'] = $zbxproxy[0]['proxyid'];
				} else {
					error(_s('Proxy "%1$s" on host "%2$s" not found.', $host['PROXY'], $host['NAME']));
					return -1;
				}
			}
		}

		if ($host['PROXY_GROUP'] !== '') {
			if ($api_version >= '7.0') {
				$zbxproxygroup = API::ProxyGroup()->get([
					'output' => ['proxy_groupid'],
					'filter' => ['name' => $host['PROXY_GROUP']],
					'limit' => 1
				]);
				if ($zbxproxygroup) {
						$zbxhost['proxy_groupid'] = $zbxproxygroup[0]['proxy_groupid'];
						$zbxhost['monitored_by'] = 2; // PROXY_GROUP 
				} else {
						error(_s('Proxy Group "%1$s" on host "%2$s" not found.', $host['PROXY_GROUP'], $host['NAME']));
						return -1;
				}
			} else {
					error(_s('Proxy Groups supported on API v7 and above. Cant add proxy group "%1$s" on host "%2$s" ', $host['PROXY_GROUP'], $host['NAME']));
					return -1;
				}
		}
		
		if ($host['STATUS'] !== '') {
			$zbxhost['status'] = $host['STATUS'] !== '' ? intval($host['STATUS']) : 0;
		}

		if ($host['TEMPLATES'] !== '') {
			$templates = explode(self::ELEMENT_SEPARATOR, $host['TEMPLATES']);
			$zbxtemplates = [];

			foreach ($templates as $template) {
				$template = trim($template);
				if ($template === '') {
					continue;
				}

				$zbxtemplate = API::Template()->get([
					'output' => ['templateid'],
					'filter' => ['name' => $template],
					'limit' => 1
				]);

				if ($zbxtemplate) {
					$zbxtemplates[] = $zbxtemplate[0];
				} else {
					error(_s('Template "%1$s" on host "%2$s" not found.', $template, $host['NAME']));
					return -1;
				}
			}

			$zbxhost['templates'] = $zbxtemplates;
		}

		$zbxinterfaces = [];

		if ($host['AGENT_IP'] !== '' || $host['AGENT_DNS'] !== '') {
			$zbxinterfaces[] = [
				'type' => 1,
				'dns' => $host['AGENT_DNS'],
				'ip' => $host['AGENT_IP'],
				'main' => 1,
				'useip' => $host['AGENT_IP'] !== '' ? 1 : 0,
				'port' => $host['AGENT_PORT'] !== '' ? intval($host['AGENT_PORT']) : 10050,
			];
		}

		if (($host['SNMP_IP'] !== '' || $host['SNMP_DNS'] !== '') && $host['SNMP_VERSION'] !== '3') {
			$zbxinterfaces[] = [
				'type' => 2,
				'dns' => $host['SNMP_DNS'],
				'ip' => $host['SNMP_IP'],
				'main' => 1,
				'useip' => $host['SNMP_IP'] !== '' ? 1 : 0,
				'port' => $host['SNMP_PORT'] !== '' ? intval($host['SNMP_PORT']) : 161,
				'details' => [
					'version' => $host['SNMP_VERSION'] !== '' ? intval($host['SNMP_VERSION']) : 1,
					'bulk' => $host['SNMP_BULK'] !== '' ? intval($host['SNMP_BULK']) : 1,
					'community' => $host['SNMP_COMMUNITY'],
					'max_repetitions' => $host['SNMP_MAXREP'] !== '' ? intval($host['SNMP_MAXREP']) : 10
				]
			];
		}

		if (($host['SNMP_IP'] !== '' || $host['SNMP_DNS'] !== '') && ($host['SNMP_VERSION'] == '3')) {
			$zbxinterfaces[] = [
				'type' => 2,
				'dns' => $host['SNMP_DNS'],
				'ip' => $host['SNMP_IP'],
				'main' => 1,
				'useip' => $host['SNMP_IP'] !== '' ? 1 : 0,
				'port' => $host['SNMP_PORT'] !== '' ? intval($host['SNMP_PORT']) : 161,
				'details' => [
					'version' => intval($host['SNMP_VERSION']),
					'bulk' => $host['SNMP_BULK'] !== '' ? intval($host['SNMP_BULK']) : 1,
					'max_repetitions' => $host['SNMP_MAXREP'] !== '' ? intval($host['SNMP_MAXREP']) : 10,
					'securityname' => $host['SNMP_V3SECNAME'],
					'securitylevel' => intval($host['SNMP_V3SECLEVEL']),
					'authpassphrase' => $host['SNMP_V3AUTHPASSPHRASE'],
					'privpassphrase' => $host['SNMP_V3PRIVPASSPHRASE'],
					'authprotocol' => intval($host['SNMP_V3AUTHPROTOCOL']),
					'privprotocol' => intval($host['SNMP_V3PRIVPROTOCOL']),
					'contextname' => $host['SNMP_V3CONTEXTNAME']
				]
			];
		}

		if ($host['JMX_IP'] !== '' || $host['JMX_DNS'] !== '') {
			$zbxinterfaces[] = [
				'type' => 4,
				'dns' => $host['JMX_DNS'],
				'ip' => $host['JMX_IP'],
				'main' => 1,
				'useip' => $host['JMX_IP'] !== '' ? 1 : 0,
				'port' => $host['JMX_PORT'] !== '' ? intval($host['JMX_PORT']) : 12345,
			];
		}

		if ($zbxinterfaces) {
			$zbxhost['interfaces'] = $zbxinterfaces;
		}

		$result = API::Host()->create($zbxhost);
		if ($result && $result['hostids']) {
			return intval($result['hostids'][0]);
		}

		return -1;
	}

	private function importHosts() {
		foreach ($this->hostlist as &$host) {
			$host['HOSTID'] = $this->importHost($host);
		}
		unset($host);
	}

	/**
	 * Prepare the response object for the view. Method called by Zabbix core.
	 *
	 * @return void
	 */
	protected function doAction() {
		$tmpPath = sprintf("%s/ichi.hostlist.%d.csv", sys_get_temp_dir(), CWebUser::$data['userid']);

		if ($this->hasInput('separator')) {
			$this->separator = $this->getInput('separator');
		} else {
			$this->separator = 0;
		}

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
					// upload or parser error, go back to upload step
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
			'hostcols' => $this->hostcols,
			'step' => $this->step,
			'separator' => $this->separator,
		]);
		$response->setTitle(_('Host CSV Importer'));
		$this->setResponse($response);
	}
}
?>