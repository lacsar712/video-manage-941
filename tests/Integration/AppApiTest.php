<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * APP 公开 API 集成测试
 */
class AppApiTest extends TestCase
{
    /**
     * 测试获取影片列表 - 仅返回上架影片
     */
    public function testGetVideoList()
    {
        // 创建测试数据
        $this->createTestVideo(['title' => '上架影片1', 'status' => 1]);
        $this->createTestVideo(['title' => '下架影片', 'status' => 0]);
        $this->createTestVideo(['title' => '上架影片2', 'status' => 1]);

        // 模拟请求
        $_GET['page'] = 1;
        $_GET['page_size'] = 10;

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertArrayHasKey('list', $response['data']);
        $this->assertCount(2, $response['data']['list']); // 只返回上架的
        $this->assertEquals(2, $response['data']['total']);

        // 验证所有返回的影片都是上架状态
        foreach ($response['data']['list'] as $video) {
            $this->assertEquals(1, $video['status']);
        }
    }

    /**
     * 测试获取影片列表 - 分页功能
     */
    public function testGetVideoListPagination()
    {
        // 创建15个上架影片
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestVideo(['title' => "影片{$i}", 'status' => 1]);
        }

        // 第一页
        $_GET['page'] = 1;
        $_GET['page_size'] = 10;

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertCount(10, $response['data']['list']);
        $this->assertEquals(15, $response['data']['total']);

        // 第二页
        $_GET['page'] = 2;
        $_GET['page_size'] = 10;

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertCount(5, $response['data']['list']);
        $this->assertEquals(15, $response['data']['total']);
    }

    /**
     * 测试获取影片详情 - 成功
     */
    public function testGetVideoDetailSuccess()
    {
        $videoId = $this->createTestVideo([
            'title' => '测试影片',
            'description' => '测试描述',
            'status' => 1
        ]);

        // 创建播放源
        $this->createTestSource($videoId, ['source_name' => '线路1']);
        $this->createTestSource($videoId, ['source_name' => '线路2']);

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoDetail($videoId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertEquals('测试影片', $response['data']['title']);
        $this->assertEquals('测试描述', $response['data']['description']);
        $this->assertArrayHasKey('sources', $response['data']);
        $this->assertCount(2, $response['data']['sources']);
    }

    /**
     * 测试获取影片详情 - 影片不存在
     */
    public function testGetVideoDetailNotFound()
    {
        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoDetail(99999);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }

    /**
     * 测试获取影片详情 - 下架影片不可访问
     */
    public function testGetVideoDetailOffline()
    {
        $videoId = $this->createTestVideo(['status' => 0]);

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoDetail($videoId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }

    /**
     * 测试搜索影片
     */
    public function testSearchVideos()
    {
        // 创建测试数据
        $this->createTestVideo(['title' => '复仇者联盟', 'status' => 1]);
        $this->createTestVideo(['title' => '钢铁侠', 'status' => 1]);
        $this->createTestVideo(['title' => '蜘蛛侠', 'status' => 1]);
        $this->createTestVideo(['title' => '复仇者联盟2', 'status' => 0]); // 下架

        // 搜索"复仇者"
        $_GET['keyword'] = '复仇者';
        $_GET['page'] = 1;
        $_GET['page_size'] = 10;

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/app.php';
            getVideoList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertCount(1, $response['data']['list']); // 只返回上架的"复仇者联盟"
        $this->assertStringContainsString('复仇者', $response['data']['list'][0]['title']);
    }
}
