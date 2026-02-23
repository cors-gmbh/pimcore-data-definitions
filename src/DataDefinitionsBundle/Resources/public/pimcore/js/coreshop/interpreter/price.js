/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.interpreters.coreshop_price');

pimcore.plugin.datadefinitions.interpreters.coreshop_price = Class.create(pimcore.plugin.datadefinitions.interpreters.abstract, {
    getLayout: function (fromColumn, toColumn, record, config) {
        return [{
            xtype: 'checkbox',
            fieldLabel: t('data_definitions_interpreter_coreshop_is_float'),
            name: 'isFloat',
            value: config.isFloat ? config.isFloat : false
        }];
    }
});
