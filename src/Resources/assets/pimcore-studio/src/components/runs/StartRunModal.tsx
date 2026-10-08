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

import React, { useEffect, useState } from 'react'
import { Alert, Input, Modal, Space, Spin, Switch, Tabs, Typography, message } from 'antd'
import { useTranslation } from 'react-i18next'
import { runApi } from '../../services/api'
import type { ParamField, Run, RunType } from '../../types/runs'
import { ParamsForm } from './ParamsForm'
import { runEvents } from './runEvents'

interface StartRunModalProps {
  open: boolean
  type: RunType
  definitionId: number
  definitionName?: string
  initialParams?: Record<string, unknown>
  onClose: () => void
  onStarted?: (run: Run) => void
}

/**
 * Collects the run params (schema form + free JSON) and starts the run, by default on the worker.
 */
export const StartRunModal: React.FC<StartRunModalProps> = ({
  open,
  type,
  definitionId,
  definitionName,
  initialParams,
  onClose,
  onStarted
}) => {
  const { t } = useTranslation()
  const [fields, setFields] = useState<ParamField[]>([])
  const [loading, setLoading] = useState(false)
  const [params, setParams] = useState<Record<string, unknown>>({})
  const [jsonText, setJsonText] = useState('{}')
  const [jsonError, setJsonError] = useState<string | null>(null)
  const [formVersion, setFormVersion] = useState(0)
  const [activeTab, setActiveTab] = useState('form')
  const [runAsync, setRunAsync] = useState(true)
  const [starting, setStarting] = useState(false)

  useEffect(() => {
    if (!open) return

    setLoading(true)
    setActiveTab('form')
    setJsonError(null)
    runApi.paramsSchema(type, definitionId)
      .then(schema => {
        setFields(schema)
        const defaults: Record<string, unknown> = {}
        schema.forEach(field => {
          if (field.default !== null && field.default !== undefined) {
            defaults[field.name] = field.default
          }
        })
        const merged = { ...defaults, ...(initialParams ?? {}) }
        delete merged.runId
        setParams(merged)
        setFormVersion(version => version + 1)
        if (schema.length === 0) setActiveTab('json')
      })
      .catch((error: unknown) => {
        setFields([])
        setParams({ ...(initialParams ?? {}) })
        setActiveTab('json')
        message.error(error instanceof Error ? error.message : String(error), 6)
      })
      .finally(() => { setLoading(false) })
  }, [open, type, definitionId, initialParams])

  const switchTab = (key: string): void => {
    if (key === 'json') {
      setJsonText(JSON.stringify(params, null, 2))
      setJsonError(null)
    } else {
      // remount the form so inputs with local state (JSON fields) pick up changes made in the editor
      setFormVersion(version => version + 1)
    }
    setActiveTab(key)
  }

  const handleJsonChange = (text: string): void => {
    setJsonText(text)
    try {
      const parsed = JSON.parse(text === '' ? '{}' : text)
      if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
        setJsonError(t('data_definitions.runs.json_object_required'))
        return
      }
      setJsonError(null)
      setParams(parsed as Record<string, unknown>)
    } catch (error) {
      setJsonError(error instanceof Error ? error.message : String(error))
    }
  }

  const missing = fields.filter(field => field.required && (params[field.name] === undefined || params[field.name] === ''))

  const handleStart = async (): Promise<void> => {
    setStarting(true)
    try {
      const run = await runApi.start(type, definitionId, params, runAsync)
      runEvents.emitStarted(run)
      onStarted?.(run)
      message.success(t('data_definitions.runs.started', { id: run.id }))
      onClose()
    } catch (error) {
      message.error(error instanceof Error ? error.message : String(error), 8)
    } finally {
      setStarting(false)
    }
  }

  return (
    <Modal
      open={open}
      width={720}
      title={`${t(`data_definitions.runs.start_${type}`)}${definitionName !== undefined ? `: ${definitionName}` : ''}`}
      okText={t('data_definitions.runs.start')}
      cancelText={t('data_definitions.cancel')}
      onOk={() => { void handleStart() }}
      onCancel={onClose}
      confirmLoading={starting}
      okButtonProps={{ disabled: loading || jsonError !== null || missing.length > 0 }}
      destroyOnClose
    >
      {loading
        ? <div style={{ textAlign: 'center', padding: 32 }}><Spin /></div>
        : (
          <Space direction="vertical" style={{ width: '100%' }}>
            <Tabs
              activeKey={activeTab}
              onChange={switchTab}
              items={[
                {
                  key: 'form',
                  label: t('data_definitions.runs.parameters'),
                  disabled: fields.length === 0,
                  children: (
                    <ParamsForm
                      key={formVersion}
                      type={type}
                      fields={fields}
                      params={params}
                      onChange={setParams}
                    />
                  )
                },
                {
                  key: 'json',
                  label: t('data_definitions.runs.json'),
                  children: (
                    <Space direction="vertical" style={{ width: '100%' }}>
                      <Typography.Text type="secondary">{t('data_definitions.runs.json_hint')}</Typography.Text>
                      <Input.TextArea
                        value={jsonText}
                        status={jsonError !== null ? 'error' : undefined}
                        autoSize={{ minRows: 8, maxRows: 24 }}
                        style={{ fontFamily: 'monospace' }}
                        onChange={e => { handleJsonChange(e.target.value) }}
                      />
                      {jsonError !== null && <Typography.Text type="danger">{jsonError}</Typography.Text>}
                    </Space>
                  )
                }
              ]}
            />
            {missing.length > 0 && (
              <Alert
                type="warning"
                showIcon
                message={t('data_definitions.runs.required_missing', { fields: missing.map(field => field.label).join(', ') })}
              />
            )}
            <Space>
              <Switch checked={runAsync} onChange={setRunAsync} />
              <span>{t('data_definitions.runs.async')}</span>
            </Space>
            {!runAsync && <Alert type="info" showIcon message={t('data_definitions.runs.sync_hint')} />}
          </Space>
          )}
    </Modal>
  )
}
