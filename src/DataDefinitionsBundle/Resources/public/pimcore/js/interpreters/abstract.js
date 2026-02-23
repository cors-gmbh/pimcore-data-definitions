/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

pimcore.registerNS('pimcore.plugin.datadefinitions.interpreters');
pimcore.registerNS('pimcore.plugin.datadefinitions.interpreters.abstract');

pimcore.plugin.datadefinitions.interpreters.abstract = Class.create({
    getLayout: function () {
        return [];
    }
});
