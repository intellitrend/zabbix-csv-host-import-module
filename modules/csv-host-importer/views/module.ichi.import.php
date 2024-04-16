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

// alias for Zabbix 6.0
if (!class_exists('CHtmlPage') && class_exists('CWidget')) {
	class_alias('CWidget', 'CHtmlPage');
}

$widget = (new CHtmlPage())->setTitle(_('CSV Host Importer'));
$form_list = (new CFormList('hostListFormList'));
$form = (new CForm('post', (new CUrl('zabbix.php'))
        ->setArgument('action', 'ichi.import')
        ->getUrl(), 'multipart/form-data')
);

$button_name = ''; // label for the button of the next step
$other_buttons = []; // optional extra buttons
$step = $data['step']; // current step

switch ($step) {
    case 0:
        // upload
        $form_list->addRow(
            (new CDiv(_('This form allows you to import hosts using CSV files. Click "Example" to download an example CSV file.')))
                ->addClass('table-forms-separator')
        );
        $form_list->addRow(
            (new CLabel(_('CSV File'), 'file'))->setAsteriskMark(),
            (new CFile('csv_file'))
                ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
                ->setAriaRequired()
        );

        $button_name = 'Preview';
        $other_buttons[] = (new CRedirectButton(_('Example'), (new CUrl('zabbix.php'))->setArgument('action', 'ichi.example')));

        $step++;
        break;
    case 1:
        // preview
        $form_list->addRow(
            (new CDiv(_('Please review your host import. If you\'re satisfied with the result, click "Import" to create to the hosts as listed here.')))
                ->addClass('table-forms-separator')
        );
        $hostlist = $data['hostlist'];
        $hostcols = $data['hostcols'];

        $table = (new CTable())->setId('hostlist-table');
        if (defined('ZBX_STYLE_VALUEMAP_LIST_TABLE')) {
            $table->addClass(ZBX_STYLE_VALUEMAP_LIST_TABLE);
        }

        $cols = [];

        foreach ($hostcols as $raw => $friendly) {
            $cols[] = (new CTableColumn(_($friendly)))
                ->addStyle('min-width: 10em;')
                ->addClass('table-col-handle');
        }
    
        $table->setColumns($cols);
    
        foreach ($hostlist as $row) {
            $cols = [];
            foreach ($hostcols as $raw => $friendly) {
                $cols[] = new CCol($row[$raw] ?? '');
            }

            $table->addRow($cols, 'form_row');
        }
    
        $form_list->addRow($table);
    
        $button_name = 'Import';
        $other_buttons[] = new CSubmit("cancel", _("Cancel"));
        $step++;
        break;
    case 2:
        // import
        $hostlist = $data['hostlist'];

        $table = (new CTable())->setId('hostlist-table');
        if (defined('ZBX_STYLE_VALUEMAP_LIST_TABLE')) {
            $table->addClass(ZBX_STYLE_VALUEMAP_LIST_TABLE);
        }
    
        $table->setColumns([
            (new CTableColumn(_('Name')))
                ->addStyle('min-width: 10em;')
                ->addClass('table-col-handle'),
            (new CTableColumn(_('Visible Name')))
                ->addStyle('min-width: 10em;')
                ->addClass('table-col-handle'),
            (new CTableColumn(_('Status')))
                ->addStyle('min-width: 10em;')
                ->addClass('table-col-handle'),
        ]);
    
        foreach ($hostlist as $row) {
            $hostid = $row['HOSTID'];

            $cols = [];
            $cols[] = new CCol($row['NAME']);
            $cols[] = new CCol($row['VISIBLE_NAME'] ?? '');

            if ($hostid != -1) {
                $cols[] = new CCol(
                    new CLink('Created', (new CUrl('zabbix.php'))
                        ->setArgument('action', 'host.edit')
                        ->setArgument('hostid', $hostid)
                    )
                );
            } else {
                $cols[] = (new CCol('Error'))->addClass(ZBX_STYLE_RED);
            }

            $table->addRow($cols, 'form_row');
        }
    
        $form_list->addRow($table);

        $button_name = 'Back';
        $step = 0;
        break;
}

$tab_view = (new CTabView())->addTab('hostListTab', _('Background'), $form_list);

if ($button_name !== '') {
    $tab_view->setFooter(makeFormFooter(
        new CSubmit(null, _($button_name)),
        $other_buttons
    ));
}

$form->addVar('step', $step);
$form->addItem($tab_view);

$widget->addItem($form);
$widget->show();
?>
