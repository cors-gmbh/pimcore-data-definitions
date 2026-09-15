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

namespace Instride\Bundle\DataDefinitionsBundle\Provider;

use function count;
use Instride\Bundle\DataDefinitionsBundle\Filter\FilterInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportMapping\FromColumn;

final class RawProvider implements ImportProviderInterface
{
    #[\Override]
    public function testData(array $configuration): bool
    {
        return true;
    }

    #[\Override]
    public function getColumns(array $configuration): array
    {
        $headers = explode(',', $configuration['headers']);
        $returnHeaders = [];

        if (count($headers) > 0) {
            //First line are the headers
            foreach ($headers as $header) {
                if (!$header) {
                    continue;
                }

                $headerObj = new FromColumn();
                $headerObj->setIdentifier($header);
                $headerObj->setLabel($header);

                $returnHeaders[] = $headerObj;
            }
        }

        return $returnHeaders;
    }

    #[\Override]
    public function getData(
        array $configuration,
        ImportDefinitionInterface $definition,
        array $params,
        ?FilterInterface $filter = null,
    ): ImportDataSetInterface {
        return new ArrayImportDataSet($params['data']);
    }
}
