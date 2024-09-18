
import metadata from './block.json';
import deprecated from './deprecated';
import edit from './edit';
import save from './save';

const {registerBlockType} = wp.blocks;

registerBlockType(metadata, {
  title: '50 Years GuestBook',
  icon: 'admin-site-alt2',
  category: 'planet4-blocks',
  supports: {
    html: false, // Disable "Edit as HTML" block option.
    multiple: false, // Use the block just once per post.
  },
  edit,
  save,
  deprecated,
});
