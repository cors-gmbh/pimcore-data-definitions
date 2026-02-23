/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.interpreters.twig');

pimcore.plugin.datadefinitions.interpreters.twig = Class.create(pimcore.plugin.datadefinitions.interpreters.abstract, {
    getLayout: function (fromColumn, toColumn, record, config) {
        return [{
            xtype: 'textarea',
            fieldLabel: t('data_definitions_interpreter_template'),
            name: 'template',
            width: '100%',
            value: config.template ? config.template : null
        }];
    }
});
