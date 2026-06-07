export function getFullUrl(url) {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}${url}` : url
}

export function getUploadAction() {
  const baseURL = import.meta.env.VITE_API_BASE_URL || ''
  return baseURL ? `${baseURL}/api/upload/media` : '/api/upload/media'
}

export function getUploadHeaders() {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}

export function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}
