import {expect} from './test-utils';

/**
 * Publishes a post using the provided editor and returns the URL of the published post.
 *
 * @param {Object} params        - Parameters for publishing the post.
 * @param {Object} params.page   - The page object for interacting with the browser.
 * @param {Object} params.editor - The editor object used to publish the post.
 *
 * @return {Promise<string>} The URL of the published post.
 */
async function publishPost({page, editor}) {
  // We should be able to remove this check once we update Playwright to the latest version.
  const closeSettingsSidebar = await page.getByRole('button', {name: 'Close Settings'});
  if (await closeSettingsSidebar.isVisible()) {
    await closeSettingsSidebar.click();
  }
  await editor.publishPost();

  const urlString = await page
    .getByRole('region', {name: 'Editor publish'})
    .getByRole('textbox', {name: 'address'})
    .inputValue();

  return urlString;
}

/**
 * Updates a post and waits for the confirmation snackbar.
 *
 * @param {Object} params      - Parameters for updating the post.
 * @param {Object} params.page - The page object representing the browser page.
 *
 * @return {Promise<void>} - A promise that resolves when the snackbar confirming the update is visible.
 */
async function updatePost({page}) {
  const updateButton = await page.locator('.editor-header__settings').getByRole('button', {name: 'Save'});
  await updateButton.click();

  return page.waitForSelector('.components-snackbar');
}

/**
 * Publishes a post and then navigates to the published post's URL.
 *
 * @param {Object} params        - Parameters for publishing the post and visiting the URL.
 * @param {Object} params.page   - The page object for interacting with the browser.
 * @param {Object} params.editor - The editor object used to publish the post.
 */
async function publishPostAndVisit({page, editor}) {
  const urlString = await publishPost({page, editor});

  await page.goto(urlString);
}

/**
 * Creates a new post with a featured image set.
 *
 * @param {Object} p        - Parameters for creating the post and setting the featured image.
 * @param {Object} p.page   - The page object used to interact with the editor.
 * @param {Object} p.admin  - The admin object used to create a new post.
 * @param {Object} p.editor - The editor object used to interact with the editor.
 * @param {Object} params   - Additional parameters for creating the post.
 * @return {Promise<Object>} The newly created post.
 */
async function createPostWithFeaturedImage({page, admin, editor}, params) {
  const newPost = await admin.createNewPost({...params, legacyCanvas: true});

  await editor.openDocumentSettingsSidebar();

  await page.getByRole('button', {name: 'Set featured image'}).click();

  await page.getByRole('dialog', {name: 'Featured image'});
  await page.locator('button#menu-item-browse').click();

  const mediaSearchInput = await page.locator('#media-search-input');
  await mediaSearchInput.fill('OCEANS-GP0STOM6C');
  await page.keyboard.press('Enter');

  const thumbnail = await page.locator('li[aria-label="OCEANS-GP0STOM6C"]').nth(0);
  await page.waitForSelector('li[aria-label="OCEANS-GP0STOM6C"]');
  await thumbnail.click();

  // Get the file url
  const fileUrl = await page.locator('#attachment-details-copy-link').inputValue();
  // Remove the file extension
  const fileName = fileUrl.slice(0, fileUrl.length - 4).split('/');
  await page.getByRole('button', {name: 'Set featured image'}).click();

  // check if the featured image is the correctly assigned
  expect(await page.locator('.editor-post-featured-image__preview-image').getAttribute('src'))
    .toContain(fileName[fileName.length - 1]);

  return newPost;
}

export {publishPost, publishPostAndVisit, createPostWithFeaturedImage, updatePost};
