<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * 辅助函数单元测试
 */
class HelpersTest extends TestCase
{
    // ============================================================
    // 1. 响应封装测试：jsonResponse / success / error
    // ============================================================

    /**
     * 测试 success 函数 - 默认参数
     */
    public function testSuccessDefault()
    {
        $response = $this->captureJsonResponse(function () {
            success();
        });

        $this->assertNotNull($response);
        $this->assertEquals(0, $response['code']);
        $this->assertEquals('操作成功', $response['message']);
        $this->assertNull($response['data']);
    }

    /**
     * 测试 success 函数 - 自定义 data 和 message
     */
    public function testSuccessWithDataAndMessage()
    {
        $data = ['id' => 1, 'name' => 'test'];
        $response = $this->captureJsonResponse(function () use ($data) {
            success($data, '创建成功');
        });

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('创建成功', $response['message']);
        $this->assertEquals($data, $response['data']);
    }

    /**
     * 测试 success 函数 - data 为 null，自定义 message
     */
    public function testSuccessWithMessageOnly()
    {
        $response = $this->captureJsonResponse(function () {
            success(null, '删除成功');
        });

        $this->assertEquals(0, $response['code']);
        $this->assertEquals('删除成功', $response['message']);
        $this->assertNull($response['data']);
    }

    /**
     * 测试 error 函数 - 默认 code
     */
    public function testErrorDefaultCode()
    {
        $response = $this->captureJsonResponse(function () {
            error('出错了');
        });

        $this->assertNotNull($response);
        $this->assertEquals(1, $response['code']);
        $this->assertEquals('出错了', $response['message']);
        $this->assertNull($response['data']);
    }

    /**
     * 测试 error 函数 - 自定义 code
     */
    public function testErrorWithCustomCode()
    {
        $response = $this->captureJsonResponse(function () {
            error('未找到', 404);
        });

        $this->assertEquals(404, $response['code']);
        $this->assertEquals('未找到', $response['message']);
        $this->assertNull($response['data']);
    }

    /**
     * 测试 error 函数 - 401 未授权 code
     */
    public function testErrorUnauthorizedCode()
    {
        $response = $this->captureJsonResponse(function () {
            error('未登录', 401);
        });

        $this->assertEquals(401, $response['code']);
        $this->assertEquals('未登录', $response['message']);
    }

    /**
     * 测试 jsonResponse 函数 - 完整参数
     */
    public function testJsonResponseFull()
    {
        $response = $this->captureJsonResponse(function () {
            jsonResponse(20001, '业务错误', ['detail' => 'info']);
        });

        $this->assertEquals(20001, $response['code']);
        $this->assertEquals('业务错误', $response['message']);
        $this->assertEquals(['detail' => 'info'], $response['data']);
    }

    /**
     * 测试 jsonResponse 函数 - data 为数组/对象/空值
     */
    public function testJsonResponseDataVariants()
    {
        $response1 = $this->captureJsonResponse(function () {
            jsonResponse(0, 'ok', ['list' => [], 'total' => 0]);
        });
        $this->assertEquals(['list' => [], 'total' => 0], $response1['data']);

        $response2 = $this->captureJsonResponse(function () {
            jsonResponse(0, 'ok', 123);
        });
        $this->assertSame(123, $response2['data']);

        $response3 = $this->captureJsonResponse(function () {
            jsonResponse(0, 'ok', '');
        });
        $this->assertSame('', $response3['data']);
    }

    // ============================================================
    // 2. 校验逻辑测试：validateRequired / validateLength / validateUrl / validateInt
    // ============================================================

    /**
     * 测试 validateRequired - 全部合法
     */
    public function testValidateRequiredAllValid()
    {
        $this->assertPassesValidation(function () {
            validateRequired(
                ['username' => '用户名', 'password' => '密码'],
                ['username' => 'admin', 'password' => '123456']
            );
        });
    }

    /**
     * 测试 validateRequired - 字段缺失
     */
    public function testValidateRequiredFieldMissing()
    {
        $this->assertErrorResponse(function () {
            validateRequired(
                ['username' => '用户名', 'password' => '密码'],
                ['username' => 'admin']
            );
        }, 1, '密码不能为空');
    }

