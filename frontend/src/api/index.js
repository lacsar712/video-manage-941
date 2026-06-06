import request from '../utils/request'

// 管理员登录
export function login(data) {
  const formData = new FormData()
  formData.append('username', data.username)
  formData.append('password', data.password)

  return request({
    url: '/admin/login',
    method: 'post',
    data: formData
  })
}

// 管理员退出
export function logout() {
  return request({
    url: '/admin/logout',
    method: 'post'
  })
}

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
  if (data.content_rating_code) {
    formData.append('content_rating_code', data.content_rating_code)
  }

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
  if (data.content_rating_code) {
    formData.append('content_rating_code', data.content_rating_code)
  }

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

// 获取定时任务列表
export function getScheduledTaskList(params) {
  return request({
    url: '/scheduled_tasks',
    method: 'get',
    params
  })
}

// 获取即将执行的定时任务
export function getUpcomingScheduledTasks(params) {
  return request({
    url: '/scheduled_tasks/upcoming',
    method: 'get',
    params
  })
}

// 创建定时任务
export function createScheduledTask(data) {
  return request({
    url: '/scheduled_tasks',
    method: 'post',
    data
  })
}

// 取消定时任务
export function cancelScheduledTask(id) {
  return request({
    url: `/scheduled_tasks/${id}/cancel`,
    method: 'post'
  })
}

// 获取媒资列表
export function getMediaList(params) {
  return request({
    url: '/media',
    method: 'get',
    params
  })
}

// 删除媒资
export function deleteMedia(id) {
  return request({
    url: `/media/${id}`,
    method: 'delete'
  })
}

// 获取客户端版本列表
export function getClientReleaseList(params) {
  return request({
    url: '/client_releases',
    method: 'get',
    params
  })
}

// 获取各平台最新发布版本
export function getClientReleaseLatest() {
  return request({
    url: '/client_releases/latest',
    method: 'get'
  })
}

// 获取客户端版本详情
export function getClientReleaseDetail(id) {
  return request({
    url: `/client_releases/${id}`,
    method: 'get'
  })
}

