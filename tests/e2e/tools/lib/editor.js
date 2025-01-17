import {Locator} from '@playwright/test';

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

export {openComponentPanel, searchAndInsertBlock};
