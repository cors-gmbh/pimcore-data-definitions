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

namespace Instride\Bundle\DataDefinitionsBundle\Logging;

use Instride\Bundle\DataDefinitionsBundle\Run\RunTracker;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Pushed onto the import_definition and export_definition channel loggers. While a run is tracked in this
 * process, every record of those channels (Importer, Exporter and every logger-aware cleaner, filter, runner,
 * interpreter, setter, ...) is stored with the run. Records keep bubbling to the regular handlers.
 */
final class RunLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private RunTracker $tracker,
        string $level = 'info',
    ) {
        parent::__construct(Level::fromName($level), true);
    }

    #[\Override]
    public function isHandling(LogRecord $record): bool
    {
        return $this->tracker->isActive() && parent::isHandling($record);
    }

    #[\Override]
    protected function write(LogRecord $record): void
    {
        $this->tracker->addLog(
            $record->level->toPsrLogLevel(),
            $record->message,
            $record->context,
        );
    }
}
