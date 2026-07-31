/**
 * Data Definitions Bundle - Pimcore Studio Plugin
 *
 * This source file is available under the Data Definitions Commercial License (DDCL).
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://www.instride.ch)
 * @license    DDCL
 */

import React, { useState, useEffect, useCallback } from 'react'
import { Tabs, List, Button, Spin, message, Modal, Dropdown } from 'antd'
import { PlusOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons'
import { useTranslation } from 'react-i18next'
import { createStyles } from 'antd-style'

interface EntityWithId {
  id?: number
  name?: string
}

interface EntityApi<T extends EntityWithId> {
  list(): Promise<T[]>
  get(id: number): Promise<T>
  save(entity: T): Promise<T>
  delete(id: number): Promise<void>
}

export interface TabbedEntityManagerProps<T extends EntityWithId> {
  api: EntityApi<T>
  title: string
  onAdd: () => Promise<number>
  buildSavePayload?: (data: T) => Record<string, any>
  renderDetail: (data: T, setData: (data: T) => void) => React.ReactNode
  getTabTitle?: (item: T) => string
}

const useStyles = createStyles(({ css, token }) => ({
  container: css`
    display: flex;
    height: 100%;
    background: ${token.colorBgContainer};
  `,
  sidebar: css`
    width: 280px;
    border-right: 1px solid ${token.colorBorderSecondary};
    display: flex;
    flex-direction: column;
  `,
  sidebarHeader: css`
    padding: 16px;
    border-bottom: 1px solid ${token.colorBorderSecondary};
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
  `,
  sidebarList: css`
    flex: 1;
    overflow-y: auto;
    .ant-list-item {
      cursor: pointer;
      padding: 12px 16px;
      &:hover {
        background: ${token.colorBgTextHover};
      }
    }
  `,
  content: css`
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  `,
  tabsContainer: css`
    flex: 1;
    overflow: hidden;
    .ant-tabs {
      height: 100%;
    }
    .ant-tabs-content {
      height: 100%;
    }
    .ant-tabs-tabpane {
      height: 100%;
      overflow: auto;
    }
  `,
  tabContent: css`
    height: 100%;
    display: flex;
    flex-direction: column;
  `,
  tabBody: css`
    flex: 1;
    min-height: 0;
    overflow: auto;
  `,
  footer: css`
    padding: 8px 16px;
    border-top: 1px solid ${token.colorBorderSecondary};
    background: ${token.colorBgContainer};
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  `,
  emptyContent: css`
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: ${token.colorTextSecondary};
  `
}))

interface OpenTab<T> {
  key: string
  id: number
  data: T | null
  loading: boolean
  dirty: boolean
}

export function TabbedEntityManager<T extends EntityWithId>({
  api,
  title,
  onAdd,
  buildSavePayload,
  renderDetail,
  getTabTitle
}: TabbedEntityManagerProps<T>): React.ReactElement {
  const { t } = useTranslation()
  const { styles } = useStyles()

  const [items, setItems] = useState<T[]>([])
  const [loading, setLoading] = useState(true)
  const [openTabs, setOpenTabs] = useState<OpenTab<T>[]>([])
  const [activeTabKey, setActiveTabKey] = useState<string | undefined>()

  const loadList = useCallback(async () => {
    try {
      setLoading(true)
      const data = await api.list()
      setItems(data)
    } catch (error) {
      console.error('Failed to load items:', error)
      message.error('Failed to load items')
    } finally {
      setLoading(false)
    }
  }, [api])

  useEffect(() => {
    void loadList()
  }, [loadList])

  const handleItemClick = async (item: T) => {
    if (!item.id) return

    const existingTab = openTabs.find(tab => tab.id === item.id)
    if (existingTab) {
      setActiveTabKey(existingTab.key)
      return
    }

    const tabKey = `tab-${item.id}`
    const newTab: OpenTab<T> = {
      key: tabKey,
      id: item.id,
      data: null,
      loading: true,
      dirty: false
    }

    setOpenTabs(prev => [...prev, newTab])
    setActiveTabKey(tabKey)

    try {
      const data = await api.get(item.id)
      setOpenTabs(prev => prev.map(tab =>
        tab.key === tabKey ? { ...tab, data, loading: false } : tab
      ))
    } catch (error) {
      console.error('Failed to load item:', error)
      message.error('Failed to load item')
      setOpenTabs(prev => prev.filter(tab => tab.key !== tabKey))
    }
  }

  const handleAdd = async () => {
    try {
      const newId = await onAdd()
      await loadList()
      const newItem = { id: newId } as T
      await handleItemClick(newItem)
    } catch (error) {
      // User cancelled or error occurred
    }
  }

  const handleSave = async (tabKey: string) => {
    const tab = openTabs.find(t => t.key === tabKey)
    if (!tab?.data) return

    try {
      const payload = buildSavePayload ? buildSavePayload(tab.data) : tab.data
      const saved = await api.save(payload as T)
      setOpenTabs(prev => prev.map(t =>
        t.key === tabKey ? { ...t, data: saved, dirty: false } : t
      ))
      await loadList()
      message.success(t('data_definitions.saved'))
    } catch (error) {
      console.error('Failed to save:', error)
      message.error(error instanceof Error && error.message !== '' ? error.message : 'Failed to save', 6)
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await api.delete(id)
      // close the tab of the deleted definition, if it is open
      setOpenTabs(prev => {
        const remaining = prev.filter(tab => tab.id !== id)
        if (remaining.length !== prev.length) {
          setActiveTabKey(remaining.length > 0 ? remaining[remaining.length - 1].key : undefined)
        }
        return remaining
      })
      await loadList()
      message.success(t('data_definitions.deleted'))
    } catch (error) {
      console.error('Failed to delete:', error)
      message.error('Failed to delete')
    }
  }

  const confirmDelete = (item: T) => {
    if (!item.id) return

    Modal.confirm({
      title: t('data_definitions.delete_confirm'),
      content: item.name,
      okText: t('data_definitions.yes'),
      okButtonProps: { danger: true },
      cancelText: t('data_definitions.no'),
      onOk: async () => { await handleDelete(item.id!) }
    })
  }

  const handleTabClose = (tabKey: string) => {
    setOpenTabs(prev => {
      const newTabs = prev.filter(t => t.key !== tabKey)
      if (activeTabKey === tabKey && newTabs.length > 0) {
        setActiveTabKey(newTabs[newTabs.length - 1].key)
      } else if (newTabs.length === 0) {
        setActiveTabKey(undefined)
      }
      return newTabs
    })
  }

  const handleDataChange = (tabKey: string, data: T) => {
    setOpenTabs(prev => prev.map(tab =>
      tab.key === tabKey ? { ...tab, data, dirty: true } : tab
    ))
  }

  const getTitle = (tab: OpenTab<T>): string => {
    if (getTabTitle && tab.data) {
      return getTabTitle(tab.data)
    }
    return tab.data?.name || `#${tab.id}`
  }

  const tabItems = openTabs.map(tab => ({
    key: tab.key,
    label: (
      <span>
        {getTitle(tab)}
        {tab.dirty && ' *'}
      </span>
    ),
    closable: true,
    children: (
      <div className={styles.tabContent}>
        <div className={styles.tabBody}>
          {tab.loading ? (
            <div className={styles.emptyContent}>
              <Spin />
            </div>
          ) : tab.data ? (
            renderDetail(tab.data, (data) => handleDataChange(tab.key, data))
          ) : null}
        </div>
        <div className={styles.footer}>
          <Button
            type="primary"
            icon={<SaveOutlined />}
            onClick={() => handleSave(tab.key)}
            disabled={!tab.dirty}
          >
            {t('data_definitions.save')}
          </Button>
        </div>
      </div>
    )
  }))

  return (
    <div className={styles.container}>
      <div className={styles.sidebar}>
        <div className={styles.sidebarHeader}>
          <span>{title}</span>
          <Button
            type="primary"
            size="small"
            icon={<PlusOutlined />}
            onClick={handleAdd}
          />
        </div>
        <div className={styles.sidebarList}>
          {loading ? (
            <div style={{ padding: 16, textAlign: 'center' }}>
              <Spin />
            </div>
          ) : (
            <List
              dataSource={items}
              renderItem={(item) => (
                <Dropdown
                  trigger={['contextMenu']}
                  menu={{
                    items: [
                      {
                        key: 'delete',
                        label: t('data_definitions.delete'),
                        icon: <DeleteOutlined />,
                        danger: true,
                        onClick: () => { confirmDelete(item) }
                      }
                    ]
                  }}
                >
                  <List.Item onClick={() => handleItemClick(item)}>
                    {item.name || `#${item.id}`}
                  </List.Item>
                </Dropdown>
              )}
            />
          )}
        </div>
      </div>
      <div className={styles.content}>
        {openTabs.length > 0 ? (
          <div className={styles.tabsContainer}>
            <Tabs
              type="editable-card"
              hideAdd
              activeKey={activeTabKey}
              onChange={setActiveTabKey}
              onEdit={(targetKey, action) => {
                if (action === 'remove' && typeof targetKey === 'string') {
                  handleTabClose(targetKey)
                }
              }}
              items={tabItems}
            />
          </div>
        ) : (
          <div className={styles.emptyContent}>
            {t('data_definitions.select_definition')}
          </div>
        )}
      </div>
    </div>
  )
}
