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
					<div class="cover-selector">
						<div class="cover-preview">
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
						</div>
						<div class="cover-actions">
							<div class="action-tip">选择封面方式：</div>
							<el-upload
								class="action-upload"
								:action="uploadAction"
								:headers="uploadHeaders"
								:show-file-list="false"
								:on-success="handleUploadSuccess"
								:on-error="handleUploadError"
								:before-upload="beforeUpload"
								accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
							>
								<el-button type="primary">
									<el-icon><Upload /></el-icon>
									本地上传
								</el-button>
							</el-upload>
							<el-button type="success" @click="openMediaPicker">
								<el-icon><Picture /></el-icon>
								从媒资库选择
							</el-button>
							<el-button
								v-if="form.cover_url"
								type="danger"
								text
								@click="clearCover"
							>
								清除封面
							</el-button>
						</div>
					</div>
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

		<el-dialog
			v-model="mediaPickerVisible"
			title="从媒资库选择封面"
			width="800px"
			class="media-picker-dialog"
			append-to-body
			destroy-on-close
		>
			<div class="media-picker">
				<div class="media-picker-search">
					<el-input
						v-model="mediaKeyword"
						placeholder="按文件名搜索"
						clearable
						style="width: 280px"
						@keyup.enter="fetchMediaList"
						@clear="fetchMediaList"
					>
						<template #prefix>
							<el-icon><Search /></el-icon>
						</template>
					</el-input>
					<el-button type="primary" @click="fetchMediaList">
						<el-icon><Search /></el-icon>
						搜索
					</el-button>
				</div>

				<div v-loading="mediaLoading" class="media-picker-grid">
					<div
						v-for="item in mediaList"
						:key="item.id"
						class="media-picker-item"
						:class="{ active: selectedMediaId === item.id }"
						@click="selectMedia(item)"
					>
						<div class="media-picker-thumb">
							<img :src="getCoverUrl(item.file_path)" :alt="item.original_name" />
						</div>
						<div class="media-picker-name" :title="item.original_name">{{ item.original_name }}</div>
						<div class="media-picker-size">{{ formatSize(item.size_bytes) }}</div>
					</div>
					<el-empty v-if="!mediaLoading && mediaList.length === 0" description="暂无图片资源" />
				</div>

				<div class="media-picker-pagination" v-if="mediaTotal > 0">
					<el-pagination
						v-model:current-page="mediaPage"
						v-model:page-size="mediaPageSize"
						:page-sizes="[12, 24, 48]"
						:total="mediaTotal"
						layout="total, sizes, prev, pager, next, jumper"
						background
						small
						@size-change="fetchMediaList"
						@current-change="fetchMediaList"
					/>
				</div>
			</div>

			<template #footer>
				<el-button @click="mediaPickerVisible = false">取消</el-button>
				<el-button type="primary" :disabled="!selectedMediaId" @click="confirmSelectMedia">
					确定选择
				</el-button>
			</template>
		</el-dialog>
	</div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, Upload, Picture, Search } from '@element-plus/icons-vue'
import { getVideoDetail, createVideo, updateVideo, getMediaList } from '../api'

const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const loading = ref(false)
const isEdit = ref(false)

const mediaPickerVisible = ref(false)
const mediaLoading = ref(false)
const mediaKeyword = ref('')
const mediaPage = ref(1)
const mediaPageSize = ref(24)
const mediaTotal = ref(0)
const mediaList = ref([])
const selectedMediaId = ref(null)
const selectedMedia = ref(null)

