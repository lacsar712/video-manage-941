<template>
  <div class="content-rating">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>内容分级</h3>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增分级
          </el-button>
        </div>
      </template>

      <div class="filter-bar">
        <el-form :inline="true" :model="queryForm">
          <el-form-item label="关键词">
            <el-input
              v-model="queryForm.keyword"
              placeholder="请输入编码/标签/描述"
              clearable
              style="width: 200px"
              @clear="handleQuery"
            />
          </el-form-item>
          <el-form-item label="状态">
            <el-select
              v-model="queryForm.status"
              placeholder="请选择状态"
              clearable
              style="width: 200px"
              @clear="handleQuery"
            >
              <el-option label="启用" value="1" />
              <el-option label="禁用" value="0" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleQuery">查询</el-button>
            <el-button @click="handleReset">重置</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-table :data="tableData" border stripe v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="标签" width="180">
          <template #default="{ row }">
            <div class="rating-tag-preview">
              <span
                class="rating-tag"
                :style="{ backgroundColor: row.color_hex }"
              >
                {{ row.label }}
              </span>
              <span class="rating-code">{{ row.code }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="min_age" label="最低年龄" width="100">
          <template #default="{ row }">
            <span v-if="row.min_age">{{ row.min_age }} 岁+</span>
            <span v-else class="text-muted">不限</span>
          </template>
        </el-table-column>
        <el-table-column label="颜色" width="120">
          <template #default="{ row }">
            <div class="color-preview">
              <span class="color-block" :style="{ backgroundColor: row.color_hex }"></span>
              <span class="color-value">{{ row.color_hex }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'">
              {{ row.status == 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="180" />
        <el-table-column label="操作" width="300" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              size="small"
              :type="row.status == 1 ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status == 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="isEdit ? '编辑分级' : '新增分级'"
      width="560px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="分级编码" prop="code">
          <el-input
            v-model="formData.code"
            placeholder="例如：PG-13"
            maxlength="20"
            show-word-limit
          />
          <div class="form-tip">唯一标识，如 G、PG、PG-13、R、NC-17</div>
        </el-form-item>
        <el-form-item label="分级标签" prop="label">
          <el-input
            v-model="formData.label"
            placeholder="例如：特别辅导级"
            maxlength="50"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="3"
            placeholder="请输入分级描述说明"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="最低年龄" prop="min_age">
          <el-input-number
            v-model="formData.min_age"
            :min="0"
            :max="100"
            :step="1"
            :precision="0"
            placeholder="留空表示不限"
            controls-position="right"
            style="width: 100%"
          />
          <div class="form-tip">单位：岁，留空表示无年龄限制</div>
        </el-form-item>
        <el-form-item label="标签颜色" prop="color_hex">
          <div class="color-picker-wrapper">
            <el-color-picker
              v-model="formData.color_hex"
              show-alpha
              :predefine="predefineColors"
            />
            <el-input
              v-model="formData.color_hex"
              placeholder="#6366f1"
              maxlength="7"
              style="width: 160px; margin-left: 12px"
            />
          </div>
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number
            v-model="formData.sort_order"
            :min="0"
            :max="9999"
            :step="1"
            :precision="0"
            controls-position="right"
            style="width: 100%"
          />
          <div class="form-tip">数值越大越靠前，默认 0</div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="formSubmitting" @click="handleFormSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getContentRatingList,
  createContentRating,
  updateContentRating,
  deleteContentRating,
  updateContentRatingStatus
} from '../api'

const loading = ref(false)
const tableData = ref([])
const formVisible = ref(false)
const isEdit = ref(false)
const formSubmitting = ref(false)
const formRef = ref(null)
const editingId = ref(null)

const queryForm = reactive({
  keyword: '',
  status: ''
})

const predefineColors = [
  '#22c55e',
  '#3b82f6',
  '#f59e0b',
  '#ef4444',
  '#7f1d1d',
  '#6366f1',
  '#8b5cf6',
  '#ec4899'
]

const formData = reactive({
  code: '',
  label: '',
  description: '',
  min_age: null,
  color_hex: '#6366f1',
  sort_order: 0,
  status: 1
})

const formRules = {
  code: [
    { required: true, message: '请输入分级编码', trigger: 'blur' },
    { min: 1, max: 20, message: '编码长度必须在1-20个字符之间', trigger: 'blur' }
  ],
  label: [
    { required: true, message: '请输入分级标签', trigger: 'blur' },
    { min: 1, max: 50, message: '标签长度必须在1-50个字符之间', trigger: 'blur' }
  ],
  color_hex: [
    { required: true, message: '请选择标签颜色', trigger: 'change' }
  ],
  status: [
    { required: true, message: '请选择状态', trigger: 'change' }
  ]
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getContentRatingList({
      keyword: queryForm.keyword,
      status: queryForm.status
    })
    tableData.value = res.data.list
  } catch (error) {
    console.error('获取分级列表失败：', error)
  } finally {
    loading.value = false
  }
}

const handleQuery = () => {
  fetchData()
}

const handleReset = () => {
  queryForm.keyword = ''
  queryForm.status = ''
  fetchData()
}

const resetForm = () => {
  formData.code = ''
  formData.label = ''
  formData.description = ''
  formData.min_age = null
  formData.color_hex = '#6366f1'
  formData.sort_order = 0
  formData.status = 1
  editingId.value = null
  isEdit.value = false
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const handleAdd = () => {
  resetForm()
  formVisible.value = true
}

const handleEdit = (row) => {
  resetForm()
  isEdit.value = true
  editingId.value = row.id
  formData.code = row.code
  formData.label = row.label
  formData.description = row.description || ''
  formData.min_age = row.min_age ? Number(row.min_age) : null
  formData.color_hex = row.color_hex
  formData.sort_order = Number(row.sort_order) || 0
  formData.status = Number(row.status)
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
      code: formData.code.trim(),
      label: formData.label.trim(),
      description: formData.description,
      min_age: formData.min_age,
      color_hex: formData.color_hex,
      sort_order: Number(formData.sort_order) || 0,
      status: Number(formData.status)
    }

    if (isEdit.value) {
      await updateContentRating(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await createContentRating(payload)
      ElMessage.success('创建成功')
    }

    formVisible.value = false
    fetchData()
  } catch (error) {
    console.error('提交失败：', error)
  } finally {
    formSubmitting.value = false
  }
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status == 1 ? 0 : 1
  const action = newStatus == 1 ? '启用' : '禁用'

  try {
    await ElMessageBox.confirm(`确定要${action}该分级标准吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await updateContentRatingStatus(row.id, newStatus)
    ElMessage.success(`${action}成功`)
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error(`${action}失败：`, error)
    }
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除分级「${row.label}」吗？删除后将无法恢复！`,
      '警告',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'error'
      }
    )

    await deleteContentRating(row.id)
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
.content-rating :deep(.el-card) {
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

.rating-tag-preview {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rating-tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  color: #fff;
  font-size: 12px;
  font-weight: 500;
  line-height: 1.4;
}

.rating-code {
  font-size: 12px;
  color: #94a3b8;
  font-family: monospace;
}

.color-preview {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-block {
  display: inline-block;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.color-value {
  font-size: 12px;
  color: #64748b;
  font-family: monospace;
}

.text-muted {
  color: #94a3b8;
  font-size: 13px;
}

.form-tip {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.color-picker-wrapper {
  display: flex;
  align-items: center;
}

.content-rating :deep(.el-table) {
  border-radius: 8px;
}

.content-rating :deep(.el-table th.el-table__cell) {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
}

.content-rating :deep(.el-button--primary) {
  background: #6366f1;
  border-color: #6366f1;
}

.content-rating :deep(.el-button--primary:hover) {
  background: #4f46e5;
  border-color: #4f46e5;
}
</style>
