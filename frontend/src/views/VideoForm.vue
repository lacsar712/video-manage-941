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
								:accept="uploadAcceptTypes"
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
								:accept="uploadAcceptTypes"
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

				<el-form-item label="内容分级" prop="content_rating_code">
					<el-select
						v-model="form.content_rating_code"
						placeholder="请选择内容分级（选填）"
						clearable
						style="width: 320px"
					>
						<el-option
							v-for="item in ratingOptions"
							:key="item.code"
							:label="item.label"
							:value="item.code"
						>
							<div class="rating-option">
								<span
									class="rating-option-tag"
									:style="{ backgroundColor: item.color_hex }"
								>{{ item.label }}</span>
								<span class="rating-option-code">{{ item.code }}</span>
								<span v-if="item.min_age" class="rating-option-age">{{ item.min_age }}岁+</span>
							</div>
						</el-option>
					</el-select>
					<div class="form-tip">未设置分级的影片将标灰提醒</div>
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

		<MediaPicker v-model="mediaPickerVisible" @select="handleMediaSelected" />
	</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, Upload, Picture } from '@element-plus/icons-vue'
import { getVideoDetail, createVideo, updateVideo, getActiveContentRatings } from '../api'
import { useApiAction } from '../composables/useApiAction'
import MediaPicker from '../components/video/MediaPicker.vue'
import {
	videoFormRules as rules,
	createDefaultFormData,
	uploadAcceptTypes,
	validateBeforeUpload
} from '../components/video/videoFormSchema'
import { getFullUrl, getUploadAction, getUploadHeaders } from '../utils/url'

const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const isEdit = ref(false)
const ratingOptions = ref([])
const mediaPickerVisible = ref(false)

const { loading, execute, executeWithMessage } = useApiAction()

const uploadAction = computed(getUploadAction)
const uploadHeaders = computed(getUploadHeaders)
const getCoverUrl = getFullUrl

const form = reactive(createDefaultFormData())

const beforeUpload = (file) => {
	const result = validateBeforeUpload(file)
	if (!result.valid) {
		ElMessage.error(result.message)
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
	mediaPickerVisible.value = true
}

const handleMediaSelected = (media) => {
	form.cover_url = media.file_path
	ElMessage.success('选择成功')
}

const fetchRatingOptions = async () => {
	try {
		const res = await getActiveContentRatings()
		ratingOptions.value = res.data.list
	} catch (error) {
		console.error('获取内容分级选项失败：', error)
	}
}

const fetchDetail = async () => {
	const id = route.params.id
	if (!id) return

	try {
		const res = await execute(() => getVideoDetail(id))
		const data = res.data
		data.status = parseInt(data.status)
		Object.assign(form, data)
	} catch (error) {
		console.error('获取详情失败：', error)
		ElMessage.error('获取影片信息失败')
		router.back()
	}
}

const handleSubmit = async () => {
	if (!formRef.value) return

	await formRef.value.validate(async (valid) => {
		if (!valid) return

		try {
			if (isEdit.value) {
				await executeWithMessage(
					() => updateVideo(route.params.id, form),
					[],
					{ successMessage: '更新成功', errorPrefix: '更新失败' }
				)
			} else {
				await executeWithMessage(
					() => createVideo(form),
					[],
					{ successMessage: '添加成功', errorPrefix: '添加失败' }
				)
			}
			router.push('/videos')
		} catch (error) {
			console.error('提交失败：', error)
		}
	})
}

const handleCancel = () => {
	router.back()
}

onMounted(() => {
	isEdit.value = !!route.params.id
	fetchRatingOptions()
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

.rating-option {
	display: flex;
	align-items: center;
	gap: 8px;
}

.rating-option-tag {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 4px;
	color: #fff;
	font-size: 12px;
	font-weight: 500;
	line-height: 1.4;
}

.rating-option-code {
	font-size: 12px;
	color: #64748b;
	font-family: monospace;
}

.rating-option-age {
	font-size: 12px;
	color: #94a3b8;
}

.form-tip {
	margin-top: 4px;
	font-size: 12px;
	color: #94a3b8;
}
</style>
