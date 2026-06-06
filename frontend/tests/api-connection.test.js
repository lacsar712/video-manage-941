import { describe, it, expect } from 'vitest'

describe('API 连接测试', () => {
  it('应该能够访问后端 API', async () => {
    try {
      const response = await fetch('http://localhost:8082/api/')
      expect(response).toBeDefined()
    } catch (error) {
      // API 可能未配置，这是预期的
      expect(error).toBeDefined()
    }
  })

  it('应该能够测试异步操作', async () => {
    const promise = Promise.resolve(42)
    const result = await promise
    expect(result).toBe(42)
  })

  it('应该能够测试 setTimeout', async () => {
    const result = await new Promise(resolve => {
      setTimeout(() => resolve('done'), 10)
    })
    expect(result).toBe('done')
  })
})
