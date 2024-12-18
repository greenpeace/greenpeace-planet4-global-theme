import {AssignOnlyFlatTermSelector} from './AssignOnlyFlatTermSelector';
import {TermSelector} from './TermSelector';

const customizeTaxonomySelectors = OriginalComponent => props => {
  // For following taxonomies it should not be possible to create new terms on the post edit page
  const isCustomComponent = ['p4-page-type', 'post_tag'].includes(props.slug);

  let component = OriginalComponent;
  if (isCustomComponent) {
    component = props.slug === 'post_tag' ? TermSelector : AssignOnlyFlatTermSelector;
  }

  return wp.element.createElement(
    component,
    props
  );
};

export const replaceTaxonomyTermSelectors = () => {
  wp.hooks.addFilter(
    'editor.PostTaxonomyType',
    'planet4-master-theme',
    customizeTaxonomySelectors
  );
};
