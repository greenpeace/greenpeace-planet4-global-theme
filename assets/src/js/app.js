import 'bootstrap';

import {setupCookies} from './cookies';
import {setupHeader} from './header';
import {setupLoadMore} from './load_more';
import {setupPDFIcon} from './pdf_icon';
import {setupSearch} from './search';
import {setupExternalLinks} from './external_links';
import {setupListingPages} from './listing_pages';
import {setupQueryLoopCarousel} from './query_loop_carousel';
import {setupClickabelActionsListCards} from './actions_list_clickable_cards';
import {removeNoPostText} from './query-no-posts';
import {removeRelatedPostsSection} from './remove_related_section_no_posts';
import {setupCountrySelector} from './country_selector';
import {createLinkForToCLinksToPageElements} from './setup_toc_navigation';

function requireAll(r) {
  r.keys().forEach(r);
}

requireAll(require.context('../images/icons/', true, /\.svg$/));

// Nested lists also get returned in the first array
const tableOfContentsList = document.querySelectorAll('.table-of-contents .wp-block-list')[0];
const allListElements = tableOfContentsList.querySelectorAll('li');

setupCookies();
setupHeader();
setupLoadMore();
setupPDFIcon();
setupSearch();
setupExternalLinks();
setupListingPages();
setupQueryLoopCarousel();
removeNoPostText();
removeRelatedPostsSection();
setupClickabelActionsListCards();
setupCountrySelector();
createLinkForToCLinksToPageElements(allListElements);
