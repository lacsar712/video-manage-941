import { describe, it, expect, vi, beforeEach } from 'vitest'
import { request } from '@/utils/request'
import axios from 'axios'

// Mock axios
vi.mock('axios')

describe('request工具函数', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('应该正确设置baseURL', () => {
    expect(request.defaults.baseURL).toBe('/api')
  })

  it('应该在请求头中添加token', async () => {
    localStorage.getItem.mockReturnValue('test-token')

    const mockResponse = { data: { code: 0, data: {} } }
    axios.request.mockResolvedValue(mockResponse)

    await request.get('/test')

    expect(axios.request).toHaveBeenCalledWith(
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer test-token'
        })
      })
    )
  })

  it('应该在没有token时不添加Authorization头', async () => {
    localStorage.getItem.mockReturnValue(null)

    const mockResponse = { data: { code: 0, data: {} } }
    axios.request.mockResolvedValue(mockResponse)

    await request.get('/test')

    const callArgs = axios.request.mock.calls[0][0]
    expect(callArgs.headers.Authorization).toBeUndefined()
  })

  it('应该在401错误时清除token并跳转登录', async () => {
    const mockError = {
      response: { status: 401 }
    }
    axios.request.mockRejectedValue(mockError)

    try {
      await request.get('/test')
    } catch (e) {
      // Expected error
    }

    expect(localStorage.removeItem).toHaveBeenCalledWith('token')
    expect(window.location.href).toBe('/login')
  })

  it('应该正确处理业务错误', async () => {
    const mockResponse = {
      data: { code: 1001, message: '业务错误' }
    }
    axios.request.mockResolvedValue(mockResponse)

    await expect(request.get('/test')).rejects.toThrow('业务错误')
  })
})
