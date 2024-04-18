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

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hosts_example.csv"');

if (substr(ZABBIX_VERSION, 0, 3) == "6.0") {
  $template = "Generic SNMP";
} else {
  $template = "Generic by SNMP";
}
?>
NAME;VISIBLE_NAME;HOST_GROUPS;HOST_TAGS;TEMPLATES;AGENT_IP;AGENT_DNS;SNMP_IP;SNMP_DNS;SNMP_VERSION;DESCRIPTION
example1;Example Host Agent;First host group|Second host group;;Linux by Zabbix agent;127.0.0.1;localhost;;;;Example Zabbix Agent host
example2;Example Host SNMP;Third host group;First tag|Second tag=with value;<?=$template?>;;;127.0.0.1;localhost;2;Example SNMPv2 host
