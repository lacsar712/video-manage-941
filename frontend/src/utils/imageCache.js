/**
 * 图片缓存工具
 * 使用内存缓存和浏览器缓存双重策略
 */

class ImageCache {
  constructor() {
    // 内存缓存：存储已加载的图片URL
    this.cache = new Map()
    // 加载中的图片Promise，避免重复请求
    this.loading = new Map()
  }

  /**
   * 预加载图片
   * @param {string} url - 图片URL
   * @returns {Promise<string>} - 返回图片URL
   */
  async preload(url) {
    if (!url) return null

    // 如果已经缓存，直接返回
    if (this.cache.has(url)) {
      return url
    }

    // 如果正在加载，返回现有的Promise
    if (this.loading.has(url)) {
      return this.loading.get(url)
    }

    // 创建新的加载Promise
    const loadPromise = new Promise((resolve, reject) => {
      const img = new Image()

      img.onload = () => {
        this.cache.set(url, true)
        this.loading.delete(url)
        resolve(url)
      }

      img.onerror = () => {
        this.loading.delete(url)
        reject(new Error(`Failed to load image: ${url}`))
      }

      img.src = url
    })

    this.loading.set(url, loadPromise)
    return loadPromise
  }

  /**
   * 批量预加载图片
   * @param {string[]} urls - 图片URL数组
   * @returns {Promise<string[]>} - 返回成功加载的URL数组
   */
  async preloadBatch(urls) {
    const promises = urls.filter(url => url).map(url =>
      this.preload(url).catch(err => {
        console.warn('Image preload failed:', err.message)
        return null
      })
    )
    const results = await Promise.all(promises)
    return results.filter(url => url !== null)
  }

  /**
   * 检查图片是否已缓存
   * @param {string} url - 图片URL
   * @returns {boolean}
   */
  isCached(url) {
    return this.cache.has(url)
  }

  /**
   * 清除缓存
   */
  clear() {
    this.cache.clear()
    this.loading.clear()
  }

  /**
   * 获取缓存统计信息
   */
  getStats() {
    return {
      cached: this.cache.size,
      loading: this.loading.size
    }
  }
}

// 导出单例
export const imageCache = new ImageCache()

// 导出类，以便需要时创建新实例
export default ImageCache
