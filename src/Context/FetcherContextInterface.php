<?php

declare(strict_types=1);

/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Context;

use Instride\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;

interface FetcherContextInterface extends ContextInterface
{
    public function getDefinition(): ExportDefinitionInterface;
}
