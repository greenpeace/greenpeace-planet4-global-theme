import {test} from '../tools/lib/test-utils.js';
import {addColumnsBlock, checkColumnsBlock} from '../tools/lib/columns.js';
import {publishPostAndVisit, createPostWithFeaturedImage} from '../tools/lib/post.js';

test.useAdminLoggedIn();

test('Test Columns block with Tasks style', async ({page, admin, editor}) => {
  await createPostWithFeaturedImage({page, admin, editor}, {title: 'Test Columns block', postType: 'page'});

  // Add Columns block.
  await addColumnsBlock(page, 'Tasks');

  // Publish page.
  await publishPostAndVisit({page, editor});

  // Make sure block shows as expected in the frontend.
  await checkColumnsBlock(page, 'Tasks');
});
