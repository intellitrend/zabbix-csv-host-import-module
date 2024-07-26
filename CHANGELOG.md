# Changelog

## Version 6.3.0

* Support for Zabbix 6.4 and 7.0.
* Added support for host tags and macros (`HOST_TAGS`, `HOST_MACROS`).
* Added support for inventory fields (`INV_*`).
* Added support for SNMP community string (`SNMP_COMMUNITY`).
* Added support for additional CSV separators: comma and tabulator
* Example CSV now automatically uses the template name of the current Zabbix version.
* Fixed visible host names being required.
* Fixed incorrect check for missing host groups.

## Version 6.2.1

* Fixed JMX port not being set correctly on the host

## Version 6.2.0

* Added support for interface ports (`AGENT_PORT`, `SNMP_PORT`) and JMX interfaces (`JMX_IP`, `JMX_DNS`, `JMX_PORT`).
* Added support for host tags (`HOST_TAGS`).
* Added support for proxy (`PROXY`).

## Version 6.1.0

* Support for Zabbix 6.4 (for Zabbix 6.0 and 6.2, use previous version)
* Moved menu entry to `Administration`

## Version 6.0.4

* Support for Zabbix 6.2

## Version 6.0.2

* Fixed "Missing host list in session" error when reading larger CSV files
* Fixed incorrect host group and template assignment when the respective fields are empty
* Optional CSV columns now can be omitted entirely from the CSV file instead of leaving them empty

## Version 6.0.1

* Support for Zabbix 6.0

## Version 5.4.2

* Support for Zabbix 5.0, 5.2 and 5.4
* Non-functional menu entry for non-superadmins is now hidden

## Version 5.0.2

* Added support for templates

## Version 5.0.1

* Improved validation of CSV input

## Version 5.0.0

* Initial public release for Zabbix 5.4
