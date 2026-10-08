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
import { Button, Modal, Pagination, Popover, Progress, Space, Table, Tag, Tooltip, Typography, message } from 'antd'
import { createStyles } from 'antd-style'
import {
  DeleteOutlined,
  EditOutlined,
  FileTextOutlined,
  ReloadOutlined,
  RedoOutlined,
  StopOutlined
} from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { runApi } from '../../services/api'
import type { Run, RunType } from '../../types/runs'
import { STATUS_COLORS, formatDuration, formatTimestamp, progressPercent } from './format'
import { RunLogDrawer } from './RunLogDrawer'
import { StartRunModal } from './StartRunModal'
import { runEvents } from './runEvents'

interface RunHistoryProps {
  type: RunType
  /** omit to list the runs of all definitions */
  definitionId?: number
  definitionName?: string
}

const PAGE_SIZE = 25

const useStyles = createStyles(({ css, token }) => ({
  root: css`
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
  `,
  list: css`
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 16px 24px 0;
  `,
  footer: css`
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 24px;
    border-top: 1px solid ${token.colorBorderSecondary};
    background: ${token.colorBgContainer};
  `
}))
// live updates come via Mercure, polling is only the fallback when the hub is unreachable
const POLL_ACTIVE = 15000

/**
 * Run history of a definition: status, progress, counters, params and logs, with stop / re-run actions.
 * Polls while a run is active.
 */
