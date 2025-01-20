import {test, expect} from './tools/lib/test-utils.js';

test('Test Blocks report API', async ({page, requestUtils}) => {
  await page.goto('./wp-admin/');

  const apiRoute = './wp-json/plugin_blocks/v3/plugin_blocks_report';
  const response = await requestUtils.request.fetch(apiRoute, {
    failOnStatusCode: true,
  });

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toBe('application/json; charset=UTF-8');

  const json = await response.json();
  expect(json).toHaveProperty('block_types');
  expect(json).toHaveProperty('block_patterns');
  expect(json).toHaveProperty('post_types');

  expect(Object.keys(json.block_types)).not.toBe([]);
  expect(Object.keys(json.block_patterns)).not.toBe([]);
  expect(Object.keys(json.post_types)).not.toBe([]);
});
