export const addButtonLinkPasteWarning = () => document.addEventListener('DOMContentLoaded', () => {
  document.onpaste = event => {
    const {target} = event;
    if (!target.matches('.wp-block-button__link, .wp-block-button__link *')) {
      return;
    }

    const aTags = target.querySelectorAll('a');
    if (aTags.length) {
      const {__} = wp.i18n;
      // eslint-disable-next-line no-alert
      alert(__(
        'You are pasting a link into the button text. Please ensure your clipboard only has text in it. Alternatively you can press control/command + SHIFT + V to paste only the text in your clipboard.',
        'planet4-blocks-backend'
      ));
    }
  };
});
