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
import { Button, Divider, Form, Input, InputNumber, Select, Space, Switch, Upload, message } from 'antd'
import { UploadOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { runApi } from '../../services/api'
import type { ParamField, RunType } from '../../types/runs'

interface ParamsFormProps {
  type: RunType
  fields: ParamField[]
  params: Record<string, unknown>
  onChange: (params: Record<string, unknown>) => void
}

/**
 * Renders the params schema of a definition. Values are written straight into the params object,
 * which stays the single source of truth (the JSON editor edits the same object).
 */
export const ParamsForm: React.FC<ParamsFormProps> = ({ type, fields, params, onChange }) => {
  const { t } = useTranslation()

  const setValue = (name: string, value: unknown): void => {
    const next = { ...params }
    if (value === undefined || value === null || value === '') {
      delete next[name]
    } else {
      next[name] = value
    }
    onChange(next)
  }

  const groups: Array<[string, ParamField[]]> = []
  fields.forEach(field => {
    const group = field.group ?? ''
    const existing = groups.find(([name]) => name === group)
    if (existing !== undefined) {
      existing[1].push(field)
    } else {
      groups.push([group, [field]])
    }
  })

  return (
    <Form layout="vertical">
      {groups.map(([group, groupFields]) => (
        <React.Fragment key={group}>
          {group !== '' && <Divider orientation="left" plain>{t(`data_definitions.runs.group.${group}`, { defaultValue: group })}</Divider>}
          {groupFields.map(field => (
            <Form.Item
              key={field.name}
              label={`${field.label} (${field.name})`}
              required={field.required}
              tooltip={field.description ?? undefined}
            >
              <ParamInput
                type={type}
                field={field}
                value={params[field.name]}
                onChange={value => { setValue(field.name, value) }}
              />
            </Form.Item>
          ))}
        </React.Fragment>
      ))}
    </Form>
  )
}

interface ParamInputProps {
  type: RunType
  field: ParamField
  value: unknown
  onChange: (value: unknown) => void
}

const ParamInput: React.FC<ParamInputProps> = ({ type, field, value, onChange }) => {
  switch (field.type) {
    case 'number':
    case 'object':
      return (
        <InputNumber
          style={{ width: '100%' }}
          value={typeof value === 'number' ? value : (value !== undefined && value !== null && value !== '' ? Number(value) : null)}
          onChange={next => { onChange(next ?? undefined) }}
        />
      )
    case 'boolean':
      return <Switch checked={value === true || value === 'true'} onChange={checked => { onChange(checked) }} />
    case 'select':
      return (
        <Select
          allowClear
          value={value as string | number | undefined}
          options={field.options}
          onChange={next => { onChange(next) }}
        />
      )
    case 'multiselect':
      return (
        <Select
          mode="multiple"
          allowClear
          value={Array.isArray(value) ? value : []}
          options={field.options}
          onChange={next => { onChange(next.length > 0 ? next : undefined) }}
        />
      )
    case 'textarea':
      return <Input.TextArea autoSize={{ minRows: 3 }} value={toText(value)} onChange={e => { onChange(e.target.value) }} />
    case 'date':
      return <Input type="date" value={toText(value)} onChange={e => { onChange(e.target.value) }} />
    case 'asset':
      return <AssetInput type={type} value={toText(value)} onChange={onChange} />
    case 'json':
      return <JsonInput value={value} onChange={onChange} />
    default:
      return <Input value={toText(value)} onChange={e => { onChange(e.target.value) }} />
  }
}

const AssetInput: React.FC<{ type: RunType, value: string, onChange: (value: unknown) => void }> = ({ type, value, onChange }) => {
  const { t } = useTranslation()
  const [uploading, setUploading] = useState(false)

  return (
    <Space.Compact style={{ width: '100%' }}>
      <Input
        value={value}
        placeholder="/path/to/file.csv"
        onChange={e => { onChange(e.target.value) }}
      />
      <Upload
        showUploadList={false}
        customRequest={({ file }) => {
          setUploading(true)
          runApi.upload(type, file as File)
            .then(path => { onChange(path) })
            .catch((error: unknown) => {
              message.error(error instanceof Error ? error.message : String(error), 6)
            })
            .finally(() => { setUploading(false) })
        }}
      >
        <Button icon={<UploadOutlined />} loading={uploading}>{t('data_definitions.runs.upload')}</Button>
      </Upload>
    </Space.Compact>
  )
}

const JsonInput: React.FC<{ value: unknown, onChange: (value: unknown) => void }> = ({ value, onChange }) => {
  const [text, setText] = useState(() => value === undefined ? '' : JSON.stringify(value, null, 2))
  const [invalid, setInvalid] = useState(false)

  return (
    <Input.TextArea
      autoSize={{ minRows: 2, maxRows: 12 }}
      status={invalid ? 'error' : undefined}
      value={text}
      style={{ fontFamily: 'monospace' }}
      onChange={e => {
        const next = e.target.value
        setText(next)
        if (next.trim() === '') {
          setInvalid(false)
          onChange(undefined)
          return
        }
        try {
          onChange(JSON.parse(next))
          setInvalid(false)
        } catch {
          setInvalid(true)
        }
      }}
    />
  )
}

function toText (value: unknown): string {
  if (value === undefined || value === null) return ''
  return typeof value === 'string' ? value : JSON.stringify(value)
}
