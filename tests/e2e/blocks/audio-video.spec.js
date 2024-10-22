import {test, expect} from '../tools/lib/test-utils.js';
import {publishPostAndVisit, createPostWithFeaturedImage} from '../tools/lib/post.js';
import {searchAndInsertBlock} from '../tools/lib/editor.js';

const YOUTUBE_TEST = 'https://www.youtube.com/watch?v=-CwkccAgKrs';
const VIMEO_TEST = 'https://vimeo.com/120680405';
const MP4_TEST = 'https://www.greenpeace.org/static/planet4-assets/tests/edge_of_the_wrold.mp4';

const SOUNDCLOUD_TEST = 'https://soundcloud.com/greenpeaceuk-1/04-and-we-will-defend-requiem';
const MP3_TEST = 'https://www.greenpeace.org/static/planet4-assets/tests/wochenserie_greenpeace.mp3';

/**
 * Add a Video or Audio block to a page with a specific link.
 *
 * @param {{Page}} page      - The page object for interacting with the browser.
 * @param {string} mediaType - The type of media added (Audio or Video).
 * @param {string} mediaLink - The media file link (can be YouTube, Vimeo, mp4, mp3, SoundCloud).
 */
const addVideoOrAudioBlock = async ({page}, mediaType, mediaLink) => {
  await searchAndInsertBlock({page}, mediaType, mediaType);

  // Make sure the block has been added.
  const insertUrl = await page.getByRole('button', {name: 'Insert from URL'});
  await expect(insertUrl).toBeVisible();

  // We should close the sidebar before editing the block.
  const closeSidebar = await page.getByRole('button', {name: 'Close block inserter'});
  if (await closeSidebar.isVisible()) {
    await closeSidebar.click();
    await expect(closeSidebar).toBeHidden();
  }

  // Add the media URL.
  await insertUrl.click();
  await page.getByPlaceholder('Paste or type URL').fill(mediaLink);
  await page.keyboard.press('Enter');
};

test.useAdminLoggedIn();

test('check the Audio and Video blocks', async ({page, admin, editor}) => {
  // Create a post for the test.
  await createPostWithFeaturedImage({page, admin, editor}, {title: 'Test Audio and Video blocks'});

  // Add Video blocks with the various examples.
  await addVideoOrAudioBlock({page}, 'video', YOUTUBE_TEST);
  await addVideoOrAudioBlock({page}, 'video', VIMEO_TEST);
  await addVideoOrAudioBlock({page}, 'video', MP4_TEST);

  // Add Audio blocks with the various examples.
  await addVideoOrAudioBlock({page}, 'audio', SOUNDCLOUD_TEST);
  await addVideoOrAudioBlock({page}, 'audio', MP3_TEST);

  // Publish page.
  await publishPostAndVisit({page, editor});

  // Check on the frontend that the blocks are present.
  await expect(page.locator('.wp-block-embed-youtube')).toBeVisible();
  await expect(page.locator('.wp-block-embed-vimeo')).toBeVisible();
  await expect(page.locator('.wp-block-video > video')).toHaveAttribute('src', MP4_TEST);
  await expect(page.locator('.wp-block-embed-soundcloud')).toBeVisible();
  await expect(page.locator('.wp-block-audio > audio')).toHaveAttribute('src', MP3_TEST);
});
