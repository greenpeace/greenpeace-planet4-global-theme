import {test, expect} from './tools/lib/test-utils.js';

test.useAdminLoggedIn();

test('checks if the welcome modal on the editor is present and closed when the button is clicked', async ({page, admin}) => {
  await admin.createNewPost({postType: 'page', legacyCanvas: true});

  const modal = page.getByRole('dialog', {name: 'Choose a pattern'});
  await expect(modal).toBeVisible();

  for (const frame of await modal.getByRole('option', {name: 'Editor canvas'}).all()) {
    await frame.waitForLoadState('domcontentloaded');
  }
  await page.waitForLoadState('domcontentloaded');

  await modal.getByRole('button', {name: 'Close'}).click({timeout: 15000});
  await expect(modal).toBeHidden();
});
