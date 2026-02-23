/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.interpreters.asset_by_path');

pimcore.plugin.datadefinitions.interpreters.asset_by_path = Class.create(pimcore.plugin.datadefinitions.interpreters.abstract, {
    getLayout: function (fromColumn, toColumn, record, config) {
        return [{
            xtype: 'textfield',
            fieldLabel: t('path'),
            name: 'path',
            width: 500,
            value: config.path || null
        }];
    }
});
