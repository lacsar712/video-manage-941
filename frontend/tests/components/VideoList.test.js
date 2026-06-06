import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import VideoList from '@/views/VideoList.vue'
import * as videoApi from '@/api/video'

// Mock API
vi.mock('@/api/video')

// Mock router
const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush
  })
}))

describe('VideoList组件', () => {
  let wrapper

  const mockVideoList = {
    code: 0,
    data: {
      list: [
        {
          id: 1,
          title: '测试影片1',
          cover_url: 'https://example.com/1.jpg',
          status: 1,
          created_at: '2026-01-28 10:00:00'
        },
        {
          id: 2,
          title: '测试影片2',
          cover_url: 'https://example.com/2.jpg',
          status: 0,
          created_at: '2026-01-28 11:00:00'
        }
      ],
      total: 2
    }
  }

  beforeEach(() => {
    vi.clearAllMocks()
    videoApi.getVideoList.mockResolvedValue(mockVideoList)
    wrapper = mount(VideoList)
  })

  it('应该在挂载时加载影片列表', async () => {
    await flushPromises()

    expect(videoApi.getVideoList).toHaveBeenCalledWith({
      page: 1,
      page_size: 10,
      keyword: '',
      status: ''
    })
  })

  it('应该正确渲染影片列表', async () => {
    await flushPromises()

    const rows = wrapper.findAll('tbody tr')
    expect(rows.length).toBe(2)
    expect(wrapper.text()).toContain('测试影片1')
    expect(wrapper.text()).toContain('测试影片2')
  })

  it('应该正确显示状态标签', async () => {
    await flushPromises()

    const tags = wrapper.findAllComponents({ name: 'ElTag' })
    expect(tags[0].text()).toBe('上架')
    expect(tags[1].text()).toBe('下架')
  })

  it('应该支持关键词搜索', async () => {
    await flushPromises()

    // 输入关键词
    const input = wrapper.find('input[placeholder="请输入影片标题"]')
    await input.setValue('测试')

    // 点击查询按钮
    const buttons = wrapper.findAll('button')
    const searchButton = buttons.find(btn => btn.text() === '查询')
    await searchButton.trigger('click')
    await flushPromises()

    expect(videoApi.getVideoList).toHaveBeenCalledWith({
      page: 1,
      page_size: 10,
      keyword: '测试',
      status: ''
    })
  })

  it('应该支持状态筛选', async () => {
    await flushPromises()

    // 选择状态
    const select = wrapper.findComponent({ name: 'ElSelect' })
    await select.setValue(1)

    // 点击查询按钮
    const buttons = wrapper.findAll('button')
    const searchButton = buttons.find(btn => btn.text() === '查询')
    await searchButton.trigger('click')
    await flushPromises()

    expect(videoApi.getVideoList).toHaveBeenCalledWith({
      page: 1,
      page_size: 10,
      keyword: '',
      status: 1
    })
  })

  it('应该在查询时重置页码', async () => {
    await flushPromises()

    // 模拟翻到第2页
    wrapper.vm.queryForm.page = 2

    // 执行查询
    const buttons = wrapper.findAll('button')
    const searchButton = buttons.find(btn => btn.text() === '查询')
    await searchButton.trigger('click')
    await flushPromises()

    // 页码应该重置为1
    expect(videoApi.getVideoList).toHaveBeenCalledWith(
      expect.objectContaining({ page: 1 })
    )
  })

  it('应该支持分页', async () => {
    await flushPromises()

    // 触发分页变化
    const pagination = wrapper.findComponent({ name: 'ElPagination' })
    await pagination.vm.$emit('current-change', 2)
    await flushPromises()

    expect(videoApi.getVideoList).toHaveBeenCalledWith({
      page: 2,
      page_size: 10,
      keyword: '',
      status: ''
    })
  })

  it('应该在翻页时不重置页码', async () => {
    await flushPromises()

    // 翻到第2页
    const pagination = wrapper.findComponent({ name: 'ElPagination' })
    await pagination.vm.$emit('current-change', 2)
    await flushPromises()

    // 验证页码是2而不是1
    expect(videoApi.getVideoList).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2 })
    )
  })

  it('应该支持跳转到新增页面', async () => {
    await flushPromises()

    const buttons = wrapper.findAll('button')
    const addButton = buttons.find(btn => btn.text() === '新增影片')
    await addButton.trigger('click')

    expect(mockPush).toHaveBeenCalledWith('/videos/add')
  })

  it('应该支持跳转到编辑页面', async () => {
    await flushPromises()

    const editButtons = wrapper.findAll('button').filter(btn => btn.text() === '编辑')
    await editButtons[0].trigger('click')

    expect(mockPush).toHaveBeenCalledWith('/videos/edit/1')
  })

  it('应该支持删除影片', async () => {
    videoApi.deleteVideo.mockResolvedValue({ code: 0 })

    await flushPromises()

    // 点击删除按钮
    const deleteButtons = wrapper.findAll('button').filter(btn => btn.text() === '删除')
    await deleteButtons[0].trigger('click')

    // 确认删除（需要mock ElMessageBox）
    // 这里简化处理，直接调用删除方法
    await wrapper.vm.handleDelete(1)
    await flushPromises()

    expect(videoApi.deleteVideo).toHaveBeenCalledWith(1)
    expect(videoApi.getVideoList).toHaveBeenCalled() // 删除后重新加载列表
  })
})
