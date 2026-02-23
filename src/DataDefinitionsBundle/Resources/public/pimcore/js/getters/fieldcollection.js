/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.getters.fieldcollection');

pimcore.plugin.datadefinitions.getters.fieldcollection = Class.create(pimcore.plugin.datadefinitions.setters.fieldcollection, {
    getLayout: function (fromColumn, toColumn, record, config, definitionConfig) {
        this.fromColumn = fromColumn;

        this.fieldText = Ext.create({
            xtype: 'textfield',
            fieldLabel: t('field'),
            name: 'field',
            value: config.field ? config.field : null,
        });

        return [this.fieldText];
    },

    getGetterData: function () {
        return {
            'class': this.fromColumn.config.class,
            'field': this.fieldText.getValue(),
        };
    }
});
