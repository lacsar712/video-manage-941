<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * 辅助函数单元测试
 */
class HelpersTest extends TestCase
{
    /**
     * 测试 formatDateTime 函数
     */
    public function testFormatDateTime()
    {
        // 测试时间戳
        $result = formatDateTime(1640000000);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);

        // 测试日期字符串
        $result = formatDateTime('2026-01-28 18:00:00');
        $this->assertEquals('2026-01-28 18:00:00', $result);

        // 测试空值
        $result = formatDateTime('');
        $this->assertEquals('', $result);
    }

    /**
     * 测试 generateToken 函数
     */
    public function testGenerateToken()
    {
        $token1 = generateToken();
        $token2 = generateToken();

        // 测试长度
        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));

        // 测试唯一性
        $this->assertNotEquals($token1, $token2);

        // 测试格式（十六进制）
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token1);
    }

    /**
     * 测试 validateLength 函数
     */
    public function testValidateLength()
    {
        // 测试正常情况
        $this->expectNotToPerformAssertions();
        validateLength('测试', 1, 10, '测试字段');

        // 测试长度不足
        $this->expectException(\Exception::class);
        validateLength('', 1, 10, '测试字段');
    }

    /**
     * 测试 validateUrl 函数
     */
    public function testValidateUrl()
    {
        // 测试有效 URL
        $this->expectNotToPerformAssertions();
        validateUrl('https://example.com/test.jpg', '测试URL');

        // 测试无效 URL
        $this->expectException(\Exception::class);
        validateUrl('invalid-url', '测试URL');
    }

    /**
     * 测试 validateInt 函数
     */
    public function testValidateInt()
    {
        // 测试有效整数
        $this->expectNotToPerformAssertions();
        validateInt(123, '测试整数');
        validateInt('456', '测试整数');

        // 测试无效整数
        $this->expectException(\Exception::class);
        validateInt('abc', '测试整数');
    }

    /**
     * 测试 sanitizeInput 函数
     */
    public function testSanitizeInput()
    {
        // 测试字符串
        $result = sanitizeInput('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        // 测试数组
        $result = sanitizeInput([
            'name' => '<b>test</b>',
            'value' => '<script>xss</script>'
        ]);
        $this->assertIsArray($result);
        $this->assertStringNotContainsString('<script>', $result['value']);
    }
}
