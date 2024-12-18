/**
 * Return an editable image block
 *
 * - display image credits in caption during edition
 *
 * @param {Object} BlockEdit
 * @return {Object} interface to edit images on the Editor
 */
export const ImageBlockEdit = BlockEdit => props => {
  if ('core/image' !== props.name) {
    return <BlockEdit {...props} />;
  }

  const {attributes, clientId} = props;
  const {id, className = ''} = attributes;

  // Get image data
  const image = wp.data.useSelect(select => id ? select('core').getMedia(id) : null);
  const credits = image?.meta?._credit_text;
  const captionText = image?.caption?.raw;
  // Compile data for insertion
  let image_credits = null;
  if (credits && credits.length > 0 && (!captionText || !captionText.includes(credits))) {
    image_credits = credits.includes('©') ? credits : `© ${credits}`;
  }

  const block_id = clientId ? `block-${clientId}` : null;

  // Update width and height when sized rounded styles are selected
  if (className.includes('is-style-rounded-')) {
    const classes = className.split(' ');
    const size = classes.find(c => c.includes('is-style-rounded-')).replace('is-style-rounded-', '') || 180;
    attributes.width = parseInt(size);
    attributes.height = parseInt(size);
  }

  // Force to use square images when the class `square-*` is added
  if (className.includes('square-')) {
    const size = className.slice(className.search('square-') + 'square-'.length).split(' ')[0] || 180;
    attributes.width = parseInt(size);
    attributes.height = parseInt(size);
  }

  return (
    <>
      <BlockEdit {...props} />
      {block_id && image_credits && (
        captionText ?
          <style dangerouslySetInnerHTML={{
            __html: `#${block_id} figcaption::after { content: " ${image_credits}"; }`,
          }}>
          </style> :
          <figcaption style={{marginTop: -24}}>{image_credits}</figcaption>
      )}
    </>
  );
};
