export const setupParallax = () => {
  const parallaxImages = document.querySelectorAll('.is-style-parallax img');

  if (!parallaxImages.length) {
    return;
  }

  let mobileSpeedAllSetup = false;
  parallaxImages.forEach((image, index) => {
    image.setAttribute('data-rellax-xs-speed', -1); // the default value is -2, we want to keep it for bigger screens.
    if (index === parallaxImages.length - 1) {
      mobileSpeedAllSetup = true;
    }
  });

  if (mobileSpeedAllSetup) {
    return new Rellax('.is-style-parallax img', {center: true}); // eslint-disable-line no-undef
  }
};
