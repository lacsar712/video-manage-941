import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import VideoForm from '@/views/VideoForm.vue'
import * as videoApi from '@/api/video'

// Mock API
vi.mock('@/api/video')

// Mock router
const mockPush = vi.fn()
const mockParams = { id: null }
vi.mock('vue-router', () => ({
	useRouter: () => ({
		push: mockPush,
	}),
	useRoute: () => ({
		params: mockParams,
	}),
	createRouter: vi.fn(() => ({
		push: vi.fn(),
		replace: vi.fn(),
		beforeEach: vi.fn(),
	})),
	createWebHistory: vi.fn(),
}))

describe('VideoForm组件', () => {
	let wrapper

	beforeEach(() => {
		vi.clearAllMocks()
		mockParams.id = null
		wrapper = mount(VideoForm)
	})

	it('应该在新增模式下显示正确标题', () => {
		expect(wrapper.find('h2').text()).toBe('新增影片')
	})

	it('应该在编辑模式下显示正确标题并加载数据', async () => {
		mockParams.id = '1'

		const mockDetail = {
			code: 0,
			data: {
				id: 1,
				title: '测试影片',
				cover_url: 'https://example.com/test.jpg',
				description: '测试描述',
				status: 1,
			},
		}
		videoApi.getVideoDetail.mockResolvedValue(mockDetail)

		wrapper = mount(VideoForm)
		await flushPromises()

		expect(wrapper.find('h2').text()).toBe('编辑影片')
		expect(videoApi.getVideoDetail).toHaveBeenCalledWith(1)
		expect(wrapper.vm.form.title).toBe('测试影片')
		expect(wrapper.vm.form.status).toBe(1) // 应该是数字类型
	})

	it('应该验证必填字段', async () => {
		// 尝试提交空表单
		const submitButton = wrapper.findAll('button').find((btn) => btn.text() === '提交')
		await submitButton.trigger('click')
		await flushPromises()

		// 应该显示验证错误
		expect(wrapper.text()).toContain('请输入影片标题')
		expect(wrapper.text()).toContain('请上传影片封面')
	})

	it('应该成功创建影片', async () => {
		videoApi.createVideo.mockResolvedValue({ code: 0 })

		// 填写表单
		await wrapper.find('input[placeholder="请输入影片标题"]').setValue('新影片')
		await wrapper.find('input[placeholder="请输入封面图片URL"]').setValue('https://example.com/new.jpg')
		await wrapper.find('textarea').setValue('新影片描述')

		// 选择状态
		const radios = wrapper.findAllComponents({ name: 'ElRadio' })
		await radios[0].setValue(1)

		// 提交表单
		const submitButton = wrapper.findAll('button').find((btn) => btn.text() === '提交')
		await submitButton.trigger('click')
		await flushPromises()

		// 验证
		expect(videoApi.createVideo).toHaveBeenCalled()
		const formData = videoApi.createVideo.mock.calls[0][0]
		expect(formData.get('title')).toBe('新影片')
		expect(formData.get('cover_url')).toBe('https://example.com/new.jpg')
		expect(formData.get('status')).toBe('1')

		// 应该跳转回列表页
		expect(mockPush).toHaveBeenCalledWith('/videos')
	})

	it('应该成功更新影片', async () => {
		mockParams.id = '1'

		const mockDetail = {
			code: 0,
			data: {
				id: 1,
				title: '原标题',
				cover_url: 'https://example.com/old.jpg',
				description: '原描述',
				status: 0,
			},
		}
		videoApi.getVideoDetail.mockResolvedValue(mockDetail)
		videoApi.updateVideo.mockResolvedValue({ code: 0 })

		wrapper = mount(VideoForm)
		await flushPromises()

		// 修改表单
		await wrapper.find('input[placeholder="请输入影片标题"]').setValue('新标题')

		// 提交表单
		const submitButton = wrapper.findAll('button').find((btn) => btn.text() === '提交')
		await submitButton.trigger('click')
		await flushPromises()

		// 验证
		expect(videoApi.updateVideo).toHaveBeenCalledWith(1, expect.any(FormData))
		expect(mockPush).toHaveBeenCalledWith('/videos')
	})

	it('应该支持取消操作', async () => {
		const cancelButton = wrapper.findAll('button').find((btn) => btn.text() === '取消')
		await cancelButton.trigger('click')

		expect(mockPush).toHaveBeenCalledWith('/videos')
	})

	it('应该正确转换状态为数字类型', async () => {
		mockParams.id = '1'

		const mockDetail = {
			code: 0,
			data: {
				id: 1,
				title: '测试',
				cover_url: 'https://example.com/test.jpg',
				status: '1', // API返回字符串
			},
		}
		videoApi.getVideoDetail.mockResolvedValue(mockDetail)

		wrapper = mount(VideoForm)
		await flushPromises()

		// 状态应该被转换为数字
		expect(wrapper.vm.form.status).toBe(1)
		expect(typeof wrapper.vm.form.status).toBe('number')
	})

	it('应该在提交时禁用按钮', async () => {
		videoApi.createVideo.mockImplementation(() => new Promise((resolve) => setTimeout(resolve, 100)))

		await wrapper.find('input[placeholder="请输入影片标题"]').setValue('测试')
		await wrapper.find('input[placeholder="请输入封面图片URL"]').setValue('https://example.com/test.jpg')

		const submitButton = wrapper.findAll('button').find((btn) => btn.text() === '提交')
		await submitButton.trigger('click')

		// 按钮应该被禁用
		expect(wrapper.vm.loading).toBe(true)
	})
})
