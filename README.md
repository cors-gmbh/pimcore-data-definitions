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

## License
**CORS GmbH**  
[cors.gmbh](https://www.cors.gmbh), office@cors.gmbh  
Copyright © CORS GmbH. All rights reserved.

For licensing details please visit [LICENSE.md](LICENSE.md)
