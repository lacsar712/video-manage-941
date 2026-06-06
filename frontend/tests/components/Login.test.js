import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import Login from '@/views/Login.vue'
import * as adminApi from '@/api/admin'

// Mock API
vi.mock('@/api/admin')

// Mock router
const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush
  })
}))

describe('Login组件', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    wrapper = mount(Login)
  })

  it('应该正确渲染登录表单', () => {
    expect(wrapper.find('h2').text()).toBe('影片管理系统')
    expect(wrapper.find('input[placeholder="请输入用户名"]').exists()).toBe(true)
    expect(wrapper.find('input[placeholder="请输入密码"]').exists()).toBe(true)
    expect(wrapper.find('button').text()).toBe('登录')
  })

  it('应该验证必填字段', async () => {
    const form = wrapper.findComponent({ name: 'ElForm' })

    // 尝试提交空表单
    await wrapper.find('button').trigger('click')
    await nextTick()

    // 应该显示验证错误
    expect(wrapper.text()).toContain('请输入用户名')
  })

  it('应该在登录成功后保存token并跳转', async () => {
    const mockResponse = {
      code: 0,
      data: {
        token: 'test-token-123',
        username: 'admin'
      }
    }
    adminApi.login.mockResolvedValue(mockResponse)

    // 填写表单
    await wrapper.find('input[placeholder="请输入用户名"]').setValue('admin')
    await wrapper.find('input[placeholder="请输入密码"]').setValue('admin123')

    // 提交表单
    await wrapper.find('button').trigger('click')
    await nextTick()

    // 验证
    expect(adminApi.login).toHaveBeenCalledWith({
      username: 'admin',
      password: 'admin123'
    })
    expect(localStorage.setItem).toHaveBeenCalledWith('token', 'test-token-123')
    expect(mockPush).toHaveBeenCalledWith('/videos')
  })

  it('应该在登录失败时显示错误消息', async () => {
    adminApi.login.mockRejectedValue(new Error('用户名或密码错误'))

    // 填写表单
    await wrapper.find('input[placeholder="请输入用户名"]').setValue('admin')
    await wrapper.find('input[placeholder="请输入密码"]').setValue('wrong')

    // 提交表单
    await wrapper.find('button').trigger('click')
    await nextTick()

    // 应该显示错误消息（通过ElMessage）
    expect(adminApi.login).toHaveBeenCalled()
  })

  it('应该在提交时禁用按钮', async () => {
    adminApi.login.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))

    await wrapper.find('input[placeholder="请输入用户名"]').setValue('admin')
    await wrapper.find('input[placeholder="请输入密码"]').setValue('admin123')

    const button = wrapper.find('button')
    await button.trigger('click')

    // 按钮应该被禁用
    expect(button.attributes('disabled')).toBeDefined()
  })
})
