import {SecondaryNavigationEditor} from './SecondaryNavigationEditor';
import {example} from './example';

const {__} = wp.i18n;

const BLOCK_NAME = 'planet4-blocks/secondary-navigation';

export const registerSecondaryNavigationBlock = () => {
  const {registerBlockType} = wp.blocks;

  registerBlockType(BLOCK_NAME, {
    title: 'Secondary Navigation Menu',
    description: __('Inserts a secondary navigation menu to the page that leads to different sections of the same page.', 'planet4-blocks-backend'),
    icon: 'menu-alt3',
    category: 'planet4-blocks-beta',
    attributes: {
      levels: {
        type: 'array',
        default: [{heading: 2, link: true}],
      },
      exampleMenuItems: { // Used for the block's preview, which can't extract items from anything.
        type: 'array',
      },
    },
    isExample: {
      type: 'boolean',
      default: false,
    },
    supports: {
      multiple: false, // Use the block just once per post.
      html: false,
    },
    edit: SecondaryNavigationEditor,
    save() {
      return null;
    },
    example,
  });
};