    /**
     * 测试 validateRequired - 字段为空字符串
     */
    public function testValidateRequiredEmptyString()
    {
        $this->assertErrorResponse(function () {
            validateRequired(
                ['username' => '用户名'],
                ['username' => '']
            );
        }, 1, '用户名不能为空');
    }

    /**
     * 测试 validateRequired - 字段为纯空白字符（应被 trim 后判空）
     */
    public function testValidateRequiredWhitespaceOnly()
    {
        $this->assertErrorResponse(function () {
            validateRequired(
                ['username' => '用户名'],
                ['username' => '   ']
            );
        }, 1, '用户名不能为空');
    }

    /**
     * 测试 validateRequired - 多字段校验时第一个不合法优先报错
     */
    public function testValidateRequiredFirstInvalidField()
    {
        $this->assertErrorResponse(function () {
            validateRequired(
                ['a' => '字段A', 'b' => '字段B'],
                ['a' => '', 'b' => '']
            );
        }, 1, '字段A不能为空');
    }

    /**
     * 测试 validateRequired - 字段为 0（非空）
     */
    public function testValidateRequiredZeroIsValid()
    {
        $this->assertPassesValidation(function () {
            validateRequired(
                ['count' => '数量'],
                ['count' => '0']
            );
        });
    }

    // ---- validateLength ----

    /**
     * 测试 validateLength - 合法值
     */
    public function testValidateLengthValid()
    {
        $this->assertPassesValidation(function () {
            validateLength('abc', 1, 10, '名称');
        });
    }

    /**
     * 测试 validateLength - 中文字符计数
     */
    public function testValidateLengthChineseCharacters()
    {
        $this->assertPassesValidation(function () {
            validateLength('你好世界', 4, 4, '中文');
        });
    }

    /**
     * 测试 validateLength - 下边界（等于 min）
     */
    public function testValidateLengthAtMinBoundary()
    {
        $this->assertPassesValidation(function () {
            validateLength('a', 1, 10, '名称');
        });
    }

    /**
     * 测试 validateLength - 上边界（等于 max）
     */
    public function testValidateLengthAtMaxBoundary()
    {
        $this->assertPassesValidation(function () {
            validateLength(str_repeat('x', 200), 1, 200, '标题');
        });
    }

    /**
     * 测试 validateLength - 长度不足（小于 min）
     */
    public function testValidateLengthBelowMin()
    {
        $this->assertErrorResponse(function () {
            validateLength('', 1, 10, '名称');
        }, 1, '名称长度必须在1-10个字符之间');
    }

    /**
     * 测试 validateLength - 长度超出（大于 max）
     */
    public function testValidateLengthAboveMax()
    {
        $this->assertErrorResponse(function () {
            validateLength(str_repeat('x', 201), 1, 200, '标题');
        }, 1, '标题长度必须在1-200个字符之间');
    }

    /**
     * 测试 validateLength - min=0 允许空串
     */
    public function testValidateLengthMinZeroAllowsEmpty()
    {
        $this->assertPassesValidation(function () {
            validateLength('', 0, 1000, '描述');
        });
    }

    /**
     * 测试 validateLength - 中日韩混合字符
     */
    public function testValidateLengthMixedChars()
    {
        $this->assertPassesValidation(function () {
            validateLength('hello世界', 7, 7, '混合');
        });
    }

    // ---- validateUrl ----

    /**
     * 测试 validateUrl - 合法 HTTPS URL
     */
    public function testValidateUrlValidHttps()
    {
        $this->assertPassesValidation(function () {
            validateUrl('https://example.com/path/to/file.jpg', '封面');
        });
    }

    /**
     * 测试 validateUrl - 合法 HTTP URL
     */
    public function testValidateUrlValidHttp()
    {
        $this->assertPassesValidation(function () {
            validateUrl('http://example.org/test?a=1', '链接');
        });
    }

    /**
     * 测试 validateUrl - 合法 URL 带端口号
     */
    public function testValidateUrlValidWithPort()
    {
        $this->assertPassesValidation(function () {
            validateUrl('https://cdn.example.com:8080/cover.jpg', '图片');
        });
    }

