import {Button, ButtonGroup, PanelBody} from '@wordpress/components';
import {InspectorControls} from '@wordpress/block-editor';

const {addFilter} = wp.hooks;
const {__} = wp.i18n;

// Enable spacing control on the following blocks
const targetBlocks = [
  'core/image',
];

const captionStyleOptions = [
  {
    label: __('Medium'),
    value: 'medium',
  },
];

const captionAlignmentOptions = [
  {
    label: __('Left'),
    value: 'left',
  },
  {
    label: __('Center'),
    value: 'center',
  },
  {
    label: __('Right'),
    value: 'right',
  },
];

export const setupImageBlockExtension = () => {
  addExtraAttributes();
  addExtraControls();
};

const addExtraAttributes = () => {
  const addCaptionStyleAttributes = (settings, name) => {
  // Do nothing if it's another block than our defined ones.
    if (!targetBlocks.includes(name)) {
      return settings;
    }

    settings.attributes = {
      ...settings.attributes,
      captionStyle: {
        type: 'string',
        default: captionStyleOptions[0].value,
      },
      captionAlignment: {
        type: 'string',
        default: captionAlignmentOptions[1].value,
      },
    };

    return settings;
  };

  addFilter('blocks.registerBlockType', 'planet4-blocks/overrides/image', addCaptionStyleAttributes);
};

const addExtraControls = () => {
  const {createHigherOrderComponent} = wp.compose;

  const withCaptionStyle = createHigherOrderComponent(BlockEdit => props => {
    // Do nothing if it's another block than our defined ones.
    if (!targetBlocks.includes(props.name)) {
      return (
        <BlockEdit {...props} />
      );
    }

    const {captionAlignment} = props.attributes;

    const updateCaptionAlignment = value => {
      const className = props.attributes.className || '';
      const classNameBase = className.split('caption-alignment-')[0];
      const newClassName = classNameBase + ` caption-alignment-${value}`;
      props.setAttributes({className: newClassName});
    };

    return (
      <>
        <BlockEdit {...props} />
        <InspectorControls>
          <PanelBody
            title={__('Planet4 Image Options')}
            initialOpen={true}
          >
            {/* eslint-disable-next-line jsx-a11y/label-has-for, jsx-a11y/label-has-associated-control */}
            <label>Caption alignment</label>
            <ButtonGroup>
              {
                captionAlignmentOptions.map((option, key) => {
                  return <Button
                    key={key}
                    value={option.value}
                    onClick={() => {
                      props.setAttributes({
                        captionAlignment: option.value,
                      });
                      updateCaptionAlignment(option.value);
                    }}
                    variant={captionAlignment === option.value ? 'primary' : 'secondary'}>
                    { option.label }
                  </Button>;
                })
              }
            </ButtonGroup>
          </PanelBody>
        </InspectorControls>
      </>
    );
  }, 'withCaptionStyle');

  addFilter('editor.BlockEdit', 'planet4-blocks/overrides/image-controls', withCaptionStyle);
};
