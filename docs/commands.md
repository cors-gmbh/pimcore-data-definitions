## List your Import Definitions (in CLI)

Run following command

```cli
bin/console data-definitions:list:imports
```

## List your Export Definitions (in CLI)

Run following command

```cli
bin/console data-definitions:list:exports
```

You can see the ID, the name and the Provider

## Run your Import Definition
Import Definitions can be started from Pimcore Studio ("Run import" button, see [Runs](./runs.md)) or using the Pimcore CLI. To run your definition, use following command

```cli
bin/console data-definitions:import -d 1 -p "{\"file\":\"test.json\"}"
bin/console data-definitions:import -d name-of-definition -p "{\"file\":\"test.json\"}"
```

## Run your Export Definition
Export Definitions can be started from Pimcore Studio ("Run export" button, see [Runs](./runs.md)) or using the Pimcore CLI. To run your definition, use following command

```cli
bin/console data-definitions:export -d 1 -p "{\"file\":\"test.json\"}"
bin/console data-definitions:export -d name-of-definition -p "{\"file\":\"test.json\"}"
```

Every execution is recorded as a run (status, counters, params and log, see [Runs](./runs.md)). Both commands support:

| Option         | Description                                                                                         |
|----------------|-----------------------------------------------------------------------------------------------------|
| `--async`      | Only queue the run, a worker executes it (`bin/console messenger:consume data_definitions_run`)      |
| `--no-history` | Execute directly without recording a run (previous behaviour, no run history and run log)           |

The command exits with code `1` when the run failed.
