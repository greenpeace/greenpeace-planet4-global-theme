// This component is based on the one used by WordPress for categories (without the hierarchical aspect, since we don't need it):
// https://github.com/WordPress/gutenberg/blob/trunk/packages/editor/src/components/post-taxonomies/hierarchical-term-selector.js

/**
 * WordPress dependencies
 */
import {useMemo, useState} from '@wordpress/element';
import {
  Button,
  CheckboxControl,
  TextControl,
  withFilters,
} from '@wordpress/components';
import {useDispatch, useSelect} from '@wordpress/data';
import {useDebounce} from '@wordpress/compose';
import {store as coreStore} from '@wordpress/core-data';
import {decodeEntities} from '@wordpress/html-entities';

const {__, _n, _x, sprintf} = wp.i18n;
const {speak} = wp.a11y;

/**
 * Module Constants
 */
const DEFAULT_QUERY = {
  per_page: -1,
  orderby: 'name',
  order: 'asc',
  _fields: 'id,name',
  context: 'view',
};

const MIN_TERMS_COUNT_FOR_FILTER = 8;

const EMPTY_ARRAY = [];

/**
 * Sort Terms by Selected.
 *
 * @param {Object[]} termsTree Array of terms in tree format.
 * @param {number[]} terms     Selected terms.
 *
 * @return {Object[]} Sorted array of terms.
 */
export function sortBySelected(termsTree, terms) {
  const treeHasSelection = termTree => {
    return terms.indexOf(termTree.id) !== -1;
  };

  const termIsSelected = (termA, termB) => {
    const termASelected = treeHasSelection(termA);
    const termBSelected = treeHasSelection(termB);

    if (termASelected === termBSelected) {
      return 0;
    }

    if (termASelected && !termBSelected) {
      return -1;
    }

    if (!termASelected && termBSelected) {
      return 1;
    }

    return 0;
  };
  const newTermTree = [...termsTree];
  newTermTree.sort(termIsSelected);
  return newTermTree;
}

/**
 * Find term by name.
 *
 * @param {Object[]} terms Array of Terms.
 * @param {string}   name  Term name.
 *
 * @return {Object} Term object.
 */
export function findTerm(terms, name) {
  return terms.find(terms, term => term.name.toLowerCase() === name.toLowerCase());
}

/**
 * Get filter matcher function.
 *
 * @param {string} filterValue Filter value.
 * @return {(function(Object): (Object|boolean))} Matcher function.
 */
export function getFilterMatcher(filterValue) {
  const matchTermsForFilter = originalTerm => {
    if ('' === filterValue) {
      return originalTerm;
    }

    // If the term's name contains the filterValue then return it.
    if (-1 !== originalTerm.name.toLowerCase().indexOf(filterValue.toLowerCase())) {
      return originalTerm;
    }

    // Otherwise, return false. After mapping, the list of terms will need
    // to have false values filtered out.
    return false;
  };
  return matchTermsForFilter;
}

/**
 * Term selector.
 *
 * @param {Object} props      Component props.
 * @param {string} props.slug Taxonomy slug.
 * @return {WPElement}        Term selector component.
 */
