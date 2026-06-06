<template>
  <div class="client-releases">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>版本档案</h3>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增版本
          </el-button>
        </div>
      </template>

      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <el-tab-pane label="Android" name="android">
          <div class="latest-release" v-if="latestReleases.android">
            <div class="latest-label">
              <el-tag type="success" effect="dark">当前最新发布</el-tag>
            </div>
            <div class="latest-info">
              <span class="latest-version">v{{ latestReleases.android.version_name }}</span>
              <span class="latest-code">(version_code: {{ latestReleases.android.version_code }})</span>
              <el-tag
                v-if="latestReleases.android.force_update"
                type="danger"
                size="small"
                style="margin-left: 8px"
              >
                强制更新
              </el-tag>
              <span class="latest-time">发布时间：{{ latestReleases.android.created_at }}</span>
            </div>
          </div>
          <div class="latest-release empty" v-else>
            <el-tag type="info">暂无已发布版本</el-tag>
          </div>

          <el-table :data="tableData.android" border stripe v-loading="loading.android">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="version_name" label="版本名称" width="140">
              <template #default="{ row }">
                <span class="version-name">v{{ row.version_name }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="version_code" label="版本号" width="100" />
            <el-table-column label="强制更新" width="100">
              <template #default="{ row }">
                <el-tag :type="row.force_update ? 'danger' : 'info'" size="small">
                  {{ row.force_update ? '是' : '否' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                  {{ row.status === 1 ? '已发布' : '已下线' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="创建时间" width="180" />
            <el-table-column label="操作" width="320" fixed="right">
              <template #default="{ row }">
                <el-button size="small" @click="handleDetail(row)">详情</el-button>
                <el-button size="small" @click="handleEdit(row)">编辑</el-button>
                <el-button
                  size="small"
                  :type="row.status === 1 ? 'warning' : 'success'"
                  @click="handleToggleStatus(row)"
                >
                  {{ row.status === 1 ? '下线' : '发布' }}
                </el-button>
                <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <el-tab-pane label="iOS" name="ios">
          <div class="latest-release" v-if="latestReleases.ios">
            <div class="latest-label">
              <el-tag type="success" effect="dark">当前最新发布</el-tag>
            </div>
            <div class="latest-info">
              <span class="latest-version">v{{ latestReleases.ios.version_name }}</span>
              <span class="latest-code">(version_code: {{ latestReleases.ios.version_code }})</span>
              <el-tag
                v-if="latestReleases.ios.force_update"
                type="danger"
                size="small"
                style="margin-left: 8px"
              >
                强制更新
              </el-tag>
              <span class="latest-time">发布时间：{{ latestReleases.ios.created_at }}</span>
            </div>
          </div>
          <div class="latest-release empty" v-else>
            <el-tag type="info">暂无已发布版本</el-tag>
          </div>

          <el-table :data="tableData.ios" border stripe v-loading="loading.ios">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="version_name" label="版本名称" width="140">
              <template #default="{ row }">
                <span class="version-name">v{{ row.version_name }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="version_code" label="版本号" width="100" />
            <el-table-column label="强制更新" width="100">
              <template #default="{ row }">
                <el-tag :type="row.force_update ? 'danger' : 'info'" size="small">
                  {{ row.force_update ? '是' : '否' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
                  {{ row.status === 1 ? '已发布' : '已下线' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="创建时间" width="180" />
            <el-table-column label="操作" width="320" fixed="right">
              <template #default="{ row }">
                <el-button size="small" @click="handleDetail(row)">详情</el-button>
                <el-button size="small" @click="handleEdit(row)">编辑</el-button>
                <el-button
                  size="small"
                  :type="row.status === 1 ? 'warning' : 'success'"
                  @click="handleToggleStatus(row)"
                >
                  {{ row.status === 1 ? '下线' : '发布' }}
                </el-button>
                <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :page-sizes="[10, 20, 50, 100]"
          :total="total[activeTab]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="isEdit ? '编辑版本' : '新增版本'"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
        @submit.prevent
      >
        <el-form-item label="平台" prop="platform">
          <el-radio-group v-model="formData.platform" :disabled="isEdit">
            <el-radio value="android">Android</el-radio>
            <el-radio value="ios">iOS</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="版本名称" prop="version_name">
          <el-input v-model="formData.version_name" placeholder="例如：1.0.0" />
        </el-form-item>
        <el-form-item label="版本号" prop="version_code">
          <el-input-number
            v-model="formData.version_code"
            :min="1"
            :step="1"
            :precision="0"
            controls-position="right"
            style="width: 100%"
          />
          <div class="form-tip">正整数，同平台不可重复，例如：1、2、3...</div>
        </el-form-item>
        <el-form-item label="下载地址" prop="download_url">
          <el-input
            v-model="formData.download_url"
            type="textarea"
            :rows="2"
            placeholder="请输入完整下载链接"
          />
        </el-form-item>
        <el-form-item label="强制更新" prop="force_update">
          <el-switch
            v-model="formData.force_update"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :value="1">发布</el-radio>
            <el-radio :value="0">下线</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="更新日志" prop="changelog">
          <el-input
            v-model="formData.changelog"
            type="textarea"
            :rows="5"
            placeholder="请输入更新日志内容，支持换行"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="formSubmitting" @click="handleFormSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="版本详情" width="600px">
      <div class="detail-content" v-if="detailData">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="平台">
            <el-tag :type="detailData.platform === 'android' ? 'success' : 'primary'">
              {{ detailData.platform === 'android' ? 'Android' : 'iOS' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="detailData.status === 1 ? 'success' : 'info'">
              {{ detailData.status === 1 ? '已发布' : '已下线' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="版本名称">v{{ detailData.version_name }}</el-descriptions-item>
          <el-descriptions-item label="版本号">{{ detailData.version_code }}</el-descriptions-item>
          <el-descriptions-item label="强制更新">
            <el-tag :type="detailData.force_update ? 'danger' : 'info'" size="small">
              {{ detailData.force_update ? '是' : '否' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ detailData.created_at }}</el-descriptions-item>
          <el-descriptions-item label="下载地址" :span="2">
            <div class="download-url">
              <el-link :href="detailData.download_url" type="primary" target="_blank">
                {{ detailData.download_url }}
              </el-link>
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="更新日志" :span="2">
            <div class="changelog-content">
              <pre v-if="detailData.changelog">{{ detailData.changelog }}</pre>
              <span v-else class="empty-text">暂无更新日志</span>
            </div>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getClientReleaseList,
  getClientReleaseLatest,
  createClientRelease,
  updateClientRelease,
  deleteClientRelease,
  updateClientReleaseStatus
} from '../api'

const activeTab = ref('android')
const loading = reactive({ android: false, ios: false })
const tableData = reactive({ android: [], ios: [] })
const total = reactive({ android: 0, ios: 0 })
const latestReleases = reactive({ android: null, ios: null })

const queryForm = reactive({
  page: 1,
  page_size: 10
})

const formVisible = ref(false)
const detailVisible = ref(false)
const isEdit = ref(false)
const formSubmitting = ref(false)
const formRef = ref(null)
const detailData = ref(null)
const editingId = ref(null)

const formData = reactive({
  platform: 'android',
  version_name: '',
  version_code: 1,
  download_url: '',
  force_update: 0,
  status: 0,
  changelog: ''
})

const formRules = {
  platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
  version_name: [
    { required: true, message: '请输入版本名称', trigger: 'blur' },
    { max: 50, message: '版本名称长度不能超过50个字符', trigger: 'blur' }
  ],
  version_code: [
    { required: true, message: '请输入版本号', trigger: 'blur' },
    {
      type: 'number',
      min: 1,
      message: '版本号必须为正整数',
      trigger: 'blur'
    }
  ],
  download_url: [
    { required: true, message: '请输入下载地址', trigger: 'blur' },
    { type: 'url', message: '请输入有效的URL地址', trigger: 'blur' }
  ],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

const fetchList = async (platform) => {
  loading[platform] = true
  try {
    const res = await getClientReleaseList({
      platform,
      page: queryForm.page,
      page_size: queryForm.page_size
    })
    tableData[platform] = res.data.list
    total[platform] = res.data.total
  } catch (error) {
    console.error(`获取${platform}版本列表失败：`, error)
  } finally {
    loading[platform] = false
  }
}

const fetchLatest = async () => {
  try {
    const res = await getClientReleaseLatest()
    latestReleases.android = res.data.android
    latestReleases.ios = res.data.ios
  } catch (error) {
    console.error('获取最新版本失败：', error)
  }
}

const fetchAll = () => {
  fetchList('android')
  fetchList('ios')
  fetchLatest()
}

const handleTabChange = () => {
  queryForm.page = 1
}

const handlePageChange = () => {
  fetchList(activeTab.value)
}

const handleSizeChange = () => {
  queryForm.page = 1
  fetchList(activeTab.value)
}

const resetForm = () => {
  formData.platform = activeTab.value
  formData.version_name = ''
  formData.version_code = 1
  formData.download_url = ''
  formData.force_update = 0
  formData.status = 0
  formData.changelog = ''
  editingId.value = null
  isEdit.value = false
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const handleAdd = () => {
  resetForm()
  formData.platform = activeTab.value
  formVisible.value = true
}

const handleEdit = (row) => {
  resetForm()
  isEdit.value = true
  editingId.value = row.id
  formData.platform = row.platform
  formData.version_name = row.version_name
  formData.version_code = row.version_code
  formData.download_url = row.download_url
  formData.force_update = row.force_update
  formData.status = row.status
  formData.changelog = row.changelog || ''
  formVisible.value = true
}

const handleFormSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
  } catch (e) {
    return
  }

  formSubmitting.value = true
  try {
    const payload = {
      platform: formData.platform,
      version_name: formData.version_name,
      version_code: Number(formData.version_code),
      download_url: formData.download_url,
      force_update: Number(formData.force_update),
      status: Number(formData.status),
      changelog: formData.changelog
    }

    if (isEdit.value) {
      await updateClientRelease(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await createClientRelease(payload)
      ElMessage.success('创建成功')
    }

    formVisible.value = false
    fetchAll()
  } catch (error) {
    console.error('提交失败：', error)
  } finally {
    formSubmitting.value = false
  }
}

const handleDetail = async (row) => {
  detailData.value = { ...row }
  detailVisible.value = true
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const action = newStatus === 1 ? '发布' : '下线'

  try {
    await ElMessageBox.confirm(`确定要${action}该版本吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await updateClientReleaseStatus(row.id, newStatus)
    ElMessage.success(`${action}成功`)
    fetchAll()
  } catch (error) {
    if (error !== 'cancel') {
      console.error(`${action}失败：`, error)
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除版本 v${row.version_name} 吗？删除后将无法恢复！`,
      '警告',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'error'
      }
    )

    await deleteClientRelease(row.id)
    ElMessage.success('删除成功')
    fetchAll()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

onMounted(() => {
  fetchAll()
})
</script>

<style scoped>
.client-releases :deep(.el-card) {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.latest-release {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  margin-bottom: 16px;
  background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
  border: 1px solid #bbf7d0;
  border-radius: 8px;
}

.latest-release.empty {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.latest-info {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.latest-version {
  font-size: 16px;
  font-weight: 600;
  color: #065f46;
}

.latest-code {
  font-size: 13px;
  color: #64748b;
}

.latest-time {
  font-size: 13px;
  color: #64748b;
  margin-left: 16px;
}

.version-name {
  font-weight: 500;
  color: #1e293b;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.form-tip {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.download-url {
  word-break: break-all;
}

.changelog-content pre {
  margin: 0;
  padding: 12px;
  background: #f8fafc;
  border-radius: 6px;
  font-family: inherit;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 300px;
  overflow-y: auto;
}

.empty-text {
  color: #94a3b8;
  font-size: 13px;
}

.client-releases :deep(.el-table) {
  border-radius: 8px;
}

.client-releases :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.client-releases :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.client-releases :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.client-releases :deep(.el-tabs__item.is-active) {
  color: #6366f1;
}

.client-releases :deep(.el-tabs__active-bar) {
  background-color: #6366f1;
}
</style>
