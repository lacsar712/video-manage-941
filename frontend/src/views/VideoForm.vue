<template>
	<div class="video-form">
		<el-card>
			<template #header>
				<div class="card-header">
					<h3>{{ isEdit ? '编辑影片' : '新增影片' }}</h3>
				</div>
			</template>

			<el-form ref="formRef" :model="form" :rules="rules" label-width="120px" style="max-width: 600px">
				<el-form-item label="影片标题" prop="title">
					<el-input
						v-model="form.title"
						placeholder="请输入影片标题（1-200个字符）"
						maxlength="200"
						show-word-limit
						clearable
					/>
				</el-form-item>

				<el-form-item label="封面图片" prop="cover_url">
					<el-upload
						class="cover-uploader"
						:action="uploadAction"
						:headers="uploadHeaders"
						:show-file-list="false"
						:on-success="handleUploadSuccess"
						:on-error="handleUploadError"
						:before-upload="beforeUpload"
						accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
					>
						<img v-if="form.cover_url" :src="getCoverUrl(form.cover_url)" class="cover-image" />
						<el-icon v-else class="cover-uploader-icon"><Plus /></el-icon>
					</el-upload>
					<div class="upload-tip">支持 JPG、PNG、GIF、WebP 格式，文件大小不超过 5MB</div>
				</el-form-item>

				<el-form-item label="影片描述" prop="description">
					<el-input
						v-model="form.description"
						type="textarea"
						:rows="4"
						placeholder="请输入影片描述（选填，最多1000个字符）"
						maxlength="1000"
						show-word-limit
						clearable
					/>
				</el-form-item>

				<el-form-item label="状态" prop="status">
					<el-radio-group v-model="form.status" size="large">
						<el-radio :label="1" border>上架</el-radio>
						<el-radio :label="0" border>下架</el-radio>
					</el-radio-group>
				</el-form-item>

				<el-form-item>
					<el-button type="primary" :loading="loading" @click="handleSubmit">
						{{ isEdit ? '保存' : '提交' }}
					</el-button>
					<el-button @click="handleCancel">取消</el-button>
				</el-form-item>
			</el-form>
		</el-card>
	</div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getVideoDetail, createVideo, updateVideo } from '../api'

const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)

// 上传配置
const uploadAction = computed(() => {
	const baseURL = import.meta.env.VITE_API_BASE_URL || ''
	return baseURL ? `${baseURL}/api/upload/cover` : '/api/upload/cover'
})

const uploadHeaders = computed(() => {
	const token = localStorage.getItem('token')
	return token ? { Authorization: `Bearer ${token}` } : {}
})

const form = reactive({
	title: '',
	cover_url: '',
	description: '',
	status: 1,
})

// 获取封面完整URL
const getCoverUrl = (url) => {
	if (!url) return ''
	// 如果是完整URL，直接返回
	if (url.startsWith('http://') || url.startsWith('https://')) {
		return url
	}
	// 如果是相对路径，拼接API基础URL
	const baseURL = import.meta.env.VITE_API_BASE_URL || ''
	return baseURL ? `${baseURL}${url}` : url
}

// 上传前验证
const beforeUpload = (file) => {
	const isImage = /^image\/(jpeg|jpg|png|gif|webp)$/.test(file.type)
	const isLt5M = file.size / 1024 / 1024 < 5

	if (!isImage) {
		ElMessage.error('只能上传 JPG、PNG、GIF、WebP 格式的图片')
		return false
	}
	if (!isLt5M) {
		ElMessage.error('图片大小不能超过 5MB')
		return false
	}
	return true
}

// 上传成功
const handleUploadSuccess = (response) => {
	if (response.code === 0) {
		form.cover_url = response.data.url
		ElMessage.success('上传成功')
	} else {
		ElMessage.error(response.message || '上传失败')
	}
}

// 上传失败
const handleUploadError = (error) => {
	console.error('上传失败：', error)
	ElMessage.error('上传失败，请重试')
}

const rules = {
	title: [
		{ required: true, message: '请输入影片标题', trigger: 'blur' },
		{ min: 1, max: 200, message: '标题长度必须在1-200个字符之间', trigger: 'blur' },
	],
	cover_url: [{ required: true, message: '请上传影片封面', trigger: 'change' }],
	description: [{ max: 1000, message: '描述最多1000个字符', trigger: 'blur' }],
	status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const fetchDetail = async () => {
	const id = route.params.id
	if (!id) return

	loading.value = true
	try {
		const res = await getVideoDetail(id)
		// 确保 status 为数字类型，避免字符串 "1"/"0" 导致单选框不选中
		const data = res.data
		data.status = parseInt(data.status)
		Object.assign(form, data)
	} catch (error) {
		console.error('获取详情失败：', error)
		ElMessage.error('获取影片信息失败')
		router.back()
	} finally {
		loading.value = false
	}
}

const handleSubmit = async () => {
	if (!formRef.value) return

	await formRef.value.validate(async (valid) => {
		if (!valid) return

		loading.value = true
		try {
			if (isEdit.value) {
				await updateVideo(route.params.id, form)
				ElMessage.success('更新成功')
			} else {
				await createVideo(form)
				ElMessage.success('添加成功')
			}
			router.push('/videos')
		} catch (error) {
			console.error('提交失败：', error)
		} finally {
			loading.value = false
		}
	})
}

const handleCancel = () => {
	router.back()
}

onMounted(() => {
	isEdit.value = !!route.params.id
	if (isEdit.value) {
		fetchDetail()
	}
})
</script>

<style scoped>
.video-form :deep(.el-card) {
	border-radius: 12px;
	border: 1px solid #f0f0f0;
}

.card-header h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 600;
	color: #1e293b;
}

.cover-uploader :deep(.el-upload) {
	border: 2px dashed #e2e8f0;
	border-radius: 10px;
	cursor: pointer;
	position: relative;
	overflow: hidden;
	transition: all 0.2s;
	width: 360px;
	height: 202px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #f8fafc;
}

.cover-uploader :deep(.el-upload:hover) {
	border-color: #6366f1;
	background: #f0f0ff;
}

.cover-uploader-icon {
	font-size: 28px;
	color: #94a3b8;
	width: 360px;
	height: 202px;
	text-align: center;
	line-height: 202px;
}

.cover-image {
	width: 360px;
	height: 202px;
	display: block;
	object-fit: cover;
}

.upload-tip {
	margin-top: 8px;
	font-size: 12px;
	color: #94a3b8;
	line-height: 1.5;
}

.video-form :deep(.el-button--primary) {
	background: #6366f1;
	border-color: #6366f1;
}

.video-form :deep(.el-button--primary:hover) {
	background: #4f46e5;
	border-color: #4f46e5;
}
</style>
