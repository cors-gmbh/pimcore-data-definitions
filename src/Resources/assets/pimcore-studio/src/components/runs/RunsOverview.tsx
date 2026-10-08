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

import React from 'react'
import { Tabs } from 'antd'
import { createStyles } from 'antd-style'
import { useTranslation } from 'react-i18next'
import { RunHistory } from './RunHistory'

/**
 * Widget with the runs of all import and export definitions.
 */
const useStyles = createStyles(({ css }) => ({
  tabs: css`
    height: 100%;
    display: flex;
    flex-direction: column;

    .ant-tabs-content-holder,
    .ant-tabs-content,
    .ant-tabs-tabpane {
      flex: 1;
      min-height: 0;
      height: 100%;
    }
  `
}))

export const RunsOverview: React.FC = () => {
  const { t } = useTranslation()
  const { styles } = useStyles()

  return (
    <Tabs
      className={styles.tabs}
      tabBarStyle={{ paddingLeft: 24, paddingRight: 24, marginBottom: 0 }}
      items={[
        { key: 'import', label: t('data_definitions.menu.import'), children: <RunHistory type="import" /> },
        { key: 'export', label: t('data_definitions.menu.export'), children: <RunHistory type="export" /> }
      ]}
    />
  )
}
