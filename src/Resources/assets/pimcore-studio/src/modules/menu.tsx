/**
 * Data Definitions - Menu Module
 *
 * Registers navigation items for Import and Export Definitions in Pimcore Studio
 */

import { container } from '@pimcore/studio-ui-bundle'
import { serviceIds } from '@pimcore/studio-ui-bundle/app'
import { MainNavRegistry, type IMainNavItem } from '@pimcore/studio-ui-bundle/modules/app'
import { WidgetRegistry } from '@pimcore/studio-ui-bundle/modules/widget-manager'
import { ImportDefinitionManager } from '../components/import/ImportDefinitionManager'
import { ExportDefinitionManager } from '../components/export/ExportDefinitionManager'
import { RunsOverview } from '../components/runs/RunsOverview'

export const DataDefinitionsMenuModule = {
  onInit(): void {
    const mainNavRegistry = container.get<MainNavRegistry>(serviceIds.mainNavRegistry)
    const widgetRegistry = container.get<WidgetRegistry>(serviceIds.widgetManager)

    // Register Import Definitions widget
    widgetRegistry.registerWidget({
      name: 'data-definitions-import',
      component: ImportDefinitionManager
    })

    // Register Export Definitions widget
    widgetRegistry.registerWidget({
      name: 'data-definitions-export',
      component: ExportDefinitionManager
    })

    // Register run history widget (all import/export runs)
    widgetRegistry.registerWidget({
      name: 'data-definitions-runs',
      component: RunsOverview
    })

    // Register main navigation group for Data Definitions. Sits under
    // AutomationIntegration next to Data Hub (which registers
    // AutomationIntegration/DataHub with order 100).
    const dataDefinitionsNav: IMainNavItem = {
      path: 'AutomationIntegration/Data Definitions',
      label: 'data_definitions.menu.group',
      order: 110
    }
    mainNavRegistry.registerMainNavItem(dataDefinitionsNav)

    // Register Import Definitions navigation item
    const importNav: IMainNavItem = {
      path: 'AutomationIntegration/Data Definitions/Import Definitions',
      label: 'data_definitions.menu.import',
      order: 10,
      icon: 'data_definitions_icon_import_definition',
      widgetConfig: {
        name: 'Import Definitions',
        id: 'data-definitions-import',
        component: 'data-definitions-import',
        config: {
          translationKey: 'data_definitions.menu.import',
          icon: {
            type: 'name',
            value: 'data_definitions_icon_import_definition'
          }
        }
      }
    }
    mainNavRegistry.registerMainNavItem(importNav)

    // Register Export Definitions navigation item
    const exportNav: IMainNavItem = {
      path: 'AutomationIntegration/Data Definitions/Export Definitions',
      label: 'data_definitions.menu.export',
      order: 20,
      icon: 'data_definitions_icon_export_definition',
      widgetConfig: {
        name: 'Export Definitions',
        id: 'data-definitions-export',
        component: 'data-definitions-export',
        config: {
          translationKey: 'data_definitions.menu.export',
          icon: {
            type: 'name',
            value: 'data_definitions_icon_export_definition'
          }
        }
      }
    }
    mainNavRegistry.registerMainNavItem(exportNav)

    // Register run history navigation item
    const runsNav: IMainNavItem = {
      path: 'AutomationIntegration/Data Definitions/Runs',
      label: 'data_definitions.runs.title',
      order: 30,
      icon: 'data_definitions_icon_import_definition',
      widgetConfig: {
        name: 'Data Definition Runs',
        id: 'data-definitions-runs',
        component: 'data-definitions-runs',
        config: {
          translationKey: 'data_definitions.runs.title',
          icon: {
            type: 'name',
            value: 'data_definitions_icon_import_definition'
          }
        }
      }
    }
    mainNavRegistry.registerMainNavItem(runsNav)
  }
}
