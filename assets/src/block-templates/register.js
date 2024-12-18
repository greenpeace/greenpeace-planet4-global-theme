import edit from './edit';
import save from './save';
import templateList from './template-list';

const {registerBlockType} = wp.blocks;
const {getCurrentPostType} = wp.data.select('core/editor');

const setSupport = metadata => {
  if (!metadata.supports) {
    metadata.supports = {};
  }

  // block templates don't appear in html, they're just an editing structure
  metadata.supports.customClassName = false;
  // block templates don't appear in blocks list/block inserter
  metadata.supports.inserter = false;

  return metadata;
};

export const registerBlockTemplates = blockTemplates => {
  const templates = blockTemplates || templateList;
  const postType = getCurrentPostType();

  templates.forEach(blockTemplate => {
    // eslint-disable-next-line prefer-const
    let {metadata, template, templateLock = false} = blockTemplate;

    if (metadata.postTypes && !metadata.postTypes.includes(postType)) {
      return null;
    }

    metadata = setSupport(metadata);

    registerBlockType(metadata, {
      edit: edit(template, templateLock),
      save,
    });
  });
};
