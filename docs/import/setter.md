## Setter
A Setter sets the data to the object as it would be needed.

 - Objectbrick -> saves the data to an objectbrick
 - Localizedfield -> saves the data to the specific language field
 - Classificationstore -> Saves the data to a classificationstore field
 - Fieldcollection -> Saves the data to a fieldcollection
 - Key -> Sets Object Key to a more dynamic value
 - ObjectType -> Sets Object Type to a more dynamic value

Of course, you can also implement your own Setters. Its basically the same as with Interpreters.

Todo that, you need to implement the interface ```Instride\Bundle\DataDefinitionsBundle\Setter\SetterInterface``` and create a service

```yml
acme_bundle.data_definitions.my_setter:
    class: AcmeBundle\DataDefinitions\MySetter
    tags:
      - { name: data_definitions.setter, type: mysetter, form-type: Instride\Bundle\DataDefinitionsBundle\Form\Type\NoConfigurationType }
```

If your Setter does have configuration as well, you need to create a new FormType and a
configuration component for Pimcore Studio.

The configuration UI is a React component in the Studio plugin. Register it in the
Data Definitions registry (see `Resources/assets/pimcore-studio/src/modules/registry-module.ts`
and `registry/service-ids.ts`): retrieve the registry via `container.get()` from
`@pimcore/studio-ui-bundle` and call `register('mysetter', YourConfigComponent)`. The
classic ExtJS `pimcore.plugin.datadefinitions.*` classes and the `pimcore_admin.js`
config entries are gone with Pimcore 2026.
