function parseWidths(srcset) {
  const entries = srcset.split(',').map(entry => entry.trim());
  const widths = entries.map(entry => {
    const width = entry.split(' ')[1];
    return width.replace('w', 'px');
  });
  return widths;
}

function createSizesAttribute(widths) {
  return widths.map(width => {
    return `(max-width: ${parseInt(width)}px) ${width}`;
  }).join(', ');
}

export const SlideBackground = ({slide, decoding}) => {
  const {image_url, image_srcset, focal_points, image_alt} = slide;
  const image_widths = parseWidths(image_srcset);
  const image_sizes = createSizesAttribute(image_widths);

  // Preload the images
  if (image_srcset && image_sizes) {
    const preloadLink = document.createElement('link');
    preloadLink.rel = 'preload';
    preloadLink.as = 'image';
    preloadLink.href = image_url;
    preloadLink.imagesrcset = image_srcset;
    preloadLink.imagesizes = image_sizes.concat(', 100vw');
    document.head.appendChild(preloadLink);
  }

  return (
    <div className="background-holder">
      <img
        src={image_url}
        style={{objectPosition: `${(focal_points?.x || .5) * 100}% ${(focal_points?.y || .5) * 100}%`}}
        srcSet={image_srcset}
        sizes={image_sizes}
        alt={image_alt}
        {...decoding ? {decoding: 'async'} : {}}
      />
    </div>
  );
};
