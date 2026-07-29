/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.data_definitions');

pimcore.plugin.data_definitions = Class.create({
    getClassName: function () {
        return 'pimcore.plugin.data_definitions';
    },

    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
    },

    preMenuBuild: function (e) {
        var menu = e.detail.menu;
        var user = pimcore.globalmanager.get('user');

        if (user.isAllowed('plugins')) {
            menu.settings.items.push({
                text: t('data_definitions_import_definitions'),
                iconCls: 'data_definitions_nav_icon_import_definition',
                itemId: 'pimcore_menu_settings_data_definitions_import',
                handler: this.openImportDefinitions.bind(this)
            });

            menu.settings.items.push({
                text: t('data_definitions_export_definitions'),
                iconCls: 'data_definitions_nav_icon_export_definition',
                itemId: 'pimcore_menu_settings_data_definitions_export',
                handler: this.openExportDefinitions.bind(this)
            });
        }
    },

    pimcoreReady: function () {
        var user = pimcore.globalmanager.get('user');

        if (user.isAllowed('plugins')) {
            coreshop.global.addStore('data_definitions_definitions', 'data_definitions/import_definitions');
            coreshop.global.addStore('data_definitions_export_definitions', 'data_definitions/export_definitions');

            pimcore.globalmanager.add('importdefinitions_definitions', pimcore.globalmanager.get('data_definitions_definitions'));
            pimcore.globalmanager.add('importdefinitions_export_definitions', pimcore.globalmanager.get('data_definitions_export_definitions'));
        }
    },

    openImportDefinitions: function () {
        try {
            pimcore.globalmanager.get('data_definitions_import_definition_panel').activate();
        } catch (e) {
            pimcore.globalmanager.add('data_definitions_import_definition_panel', new pimcore.plugin.datadefinitions.import.panel());
        }
    },

    openExportDefinitions: function () {
        try {
            pimcore.globalmanager.get('data_definitions_export_definition_panel').activate();
        } catch (e) {
            pimcore.globalmanager.add('data_definitions_export_definition_panel', new pimcore.plugin.datadefinitions.export.panel());
        }
    }
});

new pimcore.plugin.data_definitions();

