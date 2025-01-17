import {test, expect} from './tools/lib/test-utils.js';
import {updatePost} from './tools/lib/post.js';

test.useAdminLoggedIn();

test('Test Related Posts block', async ({page, requestUtils}) => {
  const newPost = await requestUtils.rest({
    path: '/wp/v2/posts',
    method: 'POST',
    data: {
      title: 'Test post for Related Posts',
      content: '<!-- wp:paragraph --><p>This is a test post</p><!-- /wp:paragraph -->',
      status: 'publish',
      featured_media: 357,
      categories: [2],
    },
  });
  const editUrl = `./wp-admin/post.php?post=${newPost.id}&action=edit`;
  const postUrl = newPost.link;

  // Related posts enabled
  await page.goto(editUrl);
  await page.locator('.edit-post-layout__metaboxes').getByRole('combobox', {name: 'Include Articles in Post'}).selectOption('Yes');
  await page.waitForTimeout(1000); // letting metabox post query finish
  await updatePost({page});

  await page.goto(postUrl);
  const relatedSection = page.locator('.p4-query-loop');
  await relatedSection.scrollIntoViewIfNeeded();
  relatedSection.locator('.wp-block-post-template');
  await expect(relatedSection.locator('.wp-block-post-template')).not.toHaveCount(0);

  // Related posts disabled
  await page.goto(editUrl);
  await page.locator('.edit-post-layout__metaboxes').getByRole('combobox', {name: 'Include Articles in Post'}).selectOption('No');
  await updatePost({page});
  await page.waitForTimeout(1000); // letting metabox post query finish

  await page.goto(postUrl);
  await expect(page.locator('.p4-query-loop')).toHaveCount(0);
});
