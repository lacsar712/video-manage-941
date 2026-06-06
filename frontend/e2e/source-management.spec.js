import { test, expect } from '@playwright/test';

// 辅助函数：登录
async function login(page) {
  await page.goto('/');
  await page.fill('input[placeholder="请输入用户名"]', 'admin');
  await page.fill('input[placeholder="请输入密码"]', 'admin123');
  await page.click('button:has-text("登录")');
  await expect(page).toHaveURL(/\/videos/);
}

test.describe('播放源管理功能', () => {
  let videoId;

  test.beforeEach(async ({ page }) => {
    await login(page);

    // 创建测试影片并获取ID
    await page.click('button:has-text("新增影片")');
    await page.fill('input[placeholder="请输入影片标题"]', '播放源测试影片');
    await page.fill('input[placeholder="请输入封面图片URL"]', 'https://example.com/source-test.jpg');
    await page.click('button:has-text("提交")');
    await page.waitForTimeout(1000);

    // 进入播放源管理
    await page.locator('button:has-text("播放源")').first().click();
    const url = page.url();
    videoId = url.split('/').pop();
  });

  test('应该显示播放源列表页面', async ({ page }) => {
    await expect(page.locator('h2:has-text("播放源管理")')).toBeVisible();
    await expect(page.locator('button:has-text("新增播放源")')).toBeVisible();
    await expect(page.locator('button:has-text("返回")')).toBeVisible();
  });

  test('应该支持新增播放源', async ({ page }) => {
    await page.click('button:has-text("新增播放源")');

    // 填写表单
    await page.fill('input[placeholder="请输入线路名称"]', '测试线路1');
    await page.fill('input[placeholder="请输入M3U8播放地址"]', 'https://example.com/test1.m3u8');
    await page.click('button:has-text("提交")');

    // 验证成功
    await expect(page.locator('text=添加成功')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('text=测试线路1')).toBeVisible();
  });

  test('应该验证必填字段', async ({ page }) => {
    await page.click('button:has-text("新增播放源")');
    await page.click('button:has-text("提交")');

    await expect(page.locator('text=请输入线路名称')).toBeVisible();
  });

  test('应该验证URL格式', async ({ page }) => {
    await page.click('button:has-text("新增播放源")');

    await page.fill('input[placeholder="请输入线路名称"]', '测试线路');
    await page.fill('input[placeholder="请输入M3U8播放地址"]', 'invalid-url');
    await page.click('button:has-text("提交")');

    await expect(page.locator('text=请输入有效的URL')).toBeVisible();
  });

  test('应该支持编辑播放源', async ({ page }) => {
    // 先创建一个播放源
    await page.click('button:has-text("新增播放源")');
    await page.fill('input[placeholder="请输入线路名称"]', '原线路名');
    await page.fill('input[placeholder="请输入M3U8播放地址"]', 'https://example.com/original.m3u8');
    await page.click('button:has-text("提交")');
    await page.waitForTimeout(1000);

    // 编辑
    await page.locator('button:has-text("编辑")').first().click();
    await page.locator('input[placeholder="请输入线路名称"]').clear();
    await page.fill('input[placeholder="请输入线路名称"]', '更新后的线路名');
    await page.click('button:has-text("提交")');

    // 验证
    await expect(page.locator('text=更新成功')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('text=更新后的线路名')).toBeVisible();
  });

  test('应该支持删除播放源', async ({ page }) => {
    // 先创建一个播放源
    await page.click('button:has-text("新增播放源")');
    await page.fill('input[placeholder="请输入线路名称"]', '待删除线路');
    await page.fill('input[placeholder="请输入M3U8播放地址"]', 'https://example.com/delete.m3u8');
    await page.click('button:has-text("提交")');
    await page.waitForTimeout(1000);

    // 删除
    await page.locator('button:has-text("删除")').first().click();
    await page.locator('button:has-text("确定")').click();

    // 验证
    await expect(page.locator('text=删除成功')).toBeVisible({ timeout: 5000 });
  });

  test('应该支持返回影片列表', async ({ page }) => {
    await page.click('button:has-text("返回")');
    await expect(page).toHaveURL(/\/videos$/);
  });
});
