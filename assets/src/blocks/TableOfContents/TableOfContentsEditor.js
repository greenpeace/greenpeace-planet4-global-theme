import {TableOfContentsLevel} from './TableOfContentsLevel';
import {TableOfContentsItems} from './TableOfContentsItems';
import {getTableOfContentsStyle} from './getTableOfContentsStyle';
import {makeHierarchical} from './makeHierarchical';
import {getHeadingsFromBlocks} from './getHeadingsFromBlocks';
import {deepClone} from '../../functions/deepClone';

const TRANSLATION_ID = 'planet4-blocks-backend';

const BLOCK_TITLE = 'table-of-contents';

const BLOCK_NAME = {
  TABLE_OF_CONTENTS: 'planet4-blocks/submenu',
  EDITOR: 'core/block-editor',
  LIST: 'core/list',
  LIST_ITEM: 'core/list-item',
  HEADING: 'core/heading',
  GROUP: 'core/group',
};

const CLASS_NAME = {
  HELP: 'components-base-control__help',
  LIST: 'list-style',
};

const {useSelect, select, dispatch} = wp.data;
const {InspectorControls, RichText, BlockControls} = wp.blockEditor;
const {Button, PanelBody, ToolbarItem} = wp.components;
const {createBlock} = wp.blocks;
const {__} = wp.i18n;

/**
 * Renders the edit view of the Table of Contents block with controls for managing levels.
 *
 * @param {Object} attributes - The block attributes.
 * @param {Function} setAttributes - Function to update block attributes.
 * @return {JSX.Element} The rendered edit view.
 */
const renderEdit = (attributes, setAttributes) => {
  /**
   * Adds a new level to the Table of Contents.
   */
  function addLevel() {
    const [previousLastLevel] = attributes.levels.slice(-1);
    const newLevel = previousLastLevel.heading + 1;
    setAttributes({levels: attributes.levels.concat({heading: newLevel, link: false, style: 'none'})});
  }

  /**
   * Updates the heading level for a specific item.
   *
   * @param {number} index - Index of the level to update.
   * @param {string} value - New heading value.
   */
  function onHeadingChange(index, value) {
    const levels = deepClone(attributes.levels);
    levels[index].heading = Number(value);
    setAttributes({levels});
  }

  /**
   * Updates the link attribute for a specific item.
   *
   * @param {number} index - Index of the level to update.
   * @param {string} value - New link value.
   */
  function onLinkChange(index, value) {
    const levels = deepClone(attributes.levels);
    levels[index].link = value;
    setAttributes({levels});
  }

  /**
   * Updates the style attribute for a specific item.
   *
   * @param {number} index - Index of the level to update.
   * @param {string} value - New style value, can be "none", "bullet", or "number".
   */
  function onStyleChange(index, value) {
    const levels = deepClone(attributes.levels);
    levels[index].style = value;
    setAttributes({levels});
  }

  /**
   * Removes the last level from the Table of Contents.
   */
  function removeLevel() {
    setAttributes({levels: attributes.levels.slice(0, -1)});
  }

  /**
   * Gets the minimum heading level for a specific index.
   *
   * @param {Object} attr - Block attributes.
   * @param {number} index - Index of the level.
   * @return {number|null} Minimum heading value or null for the first index.
   */
  function getMinLevel(attr, index) {
    if (index === 0) {
      return null;
    }
    return attr.levels[index - 1].heading;
  }

  return (
    <InspectorControls>
      <PanelBody title={__('Settings', TRANSLATION_ID)}>
        <p className={CLASS_NAME.HELP}>
          {__('Choose the headings to be displayed in the table of contents.', TRANSLATION_ID)}
        </p>
        {attributes.levels.map((level, i) => (
          <TableOfContentsLevel
            {...level}
            onHeadingChange={onHeadingChange}
            onLinkChange={onLinkChange}
            onStyleChange={onStyleChange}
            index={i}
            key={i}
            minLevel={getMinLevel(attributes, i)}
          />
        ))}
        <Button
          isPrimary
          onClick={addLevel}
          disabled={attributes.levels.length >= 3 || attributes.levels.slice(-1)[0].heading === 0}
          style={{marginRight: 5}}
        >
          {__('Add level', TRANSLATION_ID)}
        </Button>
        <Button
          variant="secondary"
          onClick={removeLevel}
          disabled={attributes.levels.length <= 1}
        >
          {__('Remove level', TRANSLATION_ID)}
        </Button>
      </PanelBody>
      <PanelBody title={__('Learn more about this block', TRANSLATION_ID)} initialOpen={false}>
        <p className={CLASS_NAME.HELP}>
          <a target="_blank" href={`https://planet4.greenpeace.org/content/blocks/${BLOCK_TITLE}/`} rel="noreferrer">
            P4 Handbook P4 Table of Contents
          </a>
          {' '} &#128203;
        </p>
      </PanelBody>
    </InspectorControls>
  );
};

