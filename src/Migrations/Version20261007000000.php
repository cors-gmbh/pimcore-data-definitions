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

namespace Instride\Bundle\DataDefinitionsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007000000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Data Definitions: add run history and run log tables';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        foreach (['run.sql', 'run_log.sql'] as $file) {
            $sql = file_get_contents(__DIR__ . '/../Resources/install/pimcore/sql/' . $file);

            if ($sql) {
                $this->addSql($sql);
            }
        }
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `data_definitions_run_log`');
        $this->addSql('DROP TABLE IF EXISTS `data_definitions_run`');
    }
}
