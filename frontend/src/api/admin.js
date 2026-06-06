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
