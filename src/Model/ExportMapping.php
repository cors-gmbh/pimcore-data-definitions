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

namespace Instride\Bundle\DataDefinitionsBundle\Model;

class ExportMapping extends AbstractMapping
{
    /**
     * @var string
     */
    public $getter;

    /**
     * @var array
     */
    public $getterConfig;

    /**
     * @return string|null
     */
    public function getGetter()
    {
        return $this->getter;
    }

    /**
     * @param string $getter
     */
    public function setGetter($getter)
    {
        $this->getter = $getter;
    }

    /**
     * @return array|null
     */
    public function getGetterConfig()
    {
        return $this->getterConfig;
    }

    /**
     * @param array $getterConfig
     */
    public function setGetterConfig($getterConfig)
    {
        $this->getterConfig = $getterConfig;
    }
}
