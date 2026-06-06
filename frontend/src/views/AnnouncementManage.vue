<template>
  <div class="announcement-manage">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>公告管理</h3>
          <el-button type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>
            新增公告
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-form :inline="true" :model="queryForm">
          <el-form-item label="类型">
            <el-select
              v-model="queryForm.type"
              placeholder="请选择类型"
              clearable
              style="width: 160px"
              @clear="handleQuery"
              @change="handleQuery"
            >
              <el-option label="维护公告" value="maintenance" />
              <el-option label="更新公告" value="update" />
            </el-select>
          </el-form-item>
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 160px"
              @clear="handleQuery"
              @change="handleQuery"
            >
              <el-option label="启用" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
          </el-form-item>
          <el-form-item label="关键词">
            <el-input
              v-model="queryForm.keyword"
              placeholder="搜索标题/内容"
              clearable
              style="width: 200px"
              @clear="handleQuery"
              @keyup.enter="handleQuery"
            />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleQuery">查询</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.type === 'maintenance'" type="warning">维护</el-tag>
            <el-tag v-else type="primary">更新</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="生效时间" width="340">
          <template #default="{ row }">
            <div class="time-range">
              <span>{{ row.start_at }}</span>
              <span class="time-sep">至</span>
              <span>{{ row.end_at }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status == 1"
              @change="(val) => handleStatusChange(row, val)"
              active-text="启用"
              inactive-text="禁用"
              inline-prompt
            />
          </template>
        </el-table-column>
        <el-table-column prop="creator_name" label="创建人" width="100" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" link @click="handleView(row)">查看</el-button>
            <el-button size="small" type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑公告' : '新增公告'"
      width="720px"
      :close-on-click-modal="false"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="标题" prop="title">
          <el-input v-model="formData.title" placeholder="请输入公告标题" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="formData.type">
            <el-radio value="maintenance">维护公告</el-radio>
            <el-radio value="update">更新公告</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="生效时间" prop="start_at">
          <el-date-picker
            v-model="formData.start_at"
            type="datetime"
            placeholder="选择开始时间"
            style="width: 260px"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
          <span class="time-sep-inline">至</span>
          <el-date-picker
            v-model="formData.end_at"
            type="datetime"
            placeholder="选择结束时间"
            style="width: 260px"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item label="内容模式">
          <el-radio-group v-model="contentMode">
            <el-radio value="plain">纯文本</el-radio>
            <el-radio value="rich">富文本(HTML)</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <div class="content-editor">
            <el-input
              v-if="contentMode === 'plain'"
              v-model="formData.content"
              type="textarea"
              :rows="10"
              placeholder="请输入公告内容"
            />
            <div v-else class="rich-editor">
              <div class="rich-toolbar">
                <el-button-group>
                  <el-button size="small" @click="insertTag('b')"><b>B</b></el-button>
                  <el-button size="small" @click="insertTag('i')"><i>I</i></el-button>
                  <el-button size="small" @click="insertTag('u')"><u>U</u></el-button>
                  <el-button size="small" @click="insertTag('p')">段落</el-button>
                  <el-button size="small" @click="insertTag('br')">换行</el-button>
                </el-button-group>
                <el-button size="small" type="primary" link @click="previewVisible = true">预览</el-button>
              </div>
              <el-input
                v-model="formData.content"
                type="textarea"
                :rows="8"
                placeholder="请输入 HTML 内容，可使用工具栏快速插入标签"
              />
            </div>
          </div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          {{ isEdit ? '保存修改' : '确认创建' }}
        </el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="previewVisible"
      title="内容预览"
      width="600px"
    >
      <div class="preview-content" v-html="formData.content"></div>
    </el-dialog>

    <el-dialog
      v-model="viewDialogVisible"
      title="公告详情"
      width="600px"
    >
      <div class="view-detail" v-if="viewData">
        <div class="detail-row">
          <span class="label">标题：</span>
          <span class="value">{{ viewData.title }}</span>
        </div>
        <div class="detail-row">
          <span class="label">类型：</span>
          <el-tag v-if="viewData.type === 'maintenance'" type="warning">维护</el-tag>
          <el-tag v-else type="primary">更新</el-tag>
        </div>
        <div class="detail-row">
          <span class="label">生效时间：</span>
          <span class="value">{{ viewData.start_at }} 至 {{ viewData.end_at }}</span>
        </div>
        <div class="detail-row">
          <span class="label">状态：</span>
          <el-tag v-if="viewData.status == 1" type="success">启用</el-tag>
          <el-tag v-else type="info">禁用</el-tag>
        </div>
        <div class="detail-row">
          <span class="label">创建人：</span>
          <span class="value">{{ viewData.creator_name || '-' }}</span>
        </div>
        <div class="detail-row">
          <span class="label">创建时间：</span>
          <span class="value">{{ viewData.created_at }}</span>
        </div>
        <div class="detail-content">
          <div class="label">内容：</div>
          <div class="content-body" v-html="viewData.content"></div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getAnnouncementList,
  createAnnouncement,
  updateAnnouncement,
  deleteAnnouncement,
  updateAnnouncementStatus
} from '../api'

const loading = ref(false)
const submitLoading = ref(false)
const tableData = ref([])
const total = ref(0)
const dialogVisible = ref(false)
const previewVisible = ref(false)
const viewDialogVisible = ref(false)
const formRef = ref(null)
const isEdit = ref(false)
const contentMode = ref('plain')
const viewData = ref(null)

