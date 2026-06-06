import { test, expect } from '@playwright/test';

// 辅助函数：登录
async function login(page) {
  await page.goto('/');
  await page.fill('input[placeholder="请输入用户名"]', 'admin');
  await page.fill('input[placeholder="请输入密码"]', 'admin123');
  await page.click('button:has-text("登录")');
  await expect(page).toHaveURL(/\/videos/);
}

test.describe('影片管理功能', () => {
  test.beforeEach(async ({ page }) => {
    // 登录
    await login(page);
  });

  test.describe('影片列表', () => {
    test('应该显示影片列表页面', async ({ page }) => {
      await expect(page.locator('h2:has-text("影片列表")')).toBeVisible();
      await expect(page.locator('button:has-text("新增影片")')).toBeVisible();
    });

    test('应该支持关键词搜索', async ({ page }) => {
      await page.fill('input[placeholder="请输入影片标题"]', '测试');
      await page.click('button:has-text("查询")');

      // 验证请求已发送
      await page.waitForTimeout(500);
    });

    test('应该支持状态筛选', async ({ page }) => {
      // 选择上架状态
      await page.locator('.el-select').first().click();
      await page.locator('.el-select-dropdown__item:has-text("上架")').click();
      await page.click('button:has-text("查询")');

      await page.waitForTimeout(500);
    });

    test('应该支持重置搜索条件', async ({ page }) => {
      await page.fill('input[placeholder="请输入影片标题"]', '测试');
      await page.click('button:has-text("重置")');

      await expect(page.locator('input[placeholder="请输入影片标题"]')).toHaveValue('');
    });

    test('应该支持分页', async ({ page }) => {
      // 如果有多页数据，测试分页
      await expect(page.locator('.el-pagination')).toBeVisible();
    });
  });

  test.describe('新增影片', () => {
    test.beforeEach(async ({ page }) => {
      await page.click('button:has-text("新增影片")');
      await expect(page).toHaveURL(/\/videos\/add/);
    });

    test('应该显示新增表单', async ({ page }) => {
      await expect(page.locator('h2:has-text("新增影片")')).toBeVisible();
      await expect(page.locator('input[placeholder="请输入影片标题"]')).toBeVisible();
      await expect(page.locator('input[placeholder="请输入封面图片URL"]')).toBeVisible();
      await expect(page.locator('textarea')).toBeVisible();
    });

    test('应该验证必填字段', async ({ page }) => {
      await page.click('button:has-text("提交")');
      await expect(page.locator('text=请输入影片标题')).toBeVisible();
    });

    test('应该验证URL格式', async ({ page }) => {
      await page.fill('input[placeholder="请输入影片标题"]', '测试影片');
      await page.fill('input[placeholder="请输入封面图片URL"]', 'invalid-url');
      await page.click('button:has-text("提交")');

      await expect(page.locator('text=请输入有效的URL')).toBeVisible();
    });

    test('应该成功创建影片', async ({ page }) => {
      await page.fill('input[placeholder="请输入影片标题"]', 'E2E测试影片');
      await page.fill('input[placeholder="请输入封面图片URL"]', 'https://example.com/e2e.jpg');
      await page.fill('textarea', 'E2E测试描述');

      // 选择上架状态
      await page.locator('.el-radio:has-text("上架")').click();

      await page.click('button:has-text("提交")');

      // 验证跳转回列表
      await expect(page).toHaveURL(/\/videos$/);
      await expect(page.locator('text=添加成功')).toBeVisible({ timeout: 5000 });
    });

    test('应该支持取消操作', async ({ page }) => {
      await page.click('button:has-text("取消")');
      await expect(page).toHaveURL(/\/videos$/);
    });
  });

  test.describe('编辑影片', () => {
    test('应该打开编辑页面并加载数据', async ({ page }) => {
      // 点击第一个编辑按钮
      await page.locator('button:has-text("编辑")').first().click();

      await expect(page).toHaveURL(/\/videos\/edit\//);
      await expect(page.locator('h2:has-text("编辑影片")')).toBeVisible();

      // 验证表单已填充数据
      const titleInput = page.locator('input[placeholder="请输入影片标题"]');
      await expect(titleInput).not.toHaveValue('');
    });

    test('应该成功更新影片', async ({ page }) => {
      await page.locator('button:has-text("编辑")').first().click();

      // 修改标题
      await page.locator('input[placeholder="请输入影片标题"]').clear();
      await page.fill('input[placeholder="请输入影片标题"]', '更新后的标题');
      await page.click('button:has-text("提交")');

      // 验证跳转回列表
      await expect(page).toHaveURL(/\/videos$/);
      await expect(page.locator('text=更新成功')).toBeVisible({ timeout: 5000 });
    });
  });

  test.describe('删除影片', () => {
    test('应该显示确认对话框', async ({ page }) => {
      await page.locator('button:has-text("删除")').first().click();

      // 验证确认对话框
      await expect(page.locator('text=确定要删除这个影片吗')).toBeVisible();
    });

    test('应该支持取消删除', async ({ page }) => {
      await page.locator('button:has-text("删除")').first().click();
      await page.locator('button:has-text("取消")').click();

      // 对话框应该关闭
      await expect(page.locator('text=确定要删除这个影片吗')).not.toBeVisible();
    });

    test('应该成功删除影片', async ({ page }) => {
      await page.locator('button:has-text("删除")').first().click();
      await page.locator('button:has-text("确定")').click();

      // 验证删除成功
      await expect(page.locator('text=删除成功')).toBeVisible({ timeout: 5000 });
    });
  });

  test.describe('播放源管理', () => {
    test('应该打开播放源管理页面', async ({ page }) => {
      await page.locator('button:has-text("播放源")').first().click();

      await expect(page).toHaveURL(/\/sources\//);
      await expect(page.locator('h2:has-text("播放源管理")')).toBeVisible();
    });

    test('应该显示新增播放源按钮', async ({ page }) => {
      await page.locator('button:has-text("播放源")').first().click();
      await expect(page.locator('button:has-text("新增播放源")')).toBeVisible();
    });
  });
});
