<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * 影片 API 集成测试
 */
class VideoApiTest extends TestCase
{
    private $adminId;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->createTestAdmin();
        $this->token = $this->createTestToken($this->adminId);
    }

    /**
     * 测试获取影片列表
     */
    public function testGetVideoList()
    {
        // 创建测试数据
        $this->createTestVideo(['title' => '影片1', 'status' => 1]);
        $this->createTestVideo(['title' => '影片2', 'status' => 0]);
        $this->createTestVideo(['title' => '影片3', 'status' => 1]);

        // 模拟请求
        $_GET['page'] = 1;
        $_GET['page_size'] = 10;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            getVideoList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertArrayHasKey('list', $response['data']);
        $this->assertCount(3, $response['data']['list']);
        $this->assertEquals(3, $response['data']['total']);
    }

    /**
     * 测试新增影片成功
     */
    public function testCreateVideoSuccess()
    {
        $_POST['title'] = '新影片';
        $_POST['cover_url'] = 'https://example.com/new.jpg';
        $_POST['description'] = '新影片描述';
        $_POST['status'] = 1;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            createVideo();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertEquals('添加成功', $response['message']);
        $this->assertArrayHasKey('id', $response['data']);

        // 验证数据库
        $this->assertDatabaseHas('video', [
            'title' => '新影片',
            'status' => 1
        ]);
    }

    /**
     * 测试新增影片失败 - 标题为空
     */
    public function testCreateVideoFailureMissingTitle()
    {
        $_POST['title'] = '';
        $_POST['cover_url'] = 'https://example.com/test.jpg';
        $_POST['status'] = 1;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            createVideo();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不能为空', $response['message']);
    }

    /**
     * 测试新增影片失败 - URL 格式错误
     */
    public function testCreateVideoFailureInvalidUrl()
    {
        $_POST['title'] = '测试影片';
        $_POST['cover_url'] = 'invalid-url';
        $_POST['status'] = 1;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            createVideo();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('格式不正确', $response['message']);
    }

    /**
     * 测试新增影片失败 - 状态值非法
     */
    public function testCreateVideoFailureInvalidStatus()
    {
        $_POST['title'] = '测试影片';
        $_POST['cover_url'] = 'https://example.com/test.jpg';
        $_POST['status'] = 2; // 非法值
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            createVideo();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('状态值必须为 0 或 1', $response['message']);
    }

    /**
     * 测试更新影片成功
     */
    public function testUpdateVideoSuccess()
    {
        $videoId = $this->createTestVideo();

        $_POST['title'] = '更新后的标题';
        $_POST['cover_url'] = 'https://example.com/updated.jpg';
        $_POST['description'] = '更新后的描述';
        $_POST['status'] = 0;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            updateVideo($videoId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('更新成功', $response['message']);

        // 验证数据库
        $this->assertDatabaseHas('video', [
            'id' => $videoId,
            'title' => '更新后的标题',
            'status' => 0
        ]);
    }

    /**
     * 测试删除影片成功
     */
    public function testDeleteVideoSuccess()
    {
        $videoId = $this->createTestVideo();
        $this->createTestSource($videoId);

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            deleteVideo($videoId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('删除成功', $response['message']);

        // 验证数据库 - 影片和播放源都应该被删除
        $this->assertDatabaseMissing('video', ['id' => $videoId]);
        $this->assertDatabaseMissing('video_source', ['video_id' => $videoId]);
    }

    /**
     * 测试删除影片失败 - 影片不存在
     */
    public function testDeleteVideoFailureNotFound()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/videos.php';
            deleteVideo(99999);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
}
