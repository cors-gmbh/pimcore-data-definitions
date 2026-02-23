/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.setters.localizedfield');

pimcore.plugin.datadefinitions.setters.localizedfield = Class.create(pimcore.plugin.datadefinitions.setters.abstract, {
    getLayout: function (fromColumn, toColumn, record, config) {
        return [{
            xtype: 'textfield',
            fieldLabel: t('language'),
            name: 'language',
            width: 500,
            value: config.language ? config.language : null
        }];
    }
});
