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

import React, { useState } from 'react'
import { Button, Tooltip } from 'antd'
import { CaretRightOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import type { RunType } from '../../types/runs'
import { StartRunModal } from './StartRunModal'

interface StartRunButtonProps {
  type: RunType
  definitionId?: number
  definitionName?: string
  /** a run uses the saved definition, unsaved changes would be ignored */
  dirty: boolean
}

export const StartRunButton: React.FC<StartRunButtonProps> = ({ type, definitionId, definitionName, dirty }) => {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  if (definitionId === undefined) return null

  return (
    <>
      <Tooltip title={dirty ? t('data_definitions.runs.save_first') : undefined}>
        <Button icon={<CaretRightOutlined />} disabled={dirty} onClick={() => { setOpen(true) }}>
          {t(`data_definitions.runs.start_${type}`)}
        </Button>
      </Tooltip>
      <StartRunModal
        open={open}
        type={type}
        definitionId={definitionId}
        definitionName={definitionName}
        onClose={() => { setOpen(false) }}
      />
    </>
  )
}
