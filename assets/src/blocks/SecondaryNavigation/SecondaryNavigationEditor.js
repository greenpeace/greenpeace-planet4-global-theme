import {getHeadingsFromBlocks} from '../TableOfContents/getHeadingsFromBlocks';

const {useSelect} = wp.data;
const {InspectorControls} = wp.blockEditor;
const {PanelBody} = wp.components;
const {__} = wp.i18n;

const renderEdit = () => {
  return (
    <InspectorControls>
      <PanelBody title={__('Learn more about this block', 'planet4-blocks-backend')} initialOpen={false}>
        <p className="components-base-control__help">
          <a target="_blank" href="https://planet4.greenpeace.org/content/blocks/table-of-contents/" rel="noreferrer">
            P4 Handbook - P4 Secondary Navigation Menu
          </a>
          {' '} &#128203;
        </p>
      </PanelBody>
    </InspectorControls>
  );
};

const renderView = attributes => {
  const {
    levels,
    isExample,
    exampleMenuItems,
  } = attributes;

  const blocks = useSelect(select => select('core/block-editor').getBlocks(), []);

  const flatHeadings = getHeadingsFromBlocks(blocks, levels);

  const menuItems = isExample ? exampleMenuItems : flatHeadings;

  return (
    <section className="block secondary-navigation-block">
      {menuItems.length > 0 ?
        <div className="secondary-navigation-menu">
          <ul className="secondary-navigation-item">
            {menuItems.map(({anchor, content}) => (
              <li key={anchor}>
                <a
                  className="secondary-navigation-link"
                  href={`#${anchor}`}
                >
                  {content}
                </a>
              </li>
            ))}
          </ul>
        </div> :
        <div className="EmptyMessage">
          {__('There are not any pre-established headings that this block can display in the form of a secondary navigation menu. Please add headings to your page.', 'planet4-blocks-backend')}
        </div>
      }
    </section>
  );
};

export const SecondaryNavigationEditor = ({attributes, isSelected}) => (
  <>
    {isSelected && renderEdit()}
    {renderView(attributes)}
  </>
);
