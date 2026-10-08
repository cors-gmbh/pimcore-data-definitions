/**
 * Data Definitions Bundle - Pimcore Studio Plugin
 *
 * This source file is available under the Data Definitions Commercial License (DDCL).
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

import {
  AbstractMessageHandler,
  useGlobalMessageBus,
  type AbstractMercureMessage
} from '@pimcore/studio-ui-bundle/modules/global-message-bus'
import { runEvents, type RunUpdate } from '../components/runs/runEvents'

/**
 * Private Mercure topics the backend publishes run updates to (Studio\Mercure\RunTopics).
 * The subscriber JWT only contains the topics of the run types the user may see.
 */
const TOPICS = ['data-definitions/runs/import', 'data-definitions/runs/export']

interface RunMessagePayload {
  dataDefinitionsRun?: unknown
  run?: unknown
}

class RunUpdateMessageHandler extends AbstractMessageHandler {
  getId (): string {
    return 'data-definitions-run-updates'
  }

  shouldHandle (message: AbstractMercureMessage): boolean {
    const payload = message.payload as RunMessagePayload | null
    return typeof payload?.dataDefinitionsRun === 'string' && typeof payload.run === 'object' && payload.run !== null
  }

  async handleMessage (message: AbstractMercureMessage): Promise<void> {
    const payload = message.payload as { dataDefinitionsRun: RunUpdate['event'], run: RunUpdate['run'] }
    runEvents.emitUpdate({ event: payload.dataDefinitionsRun, run: payload.run })
  }
}

export const DataDefinitionsRunUpdatesModule = {
  /**
   * Topics have to be registered before the global subscription starts (plugin onInit).
   */
  onInit (): void {
    const messageBus = useGlobalMessageBus()

    try {
      messageBus.registerTopics(TOPICS)
    } catch (error) {
      console.warn('Data Definitions: run updates via Mercure are not available, falling back to polling', error)
    }

    messageBus.registerHandler(new RunUpdateMessageHandler())
  }
}
