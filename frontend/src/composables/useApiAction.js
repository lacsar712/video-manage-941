import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export function useApiAction() {
  const loading = ref(false)

  async function execute(apiFn, ...args) {
    loading.value = true
    try {
      const result = await apiFn(...args)
      return result
    } finally {
      loading.value = false
    }
  }

  async function executeWithMessage(
    apiFn,
    args = [],
    { successMessage = '操作成功', errorPrefix = '操作失败' } = {}
  ) {
    loading.value = true
    try {
      const result = await apiFn(...args)
      if (successMessage) {
        ElMessage.success(successMessage)
      }
      return result
    } catch (error) {
      if (error !== 'cancel') {
        console.error(`${errorPrefix}：`, error)
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function confirmAndExecute(
    apiFn,
    args = [],
    {
      confirmMessage = '确定执行该操作吗？',
      confirmTitle = '提示',
      confirmType = 'warning',
      successMessage = '操作成功',
      errorPrefix = '操作失败'
    } = {}
  ) {
    try {
      await ElMessageBox.confirm(confirmMessage, confirmTitle, {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: confirmType
      })
    } catch (error) {
      if (error === 'cancel') {
        return null
      }
      throw error
    }

    return executeWithMessage(apiFn, args, { successMessage, errorPrefix })
  }

  return {
    loading,
    execute,
    executeWithMessage,
    confirmAndExecute
  }
}
