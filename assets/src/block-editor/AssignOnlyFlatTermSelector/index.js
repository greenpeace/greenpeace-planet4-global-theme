/**
 * The logic of this component was copied from https://github.com/WordPress/gutenberg/blob/master/packages/editor/src/components/post-taxonomies/flat-term-selector.js
 * initially, then the functionality of creating non-existing terms was removed from it.
 */

/**
 * WordPress dependencies
 */
import {useEffect, useMemo, useState} from '@wordpress/element';
import {FormTokenField, withFilters} from '@wordpress/components';
import {useSelect, useDispatch} from '@wordpress/data';
import {store as coreStore} from '@wordpress/core-data';
import {useDebounce} from '@wordpress/compose';
import {decodeEntities} from '@wordpress/html-entities';

const {__, _x, sprintf} = wp.i18n;

/**
 * Shared reference to an empty array for cases where it is important to avoid
 * returning a new array reference on every invocation.
 *
 * @type {Array<any>}
 */
const EMPTY_ARRAY = [];

/**
 * Module constants
 */
const MAX_TERMS_SUGGESTIONS = 20;
const DEFAULT_QUERY = {
  per_page: MAX_TERMS_SUGGESTIONS,
  _fields: 'id,name',
  context: 'view',
};

const isSameTermName = (termA, termB) =>
  decodeEntities(termA).toLowerCase() ===
  decodeEntities(termB).toLowerCase();

const termNamesToIds = (names, terms) => names.map(
  termName => terms.find(term => isSameTermName(term.name, termName)).id
);

export const AssignOnlyFlatTermSelector = ({slug}) => {
  const [values, setValues] = useState([]);
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebounce(setSearch, 500);

  const {
    terms,
    taxonomy,
    hasAssignAction,
    hasResolvedTerms,
  } = useSelect(
    select => {
      const {getCurrentPost, getEditedPostAttribute} = select('core/editor');
      const {getEntityRecords, getTaxonomy, hasFinishedResolution} = select(coreStore);
      const post = getCurrentPost();
      const _taxonomy = getTaxonomy(slug);
      const _termIds = _taxonomy ? getEditedPostAttribute(_taxonomy.rest_base) : EMPTY_ARRAY;

      const query = {
        ...DEFAULT_QUERY,
        include: _termIds.join(','),
        per_page: -1,
      };

      return {
        hasAssignAction: _taxonomy ? post._links?.['wp:action-assign-' + _taxonomy.rest_base] ?? false : false,
        taxonomy: _taxonomy,
        termIds: _termIds,
        terms: _termIds.length ? getEntityRecords('taxonomy', slug, query) : EMPTY_ARRAY,
        hasResolvedTerms: hasFinishedResolution('getEntityRecords', ['taxonomy', slug, query]),
      };
    },
    [slug]
  );

  const {searchResults} = useSelect(
    select => {
      const {getEntityRecords} = select(coreStore);

      return {
        searchResults: !!search ? getEntityRecords('taxonomy', slug, {
          ...DEFAULT_QUERY,
          search,
        }) : EMPTY_ARRAY,
      };
    },
    [search, slug]
  );

  // Update terms state only after the selectors are resolved.
  // We're using this to avoid terms temporarily disappearing on slow networks
  // while core data makes REST API requests.
  useEffect(() => {
    if (hasResolvedTerms) {
      const newValues = (terms ?? []).map(term =>
        decodeEntities(term.name)
      );

      setValues(newValues);
    }
  }, [terms, hasResolvedTerms]);

  const suggestions = useMemo(() => {
    return (searchResults ?? []).map(term =>
      decodeEntities(term.name)
    );
  }, [searchResults]);

  const {editPost} = useDispatch('core/editor');

  if (!hasAssignAction) {
    return null;
  }

  function onUpdateTerms(newTermIds) {
    editPost({[taxonomy.rest_base]: newTermIds});
  }

  function onChange(termNames) {
    const availableTerms = [
      ...(terms ?? []),
      ...(searchResults ?? []),
    ];

    const uniqueTerms = termNames.reduce((acc, name) => {
      if (
        !acc.some(n => n.toLowerCase() === name.toLowerCase())
      ) {
        acc.push(name);
      }
      return acc;
    }, []);

    // Filter to remove new terms since we don't allow creation.
    const allowedTerms = uniqueTerms.filter(
      termName => availableTerms.find(term => isSameTermName(term.name, termName))
    );

    setValues(allowedTerms);

    return onUpdateTerms(termNamesToIds(allowedTerms, availableTerms));
  }

  const newTermLabel =
    taxonomy?.labels?.add_new_item ??
    (slug === 'post_tag' ? __('Add new tag') : __('Add new Term'));
  const singularName =
    taxonomy?.labels?.singular_name ??
    (slug === 'post_tag' ? __('Tag') : __('Term'));
  const termAddedLabel = sprintf(
    /* translators: %s: term name. */
    _x('%s added', 'term'),
    singularName
  );
  const termRemovedLabel = sprintf(
    /* translators: %s: term name. */
    _x('%s removed', 'term'),
    singularName
  );
  const removeTermLabel = sprintf(
    /* translators: %s: term name. */
    _x('Remove %s', 'term'),
    singularName
  );

  return (
    <FormTokenField
      __next40pxDefaultSize
      value={values}
      suggestions={suggestions}
      onChange={onChange}
      onInputChange={debouncedSearch}
      maxSuggestions={MAX_TERMS_SUGGESTIONS}
      label={newTermLabel}
      messages={{
        added: termAddedLabel,
        removed: termRemovedLabel,
        remove: removeTermLabel,
      }}
    />
  );
};

export default withFilters('editor.PostTaxonomyType')(AssignOnlyFlatTermSelector);
