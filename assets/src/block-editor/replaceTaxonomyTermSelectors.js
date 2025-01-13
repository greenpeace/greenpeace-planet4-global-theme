import {AssignOnlyFlatTermSelector} from './AssignOnlyFlatTermSelector';
import {TermSelector} from './TermSelector';
const {Component} = wp.element;

/**
 * Replace some native Components from taxonomy selection with our custom components.
 *
 * @param {Component} OriginalComponent The original WordPress component.
 *
 * @return {Component} The component needed based on the taxonomy.
 */
const customizeTaxonomySelectors = OriginalComponent => props => {
  // For post types and tags, it should not be possible to create new terms on the post edit page
  let component = OriginalComponent;
  if (['p4-page-type', 'post_tag'].includes(props.slug)) {
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
