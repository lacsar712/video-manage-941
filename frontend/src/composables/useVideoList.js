import { ref, reactive, onMounted } from 'vue'
import { getVideoList, getActiveContentRatings } from '../api'

const DEFAULT_QUERY = {
  page: 1,
  page_size: 10,
  keyword: '',
  status: '',
  content_rating_code: ''
}

export function useVideoList(options = {}) {
  const { autoFetch = true } = options

  const loading = ref(false)
  const tableData = ref([])
  const total = ref(0)
  const ratingOptions = ref([])

  const queryForm = reactive({ ...DEFAULT_QUERY })

  const buildParams = () => {
    const params = { ...queryForm }
    if (params.content_rating_code === '__unrated__') {
      params.only_unrated = 1
      delete params.content_rating_code
    }
    return params
  }

  const fetchRatingOptions = async () => {
    try {
      const res = await getActiveContentRatings()
      ratingOptions.value = res.data.list
    } catch (error) {
      console.error('获取内容分级选项失败：', error)
    }
  }

  const fetchData = async () => {
    loading.value = true
    try {
      const params = buildParams()
      const res = await getVideoList(params)
      tableData.value = res.data.list
      total.value = res.data.total
    } catch (error) {
      console.error('获取列表失败：', error)
    } finally {
      loading.value = false
    }
  }

  const handleQuery = () => {
    queryForm.page = 1
    fetchData()
  }

  const handlePageChange = () => {
    fetchData()
  }

  const handleSizeChange = () => {
    queryForm.page = 1
    fetchData()
  }

  const handleReset = () => {
    queryForm.keyword = ''
    queryForm.status = ''
    queryForm.content_rating_code = ''
    handleQuery()
  }

  const getRowClassName = ({ row }) => {
    if (!row.content_rating_code) {
      return 'row-unrated'
    }
    return ''
  }

  const refresh = fetchData

  onMounted(() => {
    if (autoFetch) {
      fetchRatingOptions()
      fetchData()
    }
  })

  return {
    loading,
    tableData,
    total,
    queryForm,
    ratingOptions,
    fetchData,
    fetchRatingOptions,
    handleQuery,
    handlePageChange,
    handleSizeChange,
    handleReset,
    getRowClassName,
    refresh
  }
}