// 新增客户端版本
export function createClientRelease(data) {
  return request({
    url: '/client_releases',
    method: 'post',
    data,
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 更新客户端版本
export function updateClientRelease(id, data) {
  return request({
    url: `/client_releases/${id}`,
    method: 'post',
    data,
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 删除客户端版本
export function deleteClientRelease(id) {
  return request({
    url: `/client_releases/${id}`,
    method: 'delete'
  })
}

// 更新客户端版本状态（发布/下线）
export function updateClientReleaseStatus(id, status) {
  return request({
    url: `/client_releases/${id}/status`,
    method: 'post',
    data: { status },
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 获取专题合集列表
export function getCollectionList(params) {
  return request({
    url: '/collections',
    method: 'get',
    params
  })
}

// 获取专题合集详情
export function getCollectionDetail(id) {
  return request({
    url: `/collections/${id}`,
    method: 'get'
  })
}

// 新增专题合集
export function createCollection(data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('sort_order', data.sort_order || 0)
  formData.append('status', data.status)
  if (data.video_ids && data.video_ids.length > 0) {
    data.video_ids.forEach((vid, idx) => {
      formData.append(`video_ids[${idx}]`, vid)
    })
  }
  return request({
    url: '/collections',
    method: 'post',
    data: formData
  })
}

// 更新专题合集
export function updateCollection(id, data) {
  const formData = new FormData()
  formData.append('title', data.title)
  formData.append('cover_url', data.cover_url)
  formData.append('description', data.description || '')
  formData.append('sort_order', data.sort_order || 0)
  formData.append('status', data.status)
  if (data.video_ids && data.video_ids.length > 0) {
    data.video_ids.forEach((vid, idx) => {
      formData.append(`video_ids[${idx}]`, vid)
    })
  }
  return request({
    url: `/collections/${id}`,
    method: 'post',
    data: formData
  })
}

// 删除专题合集
export function deleteCollection(id) {
  return request({
    url: `/collections/${id}`,
    method: 'delete'
  })
}

// 更新专题合集状态
export function updateCollectionStatus(id, status) {
  const formData = new FormData()
  formData.append('status', status)
  return request({
    url: `/collections/${id}/status`,
    method: 'post',
    data: formData
  })
}

// 向合集添加影片
export function addVideosToCollection(id, videoIds) {
  return request({
    url: `/collections/${id}/videos`,
    method: 'post',
    data: { video_ids: videoIds },
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 从合集移除影片
export function removeVideoFromCollection(id, videoId) {
  return request({
    url: `/collections/${id}/videos/${videoId}`,
    method: 'delete'
  })
}

// 更新合集内影片排序
export function updateCollectionVideoSort(id, videoOrders) {
  return request({
    url: `/collections/${id}/sort`,
    method: 'post',
    data: { video_orders: videoOrders },
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 获取字幕列表
export function getSubtitleList(videoId) {
  return request({
    url: '/subtitles',
    method: 'get',
    params: { video_id: videoId }
  })
}

// 上传字幕
export function uploadSubtitle(data) {
  const formData = new FormData()
  formData.append('video_id', data.video_id)
  formData.append('language', data.language)
  formData.append('file', data.file)

  return request({
    url: '/subtitles',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// 获取字幕预览
export function getSubtitlePreview(id) {
  return request({
    url: `/subtitles/${id}/preview`,
    method: 'get'
  })
}

// 删除字幕
export function deleteSubtitle(id) {
  return request({
    url: `/subtitles/${id}`,
    method: 'delete'
  })
}

// 更新字幕状态
export function updateSubtitleStatus(id, status) {
  const formData = new FormData()
  formData.append('status', status)

  return request({
    url: `/subtitles/${id}/status`,
    method: 'post',
    data: formData
  })
}

// 获取内容分级列表
export function getContentRatingList(params) {
  return request({
    url: '/content_ratings',
    method: 'get',
    params
  })
}

// 获取启用的内容分级列表（用于影片选择）
export function getActiveContentRatings() {
  return request({
    url: '/content_ratings/active',
    method: 'get'
  })
}

// 获取内容分级详情
export function getContentRatingDetail(id) {
  return request({
    url: `/content_ratings/${id}`,
    method: 'get'
  })
}

// 新增内容分级
export function createContentRating(data) {
  const formData = new FormData()
  formData.append('code', data.code)
  formData.append('label', data.label)
  formData.append('description', data.description || '')
  if (data.min_age !== '' && data.min_age !== null && data.min_age !== undefined) {
    formData.append('min_age', data.min_age)
  }
  formData.append('color_hex', data.color_hex)
  formData.append('status', data.status)
  formData.append('sort_order', data.sort_order || 0)

  return request({
    url: '/content_ratings',
    method: 'post',
    data: formData
  })
}

// 更新内容分级
export function updateContentRating(id, data) {
  const formData = new FormData()
  formData.append('code', data.code)
  formData.append('label', data.label)
  formData.append('description', data.description || '')
  if (data.min_age !== '' && data.min_age !== null && data.min_age !== undefined) {
    formData.append('min_age', data.min_age)
  }
  formData.append('color_hex', data.color_hex)
  formData.append('status', data.status)
  formData.append('sort_order', data.sort_order || 0)

  return request({
    url: `/content_ratings/${id}`,
    method: 'post',
    data: formData
  })
}

// 删除内容分级
export function deleteContentRating(id) {
  return request({
    url: `/content_ratings/${id}`,
    method: 'delete'
  })
}

// 更新内容分级状态
export function updateContentRatingStatus(id, status) {
  const formData = new FormData()
  formData.append('status', status)

  return request({
    url: `/content_ratings/${id}/status`,
    method: 'post',
    data: formData
  })
}

// 获取推荐位槽位列表
export function getRecommendSlotList() {
  return request({
    url: '/recommend_slots',
    method: 'get'
  })
}

// 获取推荐位槽位详情
export function getRecommendSlotDetail(id) {
  return request({
    url: `/recommend_slots/${id}`,
    method: 'get'
  })
}

// 获取推荐位预览JSON数据
export function getRecommendSlotsPreview() {
  return request({
    url: '/recommend_slots/preview',
    method: 'get'
  })
}

// 新增推荐位槽位
export function createRecommendSlot(data) {
  const formData = new FormData()
  formData.append('slot_key', data.slot_key)
  formData.append('title', data.title)
  formData.append('max_items', data.max_items)
  formData.append('status', data.status)
  formData.append('sort_order', data.sort_order || 0)

  return request({
    url: '/recommend_slots',
    method: 'post',
    data: formData
  })
}

// 更新推荐位槽位
export function updateRecommendSlot(id, data) {
  const formData = new FormData()
  formData.append('slot_key', data.slot_key)
  formData.append('title', data.title)
  formData.append('max_items', data.max_items)
  formData.append('status', data.status)
  formData.append('sort_order', data.sort_order || 0)

  return request({
    url: `/recommend_slots/${id}`,
    method: 'post',
    data: formData
  })
}

// 删除推荐位槽位
export function deleteRecommendSlot(id) {
  return request({
    url: `/recommend_slots/${id}`,
    method: 'delete'
  })
}

// 向推荐位添加影片
export function addVideosToRecommendSlot(id, videoIds) {
  return request({
    url: `/recommend_slots/${id}/videos`,
    method: 'post',
    data: { video_ids: videoIds },
    headers: {
      'Content-Type': 'application/json'
    }
  })
}

// 从推荐位移除影片
export function removeVideoFromRecommendSlot(id, videoId) {
  return request({
    url: `/recommend_slots/${id}/videos/${videoId}`,
    method: 'delete'
  })
}

// 更新推荐位内影片排序
export function updateRecommendItemSort(id, videoOrders) {
  return request({
    url: `/recommend_slots/${id}/sort`,
    method: 'post',
    data: { video_orders: videoOrders },
    headers: {
      'Content-Type': 'application/json'
    }
  })
}
