# IntelliTrend Zabbix CSV Host Importer

This is a Zabbix frontend module that provides a simplified host import via CSV files.

![csv-host-importer](./images/csv-host-importer.png)

## License

This software is licensed under the GNU Lesser General Public License v3.0.

## Download

You can find the latest versions for the respective Zabbix releases on the [Github releases page](https://github.com/intellitrend/zabbix-csv-host-import-module/releases).

## Requirements

- Zabbix 6.0, 6.2 or 6.4
- File write access to the Zabbix frontend server
- Super admin permissions for the Zabbix users that want to use the frontend module

## Installation

For Debian and Ubuntu server, the Zabbix Frontend modules are usually placed in ``/usr/share/zabbix/modules/``.

Copy the folder `modules/csv-host-importer` to `/usr/share/zabbix/modules/csv-host-importer` on the Zabbix frontend web server.

**Note:** If you're using Zabbix 6.4, you'll need to remove `manifest.json` and rename `manifest.v2.json` to `manifest.json`.

Then go to `Administration`, `General`, `Modules`, click `Scan directory` and enable the new module in the list.

## Usage

Once the frontend module is activated, a new menu entry `Host CSV Importer` should appear under `Configuration`.

Here's an example of two hosts: the first one with Zabbix agent and another with an SNMPv2 agent:
```
NAME;VISIBLE_NAME;HOST_GROUPS;TEMPLATES;AGENT_IP;AGENT_DNS;SNMP_IP;SNMP_DNS;SNMP_VERSION;DESCRIPTION
example1;Example Host Agent;First host group, second host group;Linux by Zabbix agent;127.0.0.1;localhost;;;;Example Zabbix Agent host
example2;Example Host SNMP;Third host group;Generic by SNMP;;;127.0.0.1;localhost;2;Example SNMPv2 host
```

The following CSV columns are supported:

| Name           | Purpose                                                      | Default | Optional |
| -------------- | ------------------------------------------------------------ | ------- | -------- |
| NAME           | Host name.                                                   |         | ❌        |
| VISIBLE_NAME   | Host visible name.                                           |         | ✔        |
| HOST_GROUPS    | List of host group names, separated by a '|'. Missing host groups are created automatically. |         | ❌        |
| HOST_TAGS      | List of host tags, separated by a '|'. The tag format can be either be ``tag name`` or ``tag name=tag value``. |         | ✔        |
| PROXY          | Name of the proxy that should monitor the host.              |         | ✔        |
| TEMPLATES      | List of template names to assign to the host, separated by a '|'. Templates must exist with the specified name. |         | ✔        |
| AGENT_IP       | Interface: Zabbix Agent IP address.                          |         | ✔        |
| AGENT_DNS      | Interface: Zabbix Agent DNS name.                            |         | ✔        |
| AGENT_PORT     | Interface: Zabbix Agent port.                                | 10050   | ✔        |
| SNMP_IP        | Interface: SNMP IP address.                                  |         | ✔        |
| SNMP_DNS       | Interface: SNMP DNS name.                                    |         | ✔        |
| SNMP_PORT      | Interface: SNMP port.                                        | 161     | ✔        |
| SNMP_VERSION   | Interface: SNMP version number (1, 2 or 3).                  |         | ✔        |
| SNMP_COMMUNITY | Interface: SNMP community string.                            | {$SNMP_COMMUNITY} | ✔        |
| JMX_IP         | Interface: JMX IP address.                                   |         | ✔        |
| JMX_DNS        | Interface: JMX DNS name.                                     |         | ✔        |
| JMX_PORT       | Interface: JMX port.                                         | 12345   | ✔        |
| DESCRIPTION    | Host description field.                                      |         | ✔        |

Additional hints:

* The columns are case-insensitive, so ``NAME``, ``name`` and ``NaMe`` are all valid.
* The columns must be in the first line of the CSV file.
* The separator character must be ";".

The CSV file can then be imported in the same menu entry. You get a chance to preview the host list before the actual import.

