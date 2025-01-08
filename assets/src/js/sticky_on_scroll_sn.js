// Adds class to Secondary Navigation block to make it sticky on scroll
export const makeSecondaryNavigationStickyonScroll = () => {
  setTimeout(() => {
    const stickyElement = document.querySelector('.secondary-navigation-block');
    const container = document.querySelector('.page-content');
    const offset = -500;

    window.addEventListener('scroll', () => {
      const containerRect = container.getBoundingClientRect();
      const stickyRect = stickyElement.getBoundingClientRect();

      if (containerRect.top <= offset && containerRect.bottom > stickyRect.height + offset) {
        stickyElement.classList.add('stuck');
      } else {
        stickyElement.classList.remove('stuck');
      }
    });

  }, 1000);
};
