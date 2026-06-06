import { test, expect } from '@playwright/test';

test.describe('登录功能', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('应该显示登录页面', async ({ page }) => {
    // 验证页面标题
    await expect(page.locator('h2')).toContainText('影片管理系统');

    // 验证表单元素存在
    await expect(page.locator('input[placeholder="请输入用户名"]')).toBeVisible();
    await expect(page.locator('input[placeholder="请输入密码"]')).toBeVisible();
    await expect(page.locator('button:has-text("登录")')).toBeVisible();
  });

  test('应该验证必填字段', async ({ page }) => {
    // 点击登录按钮
    await page.click('button:has-text("登录")');

    // 等待验证消息
    await page.waitForTimeout(500);

    // 验证错误提示
    await expect(page.locator('text=请输入用户名')).toBeVisible();
  });

  test('应该在用户名错误时显示错误', async ({ page }) => {
    // 填写错误的用户名
    await page.fill('input[placeholder="请输入用户名"]', 'wrong_user');
    await page.fill('input[placeholder="请输入密码"]', 'admin123');

    // 点击登录
    await page.click('button:has-text("登录")');

    // 等待错误消息
    await expect(page.locator('text=用户名或密码错误')).toBeVisible({ timeout: 5000 });
  });

  test('应该在密码错误时显示错误', async ({ page }) => {
    // 填写错误的密码
    await page.fill('input[placeholder="请输入用户名"]', 'admin');
    await page.fill('input[placeholder="请输入密码"]', 'wrong_password');

    // 点击登录
    await page.click('button:has-text("登录")');

    // 等待错误消息
    await expect(page.locator('text=用户名或密码错误')).toBeVisible({ timeout: 5000 });
  });

  test('应该成功登录并跳转到影片列表', async ({ page }) => {
    // 填写正确的用户名和密码
    await page.fill('input[placeholder="请输入用户名"]', 'admin');
    await page.fill('input[placeholder="请输入密码"]', 'admin123');

    // 点击登录
    await page.click('button:has-text("登录")');

    // 验证跳转
    await expect(page).toHaveURL(/\/videos/);
    await expect(page.locator('text=影片列表')).toBeVisible();

    // 验证token已保存
    const token = await page.evaluate(() => localStorage.getItem('token'));
    expect(token).toBeTruthy();
  });
});