    /**
     * 测试 validateUrl - 非法：无协议
     */
    public function testValidateUrlInvalidNoScheme()
    {
        $this->assertErrorResponse(function () {
            validateUrl('example.com/test.jpg', '封面');
        }, 1, '封面格式不正确');
    }

    /**
     * 测试 validateUrl - 非法：纯字符串
     */
    public function testValidateUrlInvalidPlainString()
    {
        $this->assertErrorResponse(function () {
            validateUrl('not-a-url', '链接');
        }, 1, '链接格式不正确');
    }

    /**
     * 测试 validateUrl - 非法：空字符串
     */
    public function testValidateUrlInvalidEmpty()
    {
        $this->assertErrorResponse(function () {
            validateUrl('', 'URL');
        }, 1, 'URL格式不正确');
    }

    /**
     * 测试 validateUrl - 非法：仅包含空白
     */
    public function testValidateUrlInvalidWhitespace()
    {
        $this->assertErrorResponse(function () {
            validateUrl('   ', 'URL');
        }, 1, 'URL格式不正确');
    }

    // ---- validateInt ----

    /**
     * 测试 validateInt - 合法整型
     */
    public function testValidateIntValidInteger()
    {
        $this->assertPassesValidation(function () {
            validateInt(123, 'ID');
        });
    }

    /**
     * 测试 validateInt - 合法数字字符串
     */
    public function testValidateIntValidNumericString()
    {
        $this->assertPassesValidation(function () {
            validateInt('456', 'ID');
        });
    }

    /**
     * 测试 validateInt - 合法：0
     */
    public function testValidateIntValidZero()
    {
        $this->assertPassesValidation(function () {
            validateInt(0, '数量');
            validateInt('0', '数量');
        });
    }

    /**
     * 测试 validateInt - 合法：负整数
     */
    public function testValidateIntValidNegative()
    {
        $this->assertPassesValidation(function () {
            validateInt(-10, '数值');
            validateInt('-10', '数值');
        });
    }

    /**
     * 测试 validateInt - 非法：浮点数
     */
    public function testValidateIntInvalidFloat()
    {
        $this->assertErrorResponse(function () {
            validateInt(12.34, 'ID');
        }, 1, 'ID必须是整数');
    }

    /**
     * 测试 validateInt - 非法：浮点数字符串
     */
    public function testValidateIntInvalidFloatString()
    {
        $this->assertErrorResponse(function () {
            validateInt('12.5', 'ID');
        }, 1, 'ID必须是整数');
    }

    /**
     * 测试 validateInt - 非法：非数字字符串
     */
    public function testValidateIntInvalidNonNumeric()
    {
        $this->assertErrorResponse(function () {
            validateInt('abc', 'ID');
        }, 1, 'ID必须是整数');
    }

    /**
     * 测试 validateInt - 非法：空字符串
     */
    public function testValidateIntInvalidEmpty()
    {
        $this->assertErrorResponse(function () {
            validateInt('', 'ID');
        }, 1, 'ID必须是整数');
    }

    // ============================================================
    // 3. 清理 / 工具函数：sanitizeInput / sanitizeOutput / formatDateTime / generateToken
    // ============================================================

