export const videoFormRules = {
  title: [
    { required: true, message: '请输入影片标题', trigger: 'blur' },
    { min: 1, max: 200, message: '标题长度必须在1-200个字符之间', trigger: 'blur' },
  ],
  cover_url: [{ required: true, message: '请上传或选择影片封面', trigger: 'change' }],
  description: [{ max: 1000, message: '描述最多1000个字符', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

export const createDefaultFormData = () => ({
  title: '',
  cover_url: '',
  description: '',
  content_rating_code: '',
  status: 1,
})

export const uploadAcceptTypes = 'image/jpeg,image/jpg,image/png,image/gif,image/webp'

export const validateBeforeUpload = (file) => {
  const isImage = /^image\/(jpeg|jpg|png|gif|webp)$/.test(file.type)
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    return { valid: false, message: '只能上传 JPG、PNG、GIF、WebP 格式的图片' }
  }
  if (!isLt5M) {
    return { valid: false, message: '图片大小不能超过 5MB' }
  }
  return { valid: true }
}
