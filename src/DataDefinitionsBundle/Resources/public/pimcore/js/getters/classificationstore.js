/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.getters.classificationstore');

pimcore.plugin.datadefinitions.getters.classificationstore = Class.create(pimcore.plugin.datadefinitions.setters.abstract, {
    getLayout: function (fromColumn, toColumn, record, config) {
        this.fromColumn = fromColumn;

        return [];
    },

    getGetterData: function () {
        return {
            'keyConfig': this.fromColumn.config.keyId,
            'groupConfig': this.fromColumn.config.groupId,
            'field': this.fromColumn.config.field
        };
    }
});
