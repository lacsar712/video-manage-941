import request from '../utils/request'

// 获取影片列表
export function getVideoList(params) {
  return request({
    url: '/videos',
    method: 'get',
    params
  })
}

// 获取影片详情
export function getVideoDetail(id) {
  return request({
    url: `/videos/${id}`,
    method: 'get'
  })
}

// 新增影片
export function createVideo(data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('status', data.status)

  return request({
    url: '/videos',
    method: 'post',
    data: formData
  })
}

// 更新影片
export function updateVideo(id, data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('status', data.status)

  return request({
    url: `/videos/${id}`,
    method: 'post',
    data: formData
  })
}

// 删除影片
export function deleteVideo(id) {
  return request({
    url: `/videos/${id}`,
    method: 'delete'
  })
}

// 更新影片状态
export function updateVideoStatus(id, status) {
  const formData = new FormData()
  formData.append('status', status)

  return request({
    url: `/videos/${id}/status`,
    method: 'post',
    data: formData
  })
}

// 获取播放源列表
export function getSourceList(videoId) {
  return request({
    url: '/sources',
    method: 'get',
    params: { video_id: videoId }
  })
}

// 新增播放源
export function createSource(data) {
  const formData = new FormData()
  formData.append('video_id', data.video_id)
  formData.append('source_name', data.source_name)
  formData.append('m3u8_url', data.m3u8_url)

  return request({
    url: '/sources',
    method: 'post',
    data: formData
  })
}

// 更新播放源
export function updateSource(id, data) {
  const formData = new FormData()
  formData.append('source_name', data.source_name)
  formData.append('m3u8_url', data.m3u8_url)

  return request({
    url: `/sources/${id}`,
    method: 'post',
    data: formData
  })
}

// 删除播放源
export function deleteSource(id) {
  return request({
    url: `/sources/${id}`,
    method: 'delete'
  })
}