    /**
     * 测试 sanitizeInput - 字符串 XSS 过滤
     */
    public function testSanitizeInputStringXss()
    {
        $input = '<script>alert("xss")</script>';
        $result = sanitizeInput($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * 测试 sanitizeInput - 字符串首尾空白被 trim
     */
    public function testSanitizeInputTrimsWhitespace()
    {
        $result = sanitizeInput('  hello  ');
        $this->assertEquals('hello', $result);
    }

    /**
     * 测试 sanitizeInput - 数组递归处理
     */
    public function testSanitizeInputArrayRecursive()
    {
        $input = [
            'name' => '<b>test</b>',
            'nested' => [
                'value' => '<script>x</script>',
                'plain' => 'ok'
            ]
        ];
        $result = sanitizeInput($input);

        $this->assertIsArray($result);
        $this->assertStringNotContainsString('<b>', $result['name']);
        $this->assertStringNotContainsString('<script>', $result['nested']['value']);
        $this->assertEquals('ok', $result['nested']['plain']);
    }

    /**
     * 测试 sanitizeInput - 空数组
     */
    public function testSanitizeInputEmptyArray()
    {
        $this->assertEquals([], sanitizeInput([]));
    }

    /**
     * 测试 sanitizeInput - 特殊字符 ENT_QUOTES
     */
    public function testSanitizeInputQuotes()
    {
        $result = sanitizeInput("it's a \"test\"");
        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    /**
     * 测试 sanitizeOutput - 字符串转义（不 trim）
     */
    public function testSanitizeOutputString()
    {
        $output = '<div class="test">it\'s content</div>  ';
        $result = sanitizeOutput($output);

        $this->assertStringNotContainsString('<div', $result);
        $this->assertStringContainsString('&lt;div', $result);
        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringEndsWith('  ', $result);
    }

    /**
     * 测试 sanitizeOutput - 数组递归
     */
    public function testSanitizeOutputArray()
    {
        $input = [
            'a' => '<p>a</p>',
            'b' => ['c' => '<span>c</span>']
        ];
        $result = sanitizeOutput($input);

        $this->assertStringNotContainsString('<p>', $result['a']);
        $this->assertStringNotContainsString('<span>', $result['b']['c']);
    }

    // ---- formatDateTime ----

    /**
     * 测试 formatDateTime - 时间戳输入
     */
    public function testFormatDateTimeTimestamp()
    {
        $result = formatDateTime(1700000000);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * 测试 formatDateTime - 日期字符串输入
     */
    public function testFormatDateTimeDateString()
    {
        $result = formatDateTime('2026-01-28 18:00:00');
        $this->assertEquals('2026-01-28 18:00:00', $result);
    }

    /**
     * 测试 formatDateTime - 空字符串返回空
     */
    public function testFormatDateTimeEmptyString()
    {
        $this->assertEquals('', formatDateTime(''));
    }

    /**
     * 测试 formatDateTime - null 返回空
     */
    public function testFormatDateTimeNull()
    {
        $this->assertEquals('', formatDateTime(null));
    }

    /**
     * 测试 formatDateTime - 0 被视为有效时间戳
     */
    public function testFormatDateTimeZeroTimestamp()
    {
        $result = formatDateTime(0);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * 测试 formatDateTime - 相对时间字符串
     */
    public function testFormatDateTimeRelativeString()
    {
        $result = formatDateTime('2026-06-01');
        $this->assertEquals('2026-06-01 00:00:00', $result);
    }

    // ---- generateToken ----

    /**
     * 测试 generateToken - 长度与格式
     */
    public function testGenerateTokenLengthAndFormat()
    {
        $token = generateToken();
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    /**
     * 测试 generateToken - 两次生成不重复
     */
    public function testGenerateTokenUniqueness()
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = generateToken();
        }
        $this->assertEquals(10, count(array_unique($tokens)));
    }

    /**
     * 测试 generateToken - 仅十六进制字符
     */
    public function testGenerateTokenHexOnly()
    {
        $token = generateToken();
        $this->assertTrue(ctype_xdigit($token));
    }

    // ============================================================
    // 4. Token 校验：validateToken
    // ============================================================

    /**
     * 测试 validateToken - 缺失 Authorization header
     */
    public function testValidateTokenMissingHeader()
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->assertUnauthorizedResponse(function () {
            validateToken();
        }, '未登录');
    }

    /**
     * 测试 validateToken - Authorization header 为空字符串
     */
    public function testValidateTokenEmptyHeader()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = '';

        $this->assertUnauthorizedResponse(function () {
            validateToken();
        }, '未登录');
    }

    /**
     * 测试 validateToken - Bearer 前缀但无 token
     */
    public function testValidateTokenBearerOnly()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ';

        $this->assertUnauthorizedResponse(function () {
            validateToken();
        }, '未登录');
    }

    /**
     * 测试 validateToken - 非法 token（数据库中不存在）
     */
    public function testValidateTokenInvalidToken()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . bin2hex(random_bytes(32));

        $this->assertUnauthorizedResponse(function () {
            validateToken();
        }, '登录已过期');
    }

