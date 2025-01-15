import {Locator, Page} from '@playwright/test'; // eslint-disable-line no-unused-vars

/**
 * @param {{Page, Editor}} options    - Page and Editor object
 * @param {string}         panelTitle - Panel title
 * @return {Locator} Playwright Locator
 */
async function openComponentPanel({page, editor}, panelTitle) {
  await editor.openDocumentSettingsSidebar();

  const editorSettings = await page.getByRole('region', {name: 'Editor settings'});
  await editorSettings.locator('.editor-sidebar__panel-tabs button').first().click();
  const panelButton = await editorSettings.getByRole('button', {name: panelTitle, exact: true});
  const panelExpanded = await panelButton.getAttribute('aria-expanded');
  if (panelExpanded === 'false') {
    await panelButton.click();
  }

  return editorSettings;
}

/**
 * Add a Category to a Post
 *
 * @param {{Page, Editor}} options  - Page and Editor object
 * @param {string}         category - The category
 */
async function addCategory({page, editor}, category) {
  const editorSettings = await openComponentPanel({page, editor}, 'Categories');
  await editorSettings.getByRole('group', {name: 'Categories'}).getByRole('checkbox', {name: category}).click();
}

/**
 * Add a Tag to a Post
 *
 * @param {{Page, Editor}} options - Page and Editor object
 * @param {string}         tag     - The tag
 */
async function addTag({page, editor}, tag) {
  const editorSettings = await openComponentPanel({page, editor}, 'Tags');
  await editorSettings.getByRole('group', {name: 'Tags'}).getByRole('checkbox', {name: tag}).click();
}

/**
 * Add a Post Type to a Post
 *
 * @param {{Page, Editor}} options  - Page and Editor object
 * @param {string}         postType - The post type (Story, Press Release, etc.)
 */
async function addPostType({page, editor}, postType) {
  const editorSettings = await openComponentPanel({page, editor}, 'Post Types');
  await editorSettings.getByLabel('Add new Post Type').fill(postType);
  await editorSettings.getByRole('option', {name: postType}).click();
}

/**
 * Remove all Post Types from a Post
 *
 * @param {{Page, Editor}} options - Page and Editor object
 */
async function removeAllPostTypes({page, editor}) {
  const editorSettings = await openComponentPanel({page, editor}, 'Post Types');
  const buttons = await editorSettings.getByRole('button', {name: 'Remove Post Type'}).all();
  for (const button of buttons) {
    await button.click();
  }
}

/**
 * Insert new block into page using the block inserter
 *
 * @param {{Page}} page
 * @param {string} blockName - The name of the block.
 * @param {string} namespace - The namespace to search if it is needed.
 * @return {Promise<void>}   - Playwright Locator
 */
const searchAndInsertBlock = async ({page}, blockName, namespace = '') => {
  await page.getByRole('button', {name: 'Toggle block inserter'}).click();
  await page.getByLabel('Search for blocks and patterns').click();
  await page.keyboard.type(blockName);

  if (namespace !== '') {
    return await page.locator(`button.editor-block-list-item-${namespace.toLowerCase()}[role="option"]`).click();
  }

  return await page.getByRole('option', {name: blockName}).click();
};

export {
  openComponentPanel,
  addCategory,
  addTag,
  addPostType,
  removeAllPostTypes,
  searchAndInsertBlock,
};
