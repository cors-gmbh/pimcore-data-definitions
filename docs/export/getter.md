## Getter
A Getter gets the data to the data object

You can also implement your own Getters.

Todo that, you need to implement the interface ```Instride\Bundle\DataDefinitionsBundle\Getter\GetterInterface``` and create a service

```yml
acme_bundle.data_definitions.my_getter:
    class: AcmeBundle\DataDefinitions\MyGetter
    tags:
      - { name: data_definitions.getter, type: mygetter }
```

If your Getter does have configuration as well, you need to create a new FormType and a
configuration component for Pimcore Studio.

The configuration UI is a React component in the Studio plugin. Register it in the
Data Definitions registry (see `Resources/assets/pimcore-studio/src/modules/registry-module.ts`
and `registry/service-ids.ts`): retrieve the registry via `container.get()` from
`@pimcore/studio-ui-bundle` and call `register('mygetter', YourConfigComponent)`. The
classic ExtJS `pimcore.plugin.datadefinitions.*` classes and the `pimcore_admin.js`
config entries are gone with Pimcore 2026.
