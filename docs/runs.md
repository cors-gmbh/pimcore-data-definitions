# Runs: history, logs and starting from Studio

Every execution of an import or export definition is recorded as a **run**:

- status (`queued`, `running`, `stopping`, `finished`, `finished_with_errors`, `failed`, `cancelled`)
- who started it (`gui`, `cli`, `api`, `rerun`) and the Pimcore user
- the params, stored exactly as given (any JSON structure) and passed unchanged to the import/export
- progress (`total` / `processed`) and counters (created, updated, skipped, errors; exported for exports)
- the log of the run

Runs are stored in the tables `data_definitions_run` and `data_definitions_run_log`
(Doctrine entities `Entity\Run` and `Entity\RunLog`).

## Studio

- **Run import / Run export** in the footer of a definition opens the start dialog. It renders a form for all
  params the definition's services describe (see below) and a JSON editor for the complete params object, so params
  without a form field can always be passed. Files can be uploaded, they are stored as asset in the upload folder
  and passed as `asset` param.
- The **Runs** tab of a definition shows its history with live progress, the params of every run, the log
  (filter by level, full-text search, context and exception per record), stop, re-run and re-run with changed params.
- *Automation / Integration → Data Definitions → Runs* lists the runs of all definitions.

Runs started from Studio are executed by a worker by default:

```cli
bin/console messenger:consume data_definitions_run
```

The transport never retries: a failed run is recorded as `failed` and can be re-run. A run can also be executed
synchronously in the request ("Run in the background" switched off), which is only meant for small runs.

## Logging

Every log record of the `import_definition` and `export_definition` Monolog channels written while a run executes
is stored with the run (the Importer, Exporter and every logger-aware cleaner, filter, runner, interpreter, setter, …).
Records still reach the regular Monolog handlers. Failed rows are logged with their row index and the exception.

## Stopping

Stopping a queued run cancels it, stopping a running run sets it to `stopping`. The run finishes the current row
and ends as `cancelled`.

## Describing run params

Any service used by a definition (provider, fetcher, loader, filter, runner, cleaner, persister, interpreter,
setter, getter) can describe the params it reads by implementing `ParamsSchemaProviderInterface`:

```php
use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamField;
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamsSchemaProviderInterface;

final class MyRunner implements RunnerInterface, ParamsSchemaProviderInterface
{
    public function getParamsSchema(DataDefinitionInterface $definition): array
    {
        return [
            new ParamField('channel', 'select', 'Channel', required: true, options: ['b2b' => 'B2B', 'b2c' => 'B2C']),
            new ParamField('since', 'date', 'Changed since'),
            new ParamField('mapping', 'json', 'Extra mapping', description: 'Any JSON structure'),
        ];
    }
}
```

Field types: `text`, `textarea`, `number`, `boolean`, `select`, `multiselect`, `date`, `asset` (path + upload),
`object` (ID), `json` (any structure).

Projects can add, replace or remove fields per definition with the `data_definitions.run.params_schema` event:

```php
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamsSchemaEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'data_definitions.run.params_schema')]
final class ParamsSchemaListener
{
    public function __invoke(ParamsSchemaEvent $event): void
    {
        if ($event->getDefinition()->getName() === 'products') {
            $event->addField(['name' => 'supplier', 'type' => 'text', 'required' => true]);
        }
    }
}
```

Inside the import/export the run id is available as `$params['runId']`.

## Starting runs programmatically

```php
use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Run\RunManager;
use Instride\Bundle\DataDefinitionsBundle\Run\RunTrigger;

$definition = $runManager->getDefinition(Run::TYPE_IMPORT, 'products');
$run = $runManager->start(Run::TYPE_IMPORT, $definition, ['asset' => '/imports/products.csv'], RunTrigger::API, $userId);
```

## Configuration

```yaml
data_definitions:
    runs:
        log_level: info              # minimum level stored per run
        max_log_entries: 50000       # per run, 0 = unlimited
        retention_days: 30           # finished runs are removed by the maintenance task, 0 = keep forever
        upload_folder: '/Data Definitions/Uploads'
```

## Update

Existing installations create the tables with

```cli
bin/console doctrine:migrations:migrate --prefix='Instride\Bundle\DataDefinitionsBundle\Migrations'
```
