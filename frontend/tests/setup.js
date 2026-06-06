import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Mock vue-router before importing anything else
vi.mock('vue-router', () => ({
  createRouter: vi.fn(() => ({
    push: vi.fn(),
    replace: vi.fn(),
    beforeEach: vi.fn()
  })),
  createWebHistory: vi.fn(),
  useRouter: vi.fn(() => ({
    push: vi.fn(),
    replace: vi.fn()
  })),
  useRoute: vi.fn(() => ({
    params: {},
    query: {}
  }))
}))

// Mock localStorage
global.localStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}

// Mock window.location
delete window.location
window.location = { href: '', reload: vi.fn() }
