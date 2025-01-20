/* eslint-disable no-console */
import {test, expect} from './lib/test-utils.js';

test('check admin PHP errors', async ({page}) => {
  test.setTimeout(240 * 1000);

  await page.goto('/wp-admin/');

  await page.locator('#user_login').focus();
  await page.fill('#user_login', 'admin');
  await page.locator('#user_pass').focus();
  await page.fill('#user_pass', 'admin');
  await page.getByText('Log In').click();

  await expect(page.locator('#wpadminbar')).toBeVisible();

  const allLinks = await page.evaluate(() => [...new Set(Array.from(document.querySelectorAll('#adminmenu a[href]'))
    .filter(n => n?.href && n.href.startsWith('http://www.planet4.test/')) //NOSONAR
    .map(n => n.href))]);

  for (let i = 0; i < allLinks.length; i++) {
    console.log(allLinks[i]);

    await page.goto(allLinks[i]);
    const html = await page.content();
    await expect.soft(html).not.toContain('<b>Deprecated</b>');
    await expect.soft(html).not.toContain('<b>Notice</b>');
    await expect.soft(html).not.toContain('<b>Warning</b>');
    await expect.soft(html).not.toContain('<b>Fatal error</b>');
  }
});

test('check rest PHP errors', async ({page, request}) => {
  //test.setTimeout(240 * 1000);

  await page.goto('/wp-admin/');

  await page.locator('#user_login').focus();
  await page.fill('#user_login', 'admin');
  await page.locator('#user_pass').focus();
  await page.fill('#user_pass', 'admin');
  await page.getByText('Log In').click();

  await expect(page.locator('#wpadminbar')).toBeVisible();

  const jsonApi = await request.get('http://www.planet4.test/wp-json'); //NOSONAR
  const data = await jsonApi.json();
  const routes = data.routes;

  for (const route in routes) {
    //console.log(route, routes[route]);

    if (!routes[route].methods.includes('GET')) {
      continue;
    }

    console.log(`http://www.planet4.test/wp-json${route}`);
    if (routes[route].endpoints.length > 1) {
      checkendpoint: for (let i = 0; i < routes[route].endpoints.length; i++) {
        if (!routes[route].endpoints[i].methods.includes('GET')) {
          continue;
        }
        for (const arg in routes[route].endpoints[i].args) {
          if (routes[route].endpoints[i].args[arg].required) {
            continue checkendpoint;
          }
        }
        //console.log(routes[route].endpoints[i]);
      }
    }

    const response = await request.get(`http://www.planet4.test/wp-json${route}`);
    console.log(response.status());
    expect.soft(response.status() < 500).toBeTruthy();
  }
});
