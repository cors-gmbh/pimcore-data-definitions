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

import React, { useCallback, useEffect, useRef, useState } from 'react'
import { Button, Drawer, Input, Select, Space, Table, Tag, Typography, message } from 'antd'
import { ReloadOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { runApi } from '../../services/api'
import type { Run, RunLogEntry } from '../../types/runs'
import { LEVEL_COLORS, formatTimestamp } from './format'
import { runEvents } from './runEvents'

interface RunLogDrawerProps {
  run: Run | null
  onClose: () => void
}

const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']
const PAGE_SIZE = 100

export const RunLogDrawer: React.FC<RunLogDrawerProps> = ({ run, onClose }) => {
  const { t } = useTranslation()
  const [entries, setEntries] = useState<RunLogEntry[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [levels, setLevels] = useState<string[]>([])
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(false)

  const load = useCallback(async (silent = false) => {
    if (run === null) return
    if (!silent) setLoading(true)
    try {
      const result = await runApi.logs(run.id, {
        level: levels,
        search,
        limit: PAGE_SIZE,
        offset: (page - 1) * PAGE_SIZE
      })
      setEntries(result.data)
      setTotal(result.total)
    } catch (error) {
      message.error(error instanceof Error ? error.message : String(error), 6)
    } finally {
      if (!silent) setLoading(false)
    }
  }, [run, levels, search, page])

  useEffect(() => { void load() }, [load])

  // new log records of the open run arrive with the (throttled) progress updates
  const lastLogCount = useRef<number | null>(null)
  useEffect(() => {
    lastLogCount.current = run?.logCount ?? null
    return runEvents.onUpdate(({ run: update }) => {
      if (run === null || update.id !== run.id || update.logCount === undefined) return
      if (update.logCount === lastLogCount.current) return
      lastLogCount.current = update.logCount
      void load(true)
    })
  }, [run, load])

  useEffect(() => {
    setPage(1)
    setLevels([])
    setSearch('')
  }, [run?.id])

  return (
    <Drawer
      open={run !== null}
      width="70%"
      title={run !== null ? t('data_definitions.runs.log_title', { id: run.id }) : ''}
      onClose={onClose}
      extra={<Button icon={<ReloadOutlined />} onClick={() => { void load() }} />}
    >
      <Space style={{ marginBottom: 16 }} wrap>
        <Select
          mode="multiple"
          allowClear
          style={{ minWidth: 240 }}
          placeholder={t('data_definitions.runs.level')}
          value={levels}
          options={LEVELS.map(level => ({ value: level, label: level }))}
          onChange={value => { setPage(1); setLevels(value) }}
        />
        <Input.Search
          allowClear
          placeholder={t('data_definitions.runs.search')}
          onSearch={value => { setPage(1); setSearch(value) }}
          style={{ width: 300 }}
        />
      </Space>
      <Table<RunLogEntry>
        rowKey="id"
        size="small"
        loading={loading}
        dataSource={entries}
        pagination={{
          current: page,
          pageSize: PAGE_SIZE,
          total,
          showSizeChanger: false,
          onChange: setPage
        }}
        expandable={{
          rowExpandable: entry => entry.context !== null,
          expandedRowRender: entry => (
            <pre style={{ margin: 0, whiteSpace: 'pre-wrap', wordBreak: 'break-all', maxHeight: 400, overflow: 'auto' }}>
              {JSON.stringify(entry.context, null, 2)}
            </pre>
          )
        }}
        columns={[
          {
            title: t('data_definitions.runs.time'),
            dataIndex: 'createdAt',
            width: 170,
            render: (value: number) => formatTimestamp(value)
          },
          {
            title: t('data_definitions.runs.level'),
            dataIndex: 'level',
            width: 100,
            render: (value: string) => <Tag color={LEVEL_COLORS[value] ?? 'default'}>{value}</Tag>
          },
          {
            title: t('data_definitions.runs.row'),
            dataIndex: 'rowIndex',
            width: 70,
            render: (value: number | null) => value ?? '–'
          },
          {
            title: t('data_definitions.runs.message'),
            dataIndex: 'message',
            render: (value: string) => (
              <Typography.Paragraph style={{ margin: 0, whiteSpace: 'pre-wrap' }} ellipsis={{ rows: 3, expandable: true }}>
                {value}
              </Typography.Paragraph>
            )
          }
        ]}
      />
    </Drawer>
  )
}
