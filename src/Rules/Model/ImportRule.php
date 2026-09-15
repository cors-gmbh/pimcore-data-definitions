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

namespace Instride\Bundle\DataDefinitionsBundle\Rules\Model;

use CoreShop\Component\Rule\Model\RuleTrait;

final class ImportRule implements ImportRuleInterface
{
    use RuleTrait;

    protected int $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
