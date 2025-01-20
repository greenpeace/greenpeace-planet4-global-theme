import {searchAndInsertBlock} from './editor.js';
import {expect} from './test-utils.js';

const PAGE_NAMES = ['Vestibulum leo libero', 'Consectetur adipiscing elit', 'Vestibulum placerat'];

async function addCoversBlock(page) {
  // Add Covers block.
  await searchAndInsertBlock({page}, 'Covers');

  const settings = await page.getByRole('region', {name: 'Editor settings'});
  const suggestions = settings
    .getByRole('listbox')
    .and(settings.locator('[id*="suggestions"]'));

  // Fill in the Posts.
  const postsInput = await settings.getByLabel('Select pages');
  await postsInput.scrollIntoViewIfNeeded();

  await postsInput.fill(PAGE_NAMES[0]);
  await suggestions.getByRole('option').first().click();

  await postsInput.fill(PAGE_NAMES[1]);
  await suggestions.getByRole('option').first().click();

  await postsInput.fill(PAGE_NAMES[2]);
  await suggestions.getByRole('option').first().click();

  await page.getByLabel('Button Text').fill('Read more');
}

async function checkCoversBlock(page) {
  await expect(page.locator('.take-action-covers-block')).toBeVisible();
  const frontendCovers = await page.locator('.cover-card').all();
  for (const [, cover] of frontendCovers.entries()) {
    await expect(cover.locator('a > img')).toBeVisible();
    const coverName = await cover.locator('a.cover-card-heading').innerText();
    expect(PAGE_NAMES.includes(coverName)).toBe(true);
    await expect(cover.locator('.cover-card-tag')).toBeVisible();
    await expect(cover.locator('.cover-card-excerpt')).toBeVisible();
    await expect(cover.locator('a.cover-card-btn')).toHaveText('Get Involved');
  }

  // Check Load more button
  const loadMoreBtn = await page.$('button.load-more-btn');
  if (loadMoreBtn) {
    await expect(page.locator('button.load-more-btn')).toHaveText('Read more');
  }
}

export {addCoversBlock, checkCoversBlock};