/**
 * Renders the view of the Table of Contents block.
 *
 * @param {Object} attributes - The block attributes.
 * @param {Function} setAttributes - Function to update block attributes.
 * @param {string} className - The CSS class for the block.
 * @return {JSX.Element} The rendered view.
 */
const renderView = (attributes, setAttributes, className) => {
  const {
    title,
    levels,
    submenu_style,
    isExample,
    exampleMenuItems,
  } = attributes;

  const blocks = useSelect(wpSelect => wpSelect(BLOCK_NAME.EDITOR).getBlocks(), null);
  const flatHeadings = getHeadingsFromBlocks(blocks, levels);
  const menuItems = isExample ? exampleMenuItems : makeHierarchical(flatHeadings);
  const style = getTableOfContentsStyle(className, submenu_style);
  return (
    <>
      <BlockControls>
        <ToolbarItem
          as={Button}
          onClick={() => convertIntoListBlock(menuItems)}
        >
          {__('Convert to static list', TRANSLATION_ID)}
        </ToolbarItem>
      </BlockControls>
      <section className={`block ${BLOCK_TITLE}-block ${BLOCK_TITLE}-${style} ${className ?? ''}`}>
        <RichText
          tagName="h2"
          placeholder={__('Enter title', TRANSLATION_ID)}
          value={title}
          onChange={titl => setAttributes({title: titl})}
          withoutInteractiveFormatting
          allowedFormats={[]}
        />
        {menuItems.length > 0 ? (
          <TableOfContentsItems menuItems={menuItems} />
        ) : (
          <div className="EmptyMessage">
            {__('There are not any pre-established headings that this block can display in the form of a table of content. Please add headings to your page or choose another heading size.', TRANSLATION_ID)}
          </div>
        )}
      </section>
    </>
  );
};

/**
 * Creates a list block with list item blocks based on the given items.
 *
 * @param {Array} items - The items to create list blocks from. Each item should have `text`, `shouldLink`, and `children`.
 * @return {Object} The core/list block with the nested structure.
 */
const createListBlocks = items => {
  const innerBlocks = [];

  items.forEach(item => {
    let content = item.text;

    if (item.shouldLink) {
      content = `<a class="icon-link table-of-contents-link" href="#${item.anchor}">${content}</a>`;
    }

    const newInnerBlock = createBlock(BLOCK_NAME.LIST_ITEM, {className: `${CLASS_NAME.LIST} ${CLASS_NAME.LIST}-${item.style}`, content});

    if (item.children && item.children.length > 0) {
      const childListBlock = createListBlocks(item.children);
      newInnerBlock.innerBlocks = [childListBlock];
    }

    innerBlocks.push(newInnerBlock);
  });

  return createBlock(BLOCK_NAME.LIST, {}, innerBlocks);
};

/**
 * Converts the given menu items into a static list block and replaces the current block.
 *
 * @param {Array} menuItems - The menu items to convert into a list block.
 */
const convertIntoListBlock = menuItems => {
  if (!menuItems) {
    return;
  }

  const blockList = select(BLOCK_NAME.EDITOR).getBlocks();
  const blockIndex = blockList.findIndex(block => block.name === BLOCK_NAME.TABLE_OF_CONTENTS);

  if (blockIndex === -1) {
    return;
  }

  const blockAttrs = blockList[blockIndex].attributes;

  const headingBlock = createBlock(BLOCK_NAME.HEADING, {content: blockAttrs.title});
  const listBlocks = createListBlocks(menuItems);
  const groupBlock = createBlock(BLOCK_NAME.GROUP, {className: `${BLOCK_TITLE} ${blockAttrs.className}`}, [headingBlock, listBlocks]);

  dispatch(BLOCK_NAME.EDITOR).insertBlock(groupBlock, blockIndex);
  dispatch(BLOCK_NAME.EDITOR).removeBlock(blockList[blockIndex].clientId);
};

/**
 * Renders the Table of Contents block editor.
 *
 * @param {Object} props - The component props.
 * @param {Object} props.attributes - The block attributes.
 * @param {Function} props.setAttributes - Function to update block attributes.
 * @param {boolean} props.isSelected - Indicates if the block is selected.
 * @param {string} props.className - The CSS class for the block.
 * @return {JSX.Element} The Table of Contents editor component.
 */
export const TableOfContentsEditor = ({attributes, setAttributes, isSelected, className}) => (
  <>
    {isSelected && renderEdit(attributes, setAttributes)}
    {renderView(attributes, setAttributes, className)}
  </>
);
