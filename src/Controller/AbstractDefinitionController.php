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

namespace Instride\Bundle\DataDefinitionsBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractDefinitionController extends ResourceController
{
    #[\Override]
    public function getAction(Request $request): JsonResponse
    {
        $this->isGrantedOr403();

        $resources = $this->findOr404((string) $this->getParameterFromRequest($request, 'id'));

        return $this->viewHandler->handle(['data' => $resources, 'success' => true], ['group' => 'Detailed']);
    }
}
