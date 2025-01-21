function toggleCommentSubmit() {
  const checkbox = document.getElementById('gdpr-comments-checkbox');
  const submit = document.querySelector('#commentform button[type="submit"]');

  if (checkbox.checked) {
    submit.removeAttribute('disabled');
  } else {
    submit.setAttribute('disabled', '');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('gdpr-comments-checkbox')
    .addEventListener('change', () => toggleCommentSubmit());
  toggleCommentSubmit();
});
