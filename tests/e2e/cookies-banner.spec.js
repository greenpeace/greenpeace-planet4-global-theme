import {test, expect} from './tools/lib/test-utils.js';

test('check cookies banner', async ({page}) => {
  await page.goto('./');

  const cookiesText = await page.evaluate('window.p4_vars.options.cookies_field');
  expect(cookiesText).toBeDefined();
  const cookiesBanner = page.locator('#set-cookie');
  if (!cookiesText) {
    // Check that cookies banner is hidden.
    await expect(cookiesBanner).toBeHidden();
  } else {
    // Check that cookies banner is visible.
    await expect(cookiesBanner).toBeVisible();

    // Check that the text is the one from the P4 settings.
    const cookiesContent = await cookiesBanner.locator('.cookies-text').innerHTML();
    expect(cookiesContent).toContain(cookiesText.replace('&', '&amp;'));

    // Accept all cookies.
    await cookiesBanner.getByText('Accept all cookies').click();

    // Check that the cookies banner disappears.
    await expect(cookiesBanner).toBeHidden();
  }
});