const uploadAction = computed(() => {
	const baseURL = import.meta.env.VITE_API_BASE_URL || ''
	return baseURL ? `${baseURL}/api/upload/media` : '/api/upload/media'
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

const getCoverUrl = (url) => {
	if (!url) return ''
	if (url.startsWith('http://') || url.startsWith('https://')) {
		return url
	}
	const baseURL = import.meta.env.VITE_API_BASE_URL || ''
	return baseURL ? `${baseURL}${url}` : url
}

const formatSize = (bytes) => {
	if (bytes < 1024) return bytes + ' B'
	if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
	return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

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

const handleUploadSuccess = (response) => {
	if (response.code === 0) {
		form.cover_url = response.data.url
		ElMessage.success('上传成功')
	} else {
		ElMessage.error(response.message || '上传失败')
	}
}

const handleUploadError = (error) => {
	console.error('上传失败：', error)
	ElMessage.error('上传失败，请重试')
}

const clearCover = () => {
	form.cover_url = ''
}

const openMediaPicker = () => {
	selectedMediaId.value = null
	selectedMedia.value = null
	mediaKeyword.value = ''
	mediaPage.value = 1
	mediaPickerVisible.value = true
	fetchMediaList()
}

const fetchMediaList = async () => {
	mediaLoading.value = true
	try {
		const res = await getMediaList({
			keyword: mediaKeyword.value,
			page: mediaPage.value,
			page_size: mediaPageSize.value
		})
		mediaList.value = res.data.list
		mediaTotal.value = res.data.total
	} catch (error) {
		console.error('获取媒资列表失败：', error)
		ElMessage.error('获取媒资列表失败')
	} finally {
		mediaLoading.value = false
	}
}

const selectMedia = (item) => {
	selectedMediaId.value = item.id
	selectedMedia.value = item
}

const confirmSelectMedia = () => {
	if (selectedMedia.value) {
		form.cover_url = selectedMedia.value.file_path
		ElMessage.success('选择成功')
	}
	mediaPickerVisible.value = false
}

const rules = {
	title: [
		{ required: true, message: '请输入影片标题', trigger: 'blur' },
		{ min: 1, max: 200, message: '标题长度必须在1-200个字符之间', trigger: 'blur' },
	],
	cover_url: [{ required: true, message: '请上传或选择影片封面', trigger: 'change' }],
	description: [{ max: 1000, message: '描述最多1000个字符', trigger: 'blur' }],
	status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const fetchDetail = async () => {
	const id = route.params.id
	if (!id) return

	loading.value = true
	try {
		const res = await getVideoDetail(id)
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

.cover-selector {
	display: flex;
	gap: 20px;
	align-items: flex-start;
}

.cover-preview {
	flex-shrink: 0;
}

.cover-uploader :deep(.el-upload) {
	border: 2px dashed #e2e8f0;
	border-radius: 10px;
	cursor: pointer;
	position: relative;
	overflow: hidden;
	transition: all 0.2s;
	width: 280px;
	height: 157px;
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
}

.cover-image {
	width: 280px;
	height: 157px;
	display: block;
	object-fit: cover;
}

.cover-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.action-tip {
	font-size: 13px;
	color: #64748b;
	font-weight: 500;
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

.media-picker {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.media-picker-search {
	display: flex;
	gap: 10px;
	align-items: center;
}

.media-picker-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
	gap: 12px;
	min-height: 300px;
	max-height: 400px;
	overflow-y: auto;
	padding: 4px;
}

.media-picker-item {
	border: 2px solid #e2e8f0;
	border-radius: 8px;
	padding: 8px;
	cursor: pointer;
	transition: all 0.2s;
	background: #fff;
}

.media-picker-item:hover {
	border-color: #6366f1;
	transform: translateY(-1px);
}

.media-picker-item.active {
	border-color: #6366f1;
	background: #f0f0ff;
}

.media-picker-thumb {
	width: 100%;
	padding-top: 56.25%;
	position: relative;
	overflow: hidden;
	border-radius: 6px;
	background: #f1f5f9;
	margin-bottom: 6px;
}

.media-picker-thumb img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.media-picker-name {
	font-size: 12px;
	color: #1e293b;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	font-weight: 500;
}

.media-picker-size {
	font-size: 11px;
	color: #94a3b8;
	margin-top: 2px;
}

.media-picker-pagination {
	display: flex;
	justify-content: center;
}
</style>
