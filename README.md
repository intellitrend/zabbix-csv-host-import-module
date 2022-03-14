# IntelliTrend Zabbix CSV Host Importer

This is a Zabbix frontend module that provides a simplified host import via CSV files.

![csv-host-importer](./images/csv-host-importer.png)

## License

This software is licensed under the GNU Lesser General Public License v3.0.

## Changelog

### Version 5.4.2

* Added support for Zabbix 5.0 and 5.2
* Non-functional menu entry for non-superadmins is now hidden

### Version 5.0.2

* Added support for templates

### Version 5.0.1

* Improved validation of CSV input

### Version 5.0.0

* Initial public release

## Requirements

- Zabbix 5.0 to 5.4
- File write access to the Zabbix frontend server
- Super admin permissions for the Zabbix users that want to use the frontend module

## Installation

For Debian and Ubuntu server, the Zabbix Frontend modules are usually placed in ``/usr/share/zabbix/modules/``.

Copy the folder `modules/csv-host-importer` to `/usr/share/zabbix/modules/csv-host-importer` on the Zabbix frontend web server.

Then go to `Administration`, `General`, `Modules`, click `Scan directory` and enable the new module in the list.

## Usage

Once the frontend module is activated, a new menu entry `Host CSV Importer` should appear under `Configuration`.

You can download an example CSV file from there or use the [example.csv](./example.csv) from the repo and edit the list to your liking.

Here is a list of supported CSV columns:

| Name         | Purpose                                                      | Optional |
| ------------ | ------------------------------------------------------------ | -------- |
| NAME         | Host name.                                                   | ❌        |
| VISIBLE_NAME | Host visible name.                                           | ✔        |
| HOST_GROUPS  | List of host group names, separated by a comma. Missing host groups are created automatically. | ❌        |
| TEMPLATES    | List of template names to assign to the host, separated by a comma. Templates must exist with the specified name. | ✔        |
| AGENT_IP     | Interface: Zabbix Agent IP address.                          | ✔        |
| AGENT_DNS    | Interface: Zabbix Agent DNS name.                            | ✔        |
| SNMP_IP      | Interface: SNMP IP address.                                  | ✔        |
| SNMP_DNS     | Interface: SNMP DNS name.                                    | ✔        |
| SNMP_VERSION | Interface: SNMP version number (1, 2 or 3).                  | ✔        |
| DESCRIPTION  | Host description field.                                      | ✔        |

Additional hints:

* The columns are case-insensitive, so ``NAME``, ``name`` and ``NaMe`` are all valid.
* The columns must be in the first line of the CSV file.
* The separator character must be ";".

The CSV file can then be imported in the same menu entry. You get a chance to preview the host list before the actual import.