export function TermSelector({slug}) {
  const [adding, setAdding] = useState(false);
  const [formName, setFormName] = useState('');
  /**
   * @type {*}
   */
  const [showForm, setShowForm] = useState(false);
  const [filterValue, setFilterValue] = useState('');
  const [filteredTermsTree, setFilteredTermsTree] = useState([]);
  const debouncedSpeak = useDebounce(speak, 500);

  const {
    hasCreateAction,
    hasAssignAction,
    terms,
    loading,
    availableTerms,
    taxonomy,
    isUserAdmin,
  } = useSelect(
    select => {
      const {getCurrentPost, getEditedPostAttribute} = select('core/editor');
      const {getTaxonomy, getEntityRecords, isResolving, canUser} = select(coreStore);
      const _taxonomy = getTaxonomy(slug);
      const post = getCurrentPost();

      return {
        isUserAdmin: canUser('create', 'users') ?? false,
        hasCreateAction: _taxonomy ?
          post._links?.[
            'wp:action-create-' + _taxonomy.rest_base
          ] ?? false :
          false,
        hasAssignAction: _taxonomy ?
          post._links?.[
            'wp:action-assign-' + _taxonomy.rest_base
          ] ?? false :
          false,
        terms: _taxonomy ?
          getEditedPostAttribute(_taxonomy.rest_base) :
          EMPTY_ARRAY,
        loading: isResolving('getEntityRecords', [
          'taxonomy',
          slug,
          DEFAULT_QUERY,
        ]),
        availableTerms:
           getEntityRecords('taxonomy', slug, DEFAULT_QUERY) ||
           EMPTY_ARRAY,
        taxonomy: _taxonomy,
      };
    },
    [slug]
  );

  const {saveEntityRecord} = useDispatch(coreStore);
  const {editPost} = useDispatch('core/editor');

  const availableTermsTree = useMemo(
    () => sortBySelected(availableTerms, terms),
    // Remove `terms` from the dependency list to avoid reordering every time
    // checking or unchecking a term.
    [availableTerms]
  );

  if (!hasAssignAction) {
    return null;
  }

  /**
   * Append new term.
   *
   * @param {Object} term Term object.
   * @return {Promise} A promise that resolves to save term object.
   */
  const addTerm = term => {
    return saveEntityRecord('taxonomy', slug, term);
  };

  /**
   * Update terms for post.
   *
   * @param {number[]} termIds Term ids.
   */
  const onUpdateTerms = termIds => {
    editPost({[taxonomy.rest_base]: termIds});
  };

  /**
   * Handler for checking term.
   *
   * @param {number} termId
   */
  const onChange = termId => {
    const hasTerm = terms.includes(termId);
    const newTerms = hasTerm ?
      terms.filter(id => id !== termId) :
      [...terms, termId];
    onUpdateTerms(newTerms);
  };

  const onChangeFormName = value => {
    setFormName(value);
  };

  const onToggleForm = () => {
    setShowForm(!showForm);
  };

  const onAddTerm = async event => {
    event.preventDefault();
    if (formName === '' || adding) {
      return;
    }

    // Check if the term we are adding already exists.
    const existingTerm = findTerm(availableTerms, formName);
    if (existingTerm) {
      // If the term we are adding exists but is not selected select it.
      if (!terms.some(term => term === existingTerm.id)) {
        onUpdateTerms([...terms, existingTerm.id]);
      }

      setFormName('');

      return;
    }
    setAdding(true);

    const newTerm = await addTerm({
      name: formName,
    });

    const defaultName = slug === 'category' ? __('Category') : __('Term');
    const termAddedMessage = sprintf(
      /* translators: %s: taxonomy name */
      _x('%s added', 'term'),
      taxonomy?.labels?.singular_name ?? defaultName
    );
    speak(termAddedMessage, 'assertive');
    setAdding(false);
    setFormName('');
    onUpdateTerms([...terms, newTerm.id]);
  };

  const setFilter = value => {
    const newFilteredTermsTree = availableTermsTree
      .map(getFilterMatcher(value))
      .filter(term => term);

    setFilterValue(value);
    setFilteredTermsTree(newFilteredTermsTree);

    const resultCount = newFilteredTermsTree.length;
    const resultsFoundMessage = sprintf(
      /* translators: %d: number of results */
      _n('%d result found.', '%d results found.', resultCount),
      resultCount
    );

    debouncedSpeak(resultsFoundMessage, 'assertive');
  };

  const renderTerms = renderedTerms => renderedTerms.map(term => (
    <div key={term.id} className="editor-post-taxonomies__hierarchical-terms-choice">
      <CheckboxControl
        __nextHasNoMarginBottom
        checked={terms.indexOf(term.id) !== -1}
        onChange={() => {
          const termId = parseInt(term.id, 10);
          onChange(termId);
        }}
        label={decodeEntities(term.name)}
      />
    </div>
  ));

  const labelWithFallback = (
    labelProperty,
    fallbackIsTag,
    fallbackIsNotTag
  ) =>
    taxonomy?.labels?.[labelProperty] ??
    (slug === 'post_tag' ? fallbackIsTag : fallbackIsNotTag);
  const newTermButtonLabel = labelWithFallback(
    'add_new_item',
    __('Add new tag'),
    __('Add new term')
  );
  const newTermLabel = labelWithFallback(
    'new_item_name',
    __('Add new tag'),
    __('Add new term')
  );
  const newTermSubmitLabel = newTermButtonLabel;
  const filterLabel = taxonomy?.labels?.search_items ?? __('Search Terms');
  const groupLabel = taxonomy?.name ?? __('Terms');
  const showFilter = availableTerms.length >= MIN_TERMS_COUNT_FOR_FILTER;

  return (
    <>
      { showFilter && (
        <TextControl
          className="editor-post-taxonomies__hierarchical-terms-filter"
          label={filterLabel}
          value={filterValue}
          onChange={setFilter}
        />
      ) }
      <div
        className="editor-post-taxonomies__hierarchical-terms-list"
        tabIndex="0"
        role="group"
        aria-label={groupLabel}
      >
        { renderTerms(
          '' !== filterValue ? filteredTermsTree : availableTermsTree
        ) }
      </div>
      {/* Only admins should be allowed to create new tags */}
      { !loading && hasCreateAction && (isUserAdmin || slug !== 'post_tag') && (
        <Button
          onClick={onToggleForm}
          className="editor-post-taxonomies__hierarchical-terms-add"
          aria-expanded={showForm}
          variant="link"
        >
          { newTermButtonLabel }
        </Button>
      ) }
      {!isUserAdmin && slug === 'post_tag' && <p>{__('New tags can only be created by an administrator', 'planet4-blocks-backend')}</p>}
      { showForm && (
        <form onSubmit={onAddTerm}>
          <TextControl
            className="editor-post-taxonomies__hierarchical-terms-input"
            label={newTermLabel}
            value={formName}
            onChange={onChangeFormName}
            required
          />
          <Button
            variant="secondary"
            type="submit"
            className="editor-post-taxonomies__hierarchical-terms-submit"
          >
            { newTermSubmitLabel }
          </Button>
        </form>
      ) }
    </>
  );
}

export default withFilters('editor.PostTaxonomyType')(TermSelector);
