import {useState, useEffect} from '@wordpress/element';
import {getHeadingsFromDom} from '../TableOfContents/getHeadingsFromDom';
import {initializeJustifyContentAdjustment} from './adjustNavWidth';

const makeSecondaryNavigationStickyonScroll = () => {
  const pageHeader = document.querySelector('.is-pattern-p4-page-header');

  if (!pageHeader) {return;}

  const stickyElement = document.querySelector('.secondary-navigation-block');
  const container = document.querySelector('.page-content');
  const offset = 20;

  window.addEventListener('scroll', () => {
    const containerRect = container.getBoundingClientRect();
    const stickyRect = stickyElement.getBoundingClientRect();
    const pageHeaderRect = pageHeader.getBoundingClientRect();

    if (pageHeaderRect.bottom <= offset && containerRect.bottom > stickyRect.height + offset) {
      stickyElement.classList.add('stuck');
    } else {
      stickyElement.classList.remove('stuck');
    }
  });
};

export const SecondaryNavigationFrontend = ({levels}) => {
  const [activeLink, setActiveLink] = useState('');
  const headings = getHeadingsFromDom(levels);

  useEffect(() => {
    makeSecondaryNavigationStickyonScroll();
    initializeJustifyContentAdjustment();
  }, [headings]);

  return (
    <section className="block secondary-navigation-block">
      <div className="secondary-navigation-menu container">
        <ul className="secondary-navigation-item">
          {headings.map(({anchor, content}) => (
            <li
              key={anchor}
            >
              <a
                className={`secondary-navigation-link ${activeLink === anchor ? 'active': ''}`}
                href={`#${anchor}`}
                onClick={() => setActiveLink(anchor)}
              >
                {content}
              </a>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
};