export const RunHistory: React.FC<RunHistoryProps> = ({ type, definitionId, definitionName }) => {
  const { t } = useTranslation()
  const { styles } = useStyles()
  const [runs, setRuns] = useState<Run[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [logRun, setLogRun] = useState<Run | null>(null)
  const [startOpen, setStartOpen] = useState(false)
  const [startParams, setStartParams] = useState<Record<string, unknown> | undefined>(undefined)
  const [startDefinition, setStartDefinition] = useState<{ id: number, name?: string } | null>(null)
  const timer = useRef<number | undefined>(undefined)

  const load = useCallback(async (silent = false) => {
    if (!silent) setLoading(true)
    try {
      const result = await runApi.list({ type, definition: definitionId, limit: PAGE_SIZE, offset: (page - 1) * PAGE_SIZE })
      setRuns(result.data)
      setTotal(result.total)
    } catch (error) {
      if (!silent) message.error(error instanceof Error ? error.message : String(error), 6)
    } finally {
      if (!silent) setLoading(false)
    }
  }, [type, definitionId, page])

  useEffect(() => { void load() }, [load])

  // poll while something is queued or running
  useEffect(() => {
    window.clearTimeout(timer.current)
    if (runs.some(run => run.active)) {
      timer.current = window.setTimeout(() => { void load(true) }, POLL_ACTIVE)
    }
    return () => { window.clearTimeout(timer.current) }
  }, [runs, load])

  useEffect(() => runEvents.onUpdate(({ event, run }) => {
    if (run.type !== type || (definitionId !== undefined && run.definition !== undefined && run.definition !== definitionId)) {
      return
    }

    if (event === 'deleted') {
      setRuns(current => {
        if (!current.some(item => item.id === run.id)) return current
        setTotal(value => Math.max(0, value - 1))
        return current.filter(item => item.id !== run.id)
      })
      return
    }

    setRuns(current => {
      const index = current.findIndex(item => item.id === run.id)

      if (index === -1) {
        // a new run of this list, only "changed" carries the complete run
        if (event === 'changed' && page === 1) {
          setTotal(value => value + 1)
          return [run as Run, ...current].slice(0, PAGE_SIZE)
        }
        return current
      }

      const next = [...current]
      next[index] = { ...current[index], ...run }
      return next
    })
  }), [type, definitionId, page])

  useEffect(() => runEvents.onStarted(run => {
    if (run.type === type && (definitionId === undefined || run.definition === definitionId)) {
      setPage(1)
      void load(true)
    }
  }), [type, definitionId, load])

  const act = async (action: () => Promise<unknown>, success: string): Promise<void> => {
    try {
      await action()
      message.success(success)
      await load(true)
    } catch (error) {
      message.error(error instanceof Error ? error.message : String(error), 6)
    }
  }

  const openStart = (run: Run): void => {
    setStartParams(run.params)
    setStartDefinition({ id: run.definition, name: run.definitionName ?? definitionName })
    setStartOpen(true)
  }

  const columns = [
    {
      title: '#',
      dataIndex: 'id',
      width: 70
    },
    ...(definitionId === undefined
      ? [{
          title: t('data_definitions.runs.definition'),
          dataIndex: 'definitionName',
          render: (value: string | null, run: Run) => value ?? `#${run.definition}`
        }]
      : []),
    {
      title: t('data_definitions.runs.status'),
      dataIndex: 'status',
      width: 220,
      render: (_: string, run: Run) => {
        const percent = progressPercent(run)
        return (
          <Space direction="vertical" size={2} style={{ width: '100%' }}>
            <Tooltip title={run.message ?? undefined}>
              <Tag color={STATUS_COLORS[run.status]}>{t(`data_definitions.runs.status_${run.status}`)}</Tag>
            </Tooltip>
            {(run.active || run.status === 'cancelled') && percent !== null && (
              <Progress percent={percent} size="small" status={run.active ? 'active' : 'normal'} />
            )}
          </Space>
        )
      }
    },
    {
      title: t('data_definitions.runs.progress'),
      width: 260,
      render: (_: unknown, run: Run) => (
        <Space size={4} wrap>
          <span>{run.processed}{run.total !== null ? ` / ${run.total}` : ''}</span>
          {type === 'import'
            ? (
              <>
                <Tag color="green">{t('data_definitions.runs.created')}: {run.createdCount}</Tag>
                <Tag color="blue">{t('data_definitions.runs.updated')}: {run.updatedCount}</Tag>
              </>
              )
            : <Tag color="green">{t('data_definitions.runs.exported')}: {run.createdCount}</Tag>}
          {run.skippedCount > 0 && <Tag>{t('data_definitions.runs.skipped')}: {run.skippedCount}</Tag>}
          {run.errorCount > 0 && <Tag color="red">{t('data_definitions.runs.errors')}: {run.errorCount}</Tag>}
        </Space>
      )
    },
    {
      title: t('data_definitions.runs.trigger'),
      dataIndex: 'trigger',
      width: 130,
      render: (value: string, run: Run) => (
        <Space direction="vertical" size={0}>
          <span>{t(`data_definitions.runs.trigger_${value}`, { defaultValue: value })}</span>
          {run.userName !== null && <Typography.Text type="secondary">{run.userName}</Typography.Text>}
        </Space>
      )
    },
    {
      title: t('data_definitions.runs.started_at'),
      width: 190,
      render: (_: unknown, run: Run) => (
        <Space direction="vertical" size={0}>
          <span>{formatTimestamp(run.startedAt ?? run.createdAt)}</span>
          <Typography.Text type="secondary">{formatDuration(run)}</Typography.Text>
        </Space>
      )
    },
    {
      title: '',
      width: 230,
      render: (_: unknown, run: Run) => (
        <Space size={0}>
          <Tooltip title={t('data_definitions.runs.logs')}>
            <Button type="text" icon={<FileTextOutlined />} onClick={() => { setLogRun(run) }}>
              {run.logCount}
            </Button>
          </Tooltip>
          <Popover
            trigger="click"
            title={t('data_definitions.runs.parameters')}
            content={<pre style={{ margin: 0, maxWidth: 500, maxHeight: 400, overflow: 'auto' }}>{JSON.stringify(run.params, null, 2)}</pre>}
          >
            <Button type="text">{'{ }'}</Button>
          </Popover>
          {run.active && (
            <Tooltip title={t('data_definitions.runs.stop')}>
              <Button
                type="text"
                danger
                icon={<StopOutlined />}
                disabled={run.status === 'stopping'}
                onClick={() => { void act(async () => await runApi.stop(run.id), t('data_definitions.runs.stop_requested')) }}
              />
            </Tooltip>
          )}
          <Tooltip title={t('data_definitions.runs.rerun')}>
            <Button
              type="text"
              icon={<RedoOutlined />}
              onClick={() => {
                Modal.confirm({
                  title: t('data_definitions.runs.rerun_confirm', { id: run.id }),
                  onOk: async () => { await act(async () => await runApi.rerun(run.id), t('data_definitions.runs.rerun_started')) }
                })
              }}
            />
          </Tooltip>
          <Tooltip title={t('data_definitions.runs.rerun_edit')}>
            <Button type="text" icon={<EditOutlined />} onClick={() => { openStart(run) }} />
          </Tooltip>
          {!run.active && (
            <Tooltip title={t('data_definitions.runs.delete')}>
              <Button
                type="text"
                danger
                icon={<DeleteOutlined />}
                onClick={() => {
                  Modal.confirm({
                    title: t('data_definitions.runs.delete_confirm', { id: run.id }),
                    okButtonProps: { danger: true },
                    onOk: async () => { await act(async () => { await runApi.delete(run.id) }, t('data_definitions.runs.deleted')) }
                  })
                }}
              />
            </Tooltip>
          )}
        </Space>
      )
    }
  ]

  return (
    <div className={styles.root}>
      <div className={styles.list}>
        <Table<Run>
          rowKey="id"
          size="small"
          loading={loading}
          dataSource={runs}
          columns={columns}
          sticky
          pagination={false}
        />
      </div>
      <div className={styles.footer}>
        <Pagination
          size="small"
          current={page}
          pageSize={PAGE_SIZE}
          total={total}
          showSizeChanger={false}
          hideOnSinglePage={false}
          showTotal={value => t('data_definitions.runs.total', { count: value })}
          onChange={setPage}
        />
        {/* starting a run lives in the definition footer ("Run import"), only re-runs start from here */}
        <Button icon={<ReloadOutlined />} onClick={() => { void load() }}>{t('data_definitions.runs.refresh')}</Button>
      </div>
      <RunLogDrawer run={logRun} onClose={() => { setLogRun(null) }} />
      {startDefinition !== null && (
        <StartRunModal
          open={startOpen}
          type={type}
          definitionId={startDefinition.id}
          definitionName={startDefinition.name}
          initialParams={startParams}
          onClose={() => { setStartOpen(false) }}
        />
      )}
    </div>
  )
}