const queryForm = reactive({
  page: 1,
  page_size: 10,
  type: '',
  status: '',
  keyword: ''
})

const defaultFormData = () => ({
  title: '',
  content: '',
  type: 'update',
  start_at: '',
  end_at: '',
  status: 1
})

const formData = reactive(defaultFormData())

const formRules = {
  title: [{ required: true, message: '请输入公告标题', trigger: 'blur' }],
  type: [{ required: true, message: '请选择公告类型', trigger: 'change' }],
  start_at: [{ required: true, message: '请选择开始时间', trigger: 'change' }],
  end_at: [{ required: true, message: '请选择结束时间', trigger: 'change' }],
  content: [{ required: true, message: '请输入公告内容', trigger: 'blur' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }]
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getAnnouncementList(queryForm)
    tableData.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error('获取公告列表失败：', error)
  } finally {
    loading.value = false
  }
}

const handleQuery = () => {
  queryForm.page = 1
  fetchData()
}

const handleReset = () => {
  queryForm.type = ''
  queryForm.status = ''
  queryForm.keyword = ''
  handleQuery()
}

const handlePageChange = () => {
  fetchData()
}

const handleSizeChange = () => {
  queryForm.page = 1
  fetchData()
}

const handleCreate = () => {
  isEdit.value = false
  Object.assign(formData, defaultFormData())
  contentMode.value = 'plain'
  dialogVisible.value = true
}

const handleEdit = (row) => {
  isEdit.value = true
  Object.assign(formData, {
    id: row.id,
    title: row.title,
    content: row.content,
    type: row.type,
    start_at: row.start_at,
    end_at: row.end_at,
    status: row.status
  })
  contentMode.value = /<[a-z][\s\S]*>/i.test(row.content) ? 'rich' : 'plain'
  dialogVisible.value = true
}

const handleView = (row) => {
  viewData.value = row
  viewDialogVisible.value = true
}

const handleDialogClose = () => {
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const insertTag = (tag) => {
  const textarea = document.querySelector('.rich-editor .el-textarea__inner')
  if (!textarea) return
  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const selected = formData.content.substring(start, end)
  let insertText = ''

  if (tag === 'br') {
    insertText = '<br/>'
  } else if (tag === 'p') {
    insertText = `<p>${selected || '段落内容'}</p>`
  } else {
    insertText = `<${tag}>${selected || '文本'}</${tag}>`
  }

  formData.content = formData.content.substring(0, start) + insertText + formData.content.substring(end)
}

const validateTimeRange = () => {
  if (!formData.start_at || !formData.end_at) return true
  const start = new Date(formData.start_at.replace(/-/g, '/')).getTime()
  const end = new Date(formData.end_at.replace(/-/g, '/')).getTime()
  if (end <= start) {
    ElMessage.warning('结束时间必须晚于开始时间')
    return false
  }
  return true
}

const handleSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    if (!validateTimeRange()) return

    submitLoading.value = true
    const payload = {
      title: formData.title,
      content: formData.content,
      type: formData.type,
      start_at: formData.start_at,
      end_at: formData.end_at,
      status: formData.status
    }

    if (isEdit.value) {
      await updateAnnouncement(formData.id, payload)
      ElMessage.success('更新成功')
    } else {
      await createAnnouncement(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchData()
  } catch (error) {
    if (error !== false) {
      console.error('提交失败：', error)
    }
  } finally {
    submitLoading.value = false
  }
}

const handleStatusChange = async (row, val) => {
  try {
    await updateAnnouncementStatus(row.id, val ? 1 : 0)
    ElMessage.success('状态更新成功')
  } catch (error) {
    console.error('状态更新失败：', error)
    row.status = row.status == 1 ? 0 : 1
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除该公告吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteAnnouncement(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败：', error)
    }
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.announcement-manage :deep(.el-card) {
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

.filter-bar {
  margin-bottom: 20px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 8px;
}

.filter-bar :deep(.el-form-item) {
  margin-bottom: 0;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.time-range {
  display: flex;
  flex-direction: column;
  font-size: 13px;
  color: #475569;
}

.time-sep {
  color: #94a3b8;
  margin: 2px 0;
}

.time-sep-inline {
  margin: 0 8px;
  color: #94a3b8;
}

.announcement-manage :deep(.el-table) {
  border-radius: 8px;
}

.announcement-manage :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.announcement-manage :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.announcement-manage :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}

.rich-editor {
  width: 100%;
}

.rich-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  padding: 6px 10px;
  background: #f8fafc;
  border-radius: 6px;
}

.preview-content {
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
  min-height: 200px;
  line-height: 1.8;
}

.preview-content :deep(h1),
.preview-content :deep(h2),
.preview-content :deep(h3) {
  margin: 12px 0 8px;
}

.preview-content :deep(p) {
  margin: 8px 0;
}

.view-detail {
  font-size: 14px;
}

.detail-row {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.detail-row .label {
  width: 80px;
  color: #64748b;
  flex-shrink: 0;
}

.detail-row .value {
  color: #1e293b;
}

.detail-content {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

.detail-content .label {
  color: #64748b;
  margin-bottom: 8px;
}

.content-body {
  padding: 12px 16px;
  background: #fafafa;
  border-radius: 8px;
  line-height: 1.8;
  min-height: 100px;
}

.content-body :deep(p) {
  margin: 8px 0;
}
</style>
