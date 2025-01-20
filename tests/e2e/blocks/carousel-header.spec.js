import {test, expect} from '../tools/lib/test-utils.js';
import {publishPostAndVisit, createPostWithFeaturedImage} from '../tools/lib/post.js';
import {searchAndInsertBlock} from '../tools/lib/editor.js';

test.useAdminLoggedIn();

const slides = [
  {image: 'NATURE-GP0STOE2U', cta: {url: 'https://google.com'}},
  {image: 'OCEANS-GP0STOM6C', cta: {url: 'https://yahoo.com', newTab: true}},
];

/**
 * Add new Carousel slide
 *
 * @param {*}              slide   slide data
 * @param {*}              param   slide extra information
 * @param {{Page, Editor}} options - Page and Editor object
 */
const addSlide = async (slide, {index, addNext}, {page, editor}) => {
  await page.getByRole('textbox', {name: 'Enter title'}).nth(index)
    .fill(`My test Header ${index + 1}`);

  await page.getByRole('textbox', {name: 'Enter description'}).nth(index)
    .fill(`Testing carousel description ${index + 1}`);

  await page.getByRole('textbox', {name: 'Enter CTA text'}).nth(index)
    .fill(`Read more ${index + 1}`);

  // Check if sidebar is not visible
  await editor.openDocumentSettingsSidebar();

  await page.locator('[data-type="toggle"]').nth(index).click();
  await page.getByLabel('Url for link').fill(slide.cta.url);
  if (slide.cta.newTab) {
    await page.getByLabel('Open in a new tab').check();
  }

  await page.getByRole('button', {name: 'Edit'}).nth(index).click();
  await page.getByRole('button', {name: 'Add image'}).click();
  await page.getByRole('tab', {name: 'Media Library'}).click();
  await page.getByRole('checkbox', {name: slide.image}).click();
  await page.getByRole('button', {name: 'Select', exact: true}).click();

  // Next Slide
  if (addNext) {
    await page.getByRole('button', {name: 'Edit'}).click();
    await page.getByRole('button', {name: 'Add slide'}).click();
  }
};

test('Create and check carousel header block', async ({page, admin, editor}) => {
  await createPostWithFeaturedImage({page, admin, editor}, {title: 'Test Carousel', postType: 'page'});

  // Add block
  await searchAndInsertBlock({page}, 'Carousel Header');

  for (const slide in slides) {
    await addSlide(
      slides[slide],
      {index: parseInt(slide), addNext: !!slides[parseInt(slide) + 1]},
      {page, editor}
    );
  }

  // Publish Page
  await publishPostAndVisit({page, editor});

  // Assertions
  const h2Title1 = await page.innerHTML('.carousel-captions-wrapper h2');
  const paragraphDescription1 = await page.innerHTML('.carousel-captions-wrapper p');
  const ctaButton1 = page.locator('.action-button a').first();

  expect(h2Title1).toBe('My test Header 1');
  expect(paragraphDescription1).toBe('Testing carousel description 1');
  await expect(ctaButton1).toBeVisible();

  await page.locator('button.carousel-control-next').click();

  const h2Title2 = await page.innerHTML('.carousel-captions-wrapper h2>>nth=1');
  const paragraphDescription2 = await page.innerHTML('.carousel-captions-wrapper p>>nth=1');
  const ctaButton2 = page.locator('.action-button a').nth(1);

  expect(h2Title2).toBe('My test Header 2');
  expect(paragraphDescription2).toBe('Testing carousel description 2');
  await expect(ctaButton2).toBeVisible();
});
