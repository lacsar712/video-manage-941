<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * 播放源 API 集成测试
 */
class SourceApiTest extends TestCase
{
    private $adminId;
    private $token;
    private $videoId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->createTestAdmin();
        $this->token = $this->createTestToken($this->adminId);
        $this->videoId = $this->createTestVideo();
    }

    /**
     * 测试获取播放源列表
     */
    public function testGetSourceList()
    {
        // 创建测试数据
        $this->createTestSource($this->videoId, ['source_name' => '线路1']);
        $this->createTestSource($this->videoId, ['source_name' => '线路2']);

        // 模拟请求
        $_GET['video_id'] = $this->videoId;
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            getSourceList();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertArrayHasKey('list', $response['data']);
        $this->assertCount(2, $response['data']['list']);
    }

    /**
     * 测试新增播放源成功
     */
    public function testCreateSourceSuccess()
    {
        $_POST['video_id'] = $this->videoId;
        $_POST['source_name'] = '新线路';
        $_POST['m3u8_url'] = 'https://example.com/new.m3u8';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            createSource();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // 断言
        $this->assertEquals(0, $response['code']);
        $this->assertEquals('添加成功', $response['message']);
        $this->assertArrayHasKey('id', $response['data']);

        // 验证数据库
        $this->assertDatabaseHas('video_source', [
            'video_id' => $this->videoId,
            'source_name' => '新线路'
        ]);
    }

    /**
     * 测试新增播放源失败 - 线路名为空
     */
    public function testCreateSourceFailureMissingName()
    {
        $_POST['video_id'] = $this->videoId;
        $_POST['source_name'] = '';
        $_POST['m3u8_url'] = 'https://example.com/test.m3u8';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            createSource();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不能为空', $response['message']);
    }

    /**
     * 测试新增播放源失败 - URL 格式错误
     */
    public function testCreateSourceFailureInvalidUrl()
    {
        $_POST['video_id'] = $this->videoId;
        $_POST['source_name'] = '测试线路';
        $_POST['m3u8_url'] = 'invalid-url';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            createSource();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('格式不正确', $response['message']);
    }

    /**
     * 测试新增播放源失败 - 影片不存在
     */
    public function testCreateSourceFailureVideoNotFound()
    {
        $_POST['video_id'] = 99999;
        $_POST['source_name'] = '测试线路';
        $_POST['m3u8_url'] = 'https://example.com/test.m3u8';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            createSource();
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }

    /**
     * 测试更新播放源成功
     */
    public function testUpdateSourceSuccess()
    {
        $sourceId = $this->createTestSource($this->videoId);

        $_POST['source_name'] = '更新后的线路';
        $_POST['m3u8_url'] = 'https://example.com/updated.m3u8';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            updateSource($sourceId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('更新成功', $response['message']);

        // 验证数据库
        $this->assertDatabaseHas('video_source', [
            'id' => $sourceId,
            'source_name' => '更新后的线路'
        ]);
    }

    /**
     * 测试删除播放源成功
     */
    public function testDeleteSourceSuccess()
    {
        $sourceId = $this->createTestSource($this->videoId);

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            deleteSource($sourceId);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('删除成功', $response['message']);

        // 验证数据库
        $this->assertDatabaseMissing('video_source', ['id' => $sourceId]);
    }

    /**
     * 测试删除播放源失败 - 播放源不存在
     */
    public function testDeleteSourceFailureNotFound()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$this->token}";

        ob_start();
        try {
            require __DIR__ . '/../../backend/api/routes/sources.php';
            deleteSource(99999);
        } catch (\Exception $e) {
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertNotEquals(0, $response['code']);
        $this->assertStringContainsString('不存在', $response['message']);
    }
}
