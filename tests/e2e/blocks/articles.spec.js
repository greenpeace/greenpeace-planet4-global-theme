import {test, expect} from '../tools/lib/test-utils.js';
import {publishPostAndVisit, createPostWithFeaturedImage} from '../tools/lib/post.js';
import {searchAndInsertBlock} from '../tools/lib/editor';

const TEST_TITLE = 'All Articles';
const TEST_DESCRIPTION = 'All articles in date order';
const TEST_BUTTON_TEXT = 'Load';

test.useAdminLoggedIn();

test('Test Articles block', async ({page, admin, editor}) => {
  await createPostWithFeaturedImage({page, admin, editor}, {title: 'Test Articles', postType: 'page'});

  // Add Articles block.
  await searchAndInsertBlock({page}, 'Articles');

  // Check that the default texts for the title and button are applied.
  const editorTitle = page.getByRole('textbox', {name: 'Enter title'});
  const editorDescription = page.getByRole('textbox', {name: 'Enter description'});
  const editorButton = page.getByRole('textbox', {name: 'Enter text'});
  await expect(editorTitle).toHaveText('Related Articles');
  await expect(editorButton).toHaveText('Load more');

  // Change title, description and button text.
  await editorTitle.fill(TEST_TITLE);
  await editorDescription.fill(TEST_DESCRIPTION);
  await editorButton.fill(TEST_BUTTON_TEXT);

  // Change amount of articles from 3 to 4.
  await page.getByLabel('Articles count').fill('4');

  // Publish page.
  await publishPostAndVisit({page, editor});

  // Test that the block is displayed as expected in the frontend.
  const frontendTitle = await page.innerHTML('.page-section-header');
  const frontendDescription = await page.innerHTML('.page-section-description');
  const frontendButton = await page.innerHTML('.article-load-more');
  expect(frontendTitle).toBe(TEST_TITLE);
  expect(frontendDescription).toBe(TEST_DESCRIPTION);
  expect(frontendButton).toBe(TEST_BUTTON_TEXT);
  await expect(page.locator('.article-list-item')).toHaveCount(4);
});
