![DataDefinitions](docs/images/github_banner.png "Data Definitions")

Data Definitions allows you to define your DataObject Imports and Exports using a nice GUI and re-run the definitions
as often you like. Everything within Data Definitions is extendable.

![Interface](docs/images/mapping.png)

## Requirements
* Pimcore 2026 (Pimcore Studio)

## Getting started
```bash
# Install via composer
composer require cors/data-definitions
```

```php
// Add to config/bundles.php
return [
    // ...
    \Instride\Bundle\DataDefinitionsBundle\DataDefinitionsBundle::class => ['all' => true]
];
```

```bash
# Install the bundle via command-line
bin/console pimcore:bundle:install DataDefinitionsBundle
```

 * Reload Pimcore
 * Open Settings -> Import Definitions or Export Definitions

## Documentation
 - [Import Definitions](./docs/imports.md)
 - [Export Definitions](./docs/exports.md)

## Development

The repository doubles as a runnable Pimcore application on **Pimcore 2026 with Studio** (the
CORS bundle template: `Kernel.php`, `bin/console`, `config/`, `dev/`, `docker-compose.yaml`
including the shared `dev-compose` stack). The bundle itself is `src`;
`.gitattributes` keeps the harness files out of the distributed composer package.

```bash
docker compose up -d
docker compose exec -T php composer install
docker compose exec -T php vendor/bin/pimcore-install \
    --install-profile='App\InstallProfile\StudioInstallProfile' \
    --admin-username=admin --admin-password=admin --no-interaction
docker compose exec -T php bin/console pimcore:bundle:install DataDefinitionsBundle
docker compose exec -T php bin/console assets:install --symlink --relative public
```

Studio is then served at `https://cors-pimcore-data-definitions.dev.localhost/pimcore-studio/`.

The install profile (`dev/InstallProfile/StudioInstallProfile.php`) declares the bundles and
infrastructure the harness needs (Studio backend/UI, generic data index + OpenSearch, Mercure,
Doctrine messenger transport). Connection defaults point at the dev-compose services and live
in `.env`; the Pimcore bundles are registered in `config/bundles.php`, the bundle under
development in `Kernel.php`.

Pimcore 2026 refuses to boot without a registered instance: the instance identifier
(`cors-pimcore-data-definitions`) is committed in `.env`, and `PIMCORE_ENCRYPTION_SECRET` plus
`PIMCORE_PRODUCT_KEY` go into your uncommitted `.env.local`. Register the identifier at
[license.pimcore.com](https://license.pimcore.com) to obtain them; CI reads the same three
values from the repository secrets `PIMCORE_ENCRYPTION_SECRET`, `PIMCORE_INSTANCE_IDENTIFIER`
and `PIMCORE_PRODUCT_KEY` (`.github/workflows/static.yaml` writes them to `.env.local` through the
shared `php-test` workflow, `.github/workflows/behat.yml` passes them as environment variables).

Static checks run the same way as in CI (`ecs.php`, `phpstan.neon` and `psalm.xml` import the
public `coreshop/test-setup` rule set, because the shared `cors/dev` package is not available to a
public repository):

```bash
vendor/bin/ecs check src
vendor/bin/phpstan analyse
vendor/bin/psalm
```

The Behat suites (`features/`) run against the same kernel in the `test` environment:

```bash
vendor/bin/behat --config behat.yml.dist -p default
```

### Studio frontend build

```bash
npm ci
npm run build
```

The build is shipped as an archive in `src/Resources/build-dist/` and is
committed by the `Studio Frontend Build` workflow on pushes to a version branch.

## License
**CORS GmbH**  
[cors.gmbh](https://www.cors.gmbh), office@cors.gmbh  
Copyright © CORS GmbH. All rights reserved.

For licensing details please visit [LICENSE.md](LICENSE.md)
