<template>
  <div class="reports">
    <div class="page-header">
      <div class="page-title">
        <h2>数据报表</h2>
        <p>聚合运营数据并以图表呈现趋势</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" :loading="generating" @click="handleGenerateSnapshot">
          <el-icon><Refresh /></el-icon>
          <span>生成今日快照</span>
        </el-button>
        <el-button @click="fetchData">
          <el-icon><RefreshRight /></el-icon>
          <span>刷新数据</span>
        </el-button>
      </div>
    </div>

    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(99,102,241,0.1);">
          <el-icon :size="24" color="#6366f1"><Film /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">影片总量</span>
          <span class="stat-value">{{ latestData.video_total }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16,185,129,0.1);">
          <el-icon :size="24" color="#10b981"><CircleCheck /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">已上架影片</span>
          <span class="stat-value">{{ latestData.video_published }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245,158,11,0.1);">
          <el-icon :size="24" color="#f59e0b"><Link /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">播放源总量</span>
          <span class="stat-value">{{ latestData.source_total }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239,68,68,0.1);">
          <el-icon :size="24" color="#ef4444"><Plus /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">今日新增影片</span>
          <span class="stat-value">{{ latestData.new_videos }}</span>
        </div>
      </div>
    </div>

    <el-card class="chart-card" shadow="never">
      <div class="card-header">
        <h3 class="section-title">近 30 天运营趋势</h3>
      </div>
      <div ref="chartRef" class="chart-container"></div>
    </el-card>

    <el-card class="table-card" shadow="never">
      <div class="card-header">
        <h3 class="section-title">详细数据</h3>
      </div>
      <el-table
        :data="tableData"
        stripe
        style="width: 100%"
        v-loading="loading"
      >
        <el-table-column prop="stat_date" label="日期" width="140">
          <template #default="scope">
            <span>{{ scope.row.stat_date }}</span>
            <el-tag v-if="!scope.row.has_data" size="small" type="info" style="margin-left: 8px;">补零</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="video_total" label="影片总量" align="right" width="120" />
        <el-table-column prop="video_published" label="已上架" align="right" width="120" />
        <el-table-column prop="source_total" label="播放源总量" align="right" width="140" />
        <el-table-column prop="new_videos" label="新增影片" align="right" width="120">
          <template #default="scope">
            <span :class="scope.row.new_videos > 0 ? 'text-positive' : ''">{{ scope.row.new_videos }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="source_increment" label="播放源增量" align="right" width="140">
          <template #default="scope">
            <span :class="scope.row.source_increment > 0 ? 'text-positive' : (scope.row.source_increment < 0 ? 'text-negative' : '')">
              {{ scope.row.source_increment > 0 ? '+' : '' }}{{ scope.row.source_increment }}
            </span>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, RefreshRight, Film, CircleCheck, Link, Plus } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { getDailyStatsSnapshot, createSnapshotToday } from '../api'

const chartRef = ref(null)
let chartInstance = null

const loading = ref(false)
const generating = ref(false)
const snapshotData = ref([])

const latestData = reactive({
  video_total: 0,
  video_published: 0,
  source_total: 0,
  new_videos: 0
})

const tableData = computed(() => {
  return [...snapshotData.value.slice().reverse()]
})

const initChart = () => {
  if (chartRef.value) {
    chartInstance = echarts.init(chartRef.value)
    window.addEventListener('resize', handleResize)
  }
}

const handleResize = () => {
  chartInstance && chartInstance.resize()
}

const renderChart = () => {
  if (!chartInstance) return

  const dates = snapshotData.value.map(item => item.stat_date)
  const videoTotal = snapshotData.value.map(item => item.video_total)
  const newVideos = snapshotData.value.map(item => item.new_videos)
  const sourceIncrement = snapshotData.value.map(item => item.source_increment)

  const option = {
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      textStyle: {
        color: '#1e293b'
      }
    },
    legend: {
      data: ['影片总量', '新增影片', '播放源增量'],
      top: 0,
      right: 0
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '50',
      containLabel: true
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: dates,
      axisLine: {
        lineStyle: {
          color: '#e5e7eb'
        }
      },
      axisLabel: {
        color: '#94a3b8',
        formatter: (value) => {
          return value.substring(5)
        }
      }
    },
    yAxis: [
      {
        type: 'value',
        name: '影片',
        axisLine: {
          show: false
        },
        axisTick: {
          show: false
        },
        splitLine: {
          lineStyle: {
            color: '#f1f5f9'
          }
        },
        axisLabel: {
          color: '#94a3b8'
        }
      }
    ],
    series: [
      {
        name: '影片总量',
        type: 'line',
        smooth: true,
        data: videoTotal,
        lineStyle: {
          width: 3,
          color: '#6366f1'
        },
        itemStyle: {
          color: '#6366f1'
        },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(99, 102, 241, 0.2)' },
            { offset: 1, color: 'rgba(99, 102, 241, 0.02)' }
          ])
        },
        symbol: 'circle',
        symbolSize: 6
      },
      {
        name: '新增影片',
        type: 'line',
        smooth: true,
        data: newVideos,
        lineStyle: {
          width: 3,
          color: '#10b981'
        },
        itemStyle: {
          color: '#10b981'
        },
        symbol: 'circle',
        symbolSize: 6
      },
      {
        name: '播放源增量',
        type: 'line',
        smooth: true,
        data: sourceIncrement,
        lineStyle: {
          width: 3,
          color: '#f59e0b'
        },
        itemStyle: {
          color: '#f59e0b'
        },
        symbol: 'circle',
        symbolSize: 6
      }
    ]
  }

  chartInstance.setOption(option)
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getDailyStatsSnapshot({ days: 30 })
    snapshotData.value = res.data || []
    if (snapshotData.value.length > 0) {
      const latest = snapshotData.value[snapshotData.value.length - 1]
      latestData.video_total = latest.video_total
      latestData.video_published = latest.video_published
      latestData.source_total = latest.source_total
      latestData.new_videos = latest.new_videos
    }
    await nextTick()
    renderChart()
  } catch (error) {
    console.error('获取快照数据失败：', error)
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleGenerateSnapshot = async () => {
  try {
    await ElMessageBox.confirm('确定要生成今日数据快照吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    generating.value = true
    await createSnapshotToday()
    ElMessage.success('快照生成成功')
    await fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('生成快照失败：', error)
    }
  } finally {
    generating.value = false
  }
}

onMounted(() => {
  initChart()
  fetchData()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  if (chartInstance) {
    chartInstance.dispose()
    chartInstance = null
  }
})
</script>

<style scoped>
.reports {
  max-width: 1200px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.page-title h2 {
  margin: 0 0 4px;
  font-size: 22px;
  color: #1e293b;
  font-weight: 700;
}

.page-title p {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.page-actions {
  display: flex;
  gap: 12px;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #f0f0f0;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
}

.chart-card,
.table-card {
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  margin-bottom: 24px;
}

.card-header {
  margin-bottom: 16px;
}

.section-title {
  margin: 0;
  font-size: 16px;
  color: #1e293b;
  font-weight: 600;
}

.chart-container {
  width: 100%;
  height: 400px;
}

.text-positive {
  color: #10b981;
  font-weight: 500;
}

.text-negative {
  color: #ef4444;
  font-weight: 500;
}
</style>
