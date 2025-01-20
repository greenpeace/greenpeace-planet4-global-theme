import {test, expect} from './tools/lib/test-utils.js';
import {
  toggleRestAPI,
  createForm,
  fillAndSubmitForm,
  checkEntry,
  changeConfirmationType,
} from './tools/lib/gravity-forms.js';

const CONFIRMATION_MESSAGE = 'This is a dummy confirmation message for testing purposes.';
const TEST_REDIRECT = 'https://www.greenpeace.org/international/';

test.useAdminLoggedIn();

test.describe('Gravity Forms tests', () => {
  test.describe.configure({mode: 'serial'});
  const testId = Math.floor(Math.random() * 10000); //NOSONAR
  let createdForm;
  let newPost;

  test.beforeAll(async ({browser, requestUtils}) => {
    const page = await browser.newPage();
    // Enable Gravity Forms rest API.
    await toggleRestAPI({page}, true);

    // Create a new form.
    createdForm = await createForm({page}, {
      title: `Gravity Forms test form - ${testId}`,
    });

    // Create a new post with the new form.
    newPost = await requestUtils.rest({
      path: '/wp/v2/posts',
      method: 'POST',
      data: {
        title: `Gravity Forms test post - ${testId}`,
        content: `<!-- wp:gravityforms/form {"formId":"${createdForm.id}"} /-->`,
        status: 'publish',
        featured_media: 357,
      },
    });

    await page.close();
  });

  test.afterAll(async ({browser}) => {
    const page = await browser.newPage();
    // Delete the form.
    await page.request.delete(`./wp-json/gf/v2/forms/${createdForm.id}?force=1`);

    // Disable Gravity Forms rest API.
    await toggleRestAPI({page}, false);
  });

  test('check the confirmation message, text type', async ({page}) => {
    // Make sure the form uses the Text confirmation type.
    await changeConfirmationType({page}, createdForm.id, 'Text');

    // Update the form's default confirmation text.
    await page.frameLocator('#_gform_setting_message_ifr').locator('#tinymce').fill(CONFIRMATION_MESSAGE);
    await page.getByRole('button', {name: 'Save Confirmation'}).click();
    await expect(page.locator('.gforms_note_success')).toBeVisible();

    // Go to the post which has the form.
    await page.goto(newPost.link);

    // Fill and submit the form.
    await fillAndSubmitForm({page}, createdForm.id);

    // Check the confirmation message text.
    const confirmationMessage = page.locator('.gform_confirmation_message');
    await expect(confirmationMessage).toBeVisible();
    await expect(confirmationMessage).toContainText(CONFIRMATION_MESSAGE);

    // Check that the entry has been registered as expected.
    await checkEntry({page}, createdForm.id);
  });

  test('check the confirmation message, redirect type', async ({page}) => {
    // Make sure the form uses the Redirect confirmation type.
    await changeConfirmationType({page}, createdForm.id, 'Redirect');

    // Set up the redirection.
    await page.getByLabel('Redirect URL(Required)').fill(TEST_REDIRECT);
    await page.getByRole('button', {name: 'Save Confirmation'}).click();
    await expect(page.locator('.gforms_note_success')).toBeVisible();

    // Go to the post which has the form.
    await page.goto(newPost.link);

    // Fill and submit the form.
    await fillAndSubmitForm({page}, createdForm.id);

    // Make sure the page is redirected as expected.
    await expect(page).toHaveURL(TEST_REDIRECT);

    // Check that the entry has been registered as expected.
    await checkEntry({page}, createdForm.id);
  });

  test('check the confirmation message, page type', async ({page}) => {
    // Make sure the form uses the Page confirmation type.
    await changeConfirmationType({page}, createdForm.id, 'Page');

    // Set up the page redirect.
    const confirmationSettings = page.locator('#gform_setting_page');
    const pagesList = confirmationSettings.locator('.gform-dropdown__list');
    const spinner = confirmationSettings.locator('.gform-dropdown__spinner');
    await confirmationSettings.getByRole('button', {name: 'Select a Page'}).click();
    await expect(pagesList).toBeVisible();
    await confirmationSettings.getByPlaceholder('Search all Pages').click();
    await page.keyboard.type('Home');
    await expect(spinner).toBeVisible();
    await expect(spinner).toBeHidden();
    await pagesList.getByRole('button', {name: 'Home'}).click();
    await expect(pagesList).toBeHidden();
    await page.getByRole('button', {name: 'Save Confirmation'}).click();
    await expect(page.locator('.gforms_note_success')).toBeVisible();

    // Go to the post which has the form.
    await page.goto(newPost.link);

    // Fill and submit the form.
    await fillAndSubmitForm({page}, createdForm.id);

    // Make sure the page is redirected as expected.
    await expect(page).toHaveURL('./');

    // Check that the entry has been registered as expected.
    await checkEntry({page}, createdForm.id);
  });

  test('check the Hubspot feeds', async ({page}) => {
    // Add a Hubspot feed.
    await page.goto(`./wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gravityformshubspot&id=${createdForm.id}`);
    const createFeedLink = page.getByRole('link', {name: 'create one'});
    // If the Huspot add-on isn't activated, the page will be empty so this link won't be there.
    if (await createFeedLink.isVisible()) {
      await createFeedLink.click();
      // Set lead status to "new".
      await page.selectOption('#_hs_customer_hs_lead_status', {label: 'New'});
      // Map contact fields.
      await page.selectOption('#_hs_customer_firstname', {label: 'First name'});
      await page.selectOption('#_hs_customer_lastname', {label: 'Last name'});
      await page.selectOption('#_hs_customer_email', {label: 'Email'});
      // Save Hubspot feed.
      await page.getByRole('button', {name: 'Save Settings'}).click();
      await expect(page.locator('.gforms_note_success')).toBeVisible();

      // Check that the feed is active.
      await page.goto(`./wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gravityformshubspot&id=${createdForm.id}`);
      const hubspotFeed = page.locator('#the-list > tr').first();
      await expect(hubspotFeed.locator('.gform-status-indicator-status')).toContainText('Active');
    }
  });
});
