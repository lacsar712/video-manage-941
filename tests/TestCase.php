<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * 测试基类
 */
abstract class TestCase extends BaseTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = $this->getTestDB();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    /**
     * 获取测试数据库连接
     */
    protected function getTestDB()
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . TEST_DB_NAME . ";charset=" . DB_CHARSET;
        return new \PDO($dsn, DB_USER, DB_PASS, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }

    /**
     * 清理数据库
     */
    protected function cleanDatabase()
    {
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->exec("TRUNCATE TABLE admin_user");
        $this->db->exec("TRUNCATE TABLE admin_token");
        $this->db->exec("TRUNCATE TABLE video");
        $this->db->exec("TRUNCATE TABLE video_source");
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * 创建测试管理员
     */
    protected function createTestAdmin($username = 'test_admin', $password = 'test123')
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_user (username, password_hash, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        return $this->db->lastInsertId();
    }

    /**
     * 创建测试 Token
     */
    protected function createTestToken($adminId)
    {
        $token = bin2hex(random_bytes(32));
        $expireAt = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $this->db->prepare("
            INSERT INTO admin_token (admin_id, token, expire_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$adminId, $token, $expireAt]);

        return $token;
    }

    /**
     * 创建测试影片
     */
    protected function createTestVideo($data = [])
    {
        $defaults = [
            'title' => '测试影片',
            'cover_url' => 'https://example.com/test.jpg',
            'description' => '测试描述',
            'status' => 1
        ];

        $data = array_merge($defaults, $data);

        $stmt = $this->db->prepare("
            INSERT INTO video (title, cover_url, description, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['title'],
            $data['cover_url'],
            $data['description'],
            $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * 创建测试播放源
     */
    protected function createTestSource($videoId, $data = [])
    {
        $defaults = [
            'source_name' => '测试线路',
            'm3u8_url' => 'https://example.com/test.m3u8'
        ];

        $data = array_merge($defaults, $data);

        $stmt = $this->db->prepare("
            INSERT INTO video_source (video_id, source_name, m3u8_url, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $videoId,
            $data['source_name'],
            $data['m3u8_url']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * 断言数据库有记录
     */
    protected function assertDatabaseHas($table, $conditions)
    {
        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        $this->assertGreaterThan(0, $result['count'], "数据库表 $table 中没有找到匹配的记录");
    }

    /**
     * 断言数据库没有记录
     */
    protected function assertDatabaseMissing($table, $conditions)
    {
        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $where[] = "$key = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        $this->assertEquals(0, $result['count'], "数据库表 $table 中找到了不应该存在的记录");
    }
}