    /**
     * 测试 validateToken - 过期 token
     */
    public function testValidateTokenExpired()
    {
        $adminId = $this->createTestAdmin('tester', 'pass123');
        $token = bin2hex(random_bytes(32));
        $expireAt = date('Y-m-d H:i:s', time() - 3600);

        $stmt = $this->db->prepare("
            INSERT INTO admin_token (admin_id, token, expire_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$adminId, $token, $expireAt]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $this->assertUnauthorizedResponse(function () {
            validateToken();
        }, '登录已过期');
    }

    /**
     * 测试 validateToken - 合法 token
     */
    public function testValidateTokenValid()
    {
        $adminId = $this->createTestAdmin('validuser', 'pass123');
        $token = $this->createTestToken($adminId);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $result = validateToken();

        $this->assertIsArray($result);
        $this->assertEquals($adminId, $result['admin_id']);
        $this->assertEquals('validuser', $result['username']);
        $this->assertEquals($token, $result['token']);
    }

    /**
     * 测试 validateToken - 不带 Bearer 前缀的纯 token（兼容处理）
     */
    public function testValidateTokenWithoutBearerPrefix()
    {
        $adminId = $this->createTestAdmin('noprefix', 'pass123');
        $token = $this->createTestToken($adminId);

        $_SERVER['HTTP_AUTHORIZATION'] = $token;

        $result = validateToken();

        $this->assertIsArray($result);
        $this->assertEquals($adminId, $result['admin_id']);
    }

    // ============================================================
    // 5. 操作日志：writeOperationLog
    // ============================================================

    /**
     * 测试 writeOperationLog - 成功写入完整字段
     */
    public function testWriteOperationLogSuccessFullFields()
    {
        $adminId = $this->createTestAdmin('logger', 'pass123');

        $result = writeOperationLog(
            $adminId,
            'video',
            'create',
            'video',
            100,
            '创建影片《测试》',
            'success',
            null
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('operation_log', [
            'admin_id' => $adminId,
            'module' => 'video',
            'action' => 'create',
            'target_type' => 'video',
            'target_id' => 100,
            'content' => '创建影片《测试》',
            'status' => 'success'
        ]);
    }

    /**
     * 测试 writeOperationLog - 使用默认可选参数
     */
    public function testWriteOperationLogDefaultOptionalFields()
    {
        $adminId = $this->createTestAdmin('logger2', 'pass123');

        $result = writeOperationLog($adminId, 'auth', 'login');

        $this->assertTrue($result);
        $this->assertDatabaseHas('operation_log', [
            'admin_id' => $adminId,
            'module' => 'auth',
            'action' => 'login',
            'status' => 'success'
        ]);

        $stmt = $this->db->prepare("SELECT * FROM operation_log WHERE admin_id = ? AND action = 'login'");
        $stmt->execute([$adminId]);
        $log = $stmt->fetch();
        $this->assertNull($log['target_type']);
        $this->assertNull($log['target_id']);
        $this->assertNull($log['content']);
        $this->assertNull($log['error_message']);
    }

    /**
     * 测试 writeOperationLog - 失败状态及 error_message
     */
    public function testWriteOperationLogFailedStatus()
    {
        $adminId = $this->createTestAdmin('logger3', 'pass123');

        $result = writeOperationLog(
            $adminId,
            'video',
            'delete',
            'video',
            999,
            null,
            'failed',
            '影片不存在'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('operation_log', [
            'admin_id' => $adminId,
            'module' => 'video',
            'action' => 'delete',
            'status' => 'failed',
            'error_message' => '影片不存在'
        ]);
    }

    /**
     * 测试 writeOperationLog - created_at 自动填充
     */
    public function testWriteOperationLogCreatedAtFilled()
    {
        $adminId = $this->createTestAdmin('logger4', 'pass123');
        writeOperationLog($adminId, 'system', 'test');

        $stmt = $this->db->prepare("SELECT created_at FROM operation_log WHERE admin_id = ? AND module = 'system'");
        $stmt->execute([$adminId]);
        $log = $stmt->fetch();

        $this->assertNotEmpty($log['created_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $log['created_at']);
    }
}
