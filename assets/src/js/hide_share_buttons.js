// This bit of code is to hide the "Share Buttons" section
// if editors select "Page" or "Redirect" as confirmation message.

addEventListener('DOMContentLoaded', () => {
  const confirmationTypeCheckboxes = [...document.querySelectorAll('input[name="_gform_setting_type"]')];
  const textTypeCheckbox = confirmationTypeCheckboxes.find(input => input.value === 'message');
  const shareButtonsSettings = document.querySelector('#gform-settings-section-share-buttons');

  const onChange = checkbox => {
    if (checkbox.value === 'message' && checkbox.checked) {
      shareButtonsSettings.classList.remove('hidden');
    } else {
      shareButtonsSettings.classList.add('hidden');
    }
  };

  confirmationTypeCheckboxes.forEach(input => input.addEventListener('change', event => onChange(event.currentTarget)));

  onChange(textTypeCheckbox);
});
