import {useState, useEffect, useRef} from '@wordpress/element';
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
  const isClicking = useRef(false);
  const hasLoaded = useRef(false);


  const headings = getHeadingsFromDom(levels);

  useEffect(() => {
    makeSecondaryNavigationStickyonScroll();
    initializeJustifyContentAdjustment();
  }, []);

  useEffect(() => {
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 1.0,
    };

    const observerCallback = entries => {
      if (isClicking.current) {return;}

      entries.forEach(entry => {
        if (entry.isIntersecting) {
          if (hasLoaded.current) {
            setActiveLink(entry.target.id);
            window.history.replaceState(null, null, `#${entry.target.id}`);
          }
        }
      });
    };

    const observer = new IntersectionObserver(observerCallback, observerOptions);

    headings.forEach(({anchor}) => {
      const link = document.getElementById(anchor);
      if (link) {observer.observe(link);}
    });

    setTimeout(() => {
      hasLoaded.current = true;
    }, 500);

    return () => {
      headings.forEach(({anchor}) => {
        const link = document.getElementById(anchor);
        if (link) {observer.unobserve(link);}
      });
    };
  }, []);

  const handleClick = id => {
    isClicking.current = true;
    setActiveLink(id);

    setTimeout(() => {
      isClicking.current = false;
    }, 500);
  };

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
                data-target={anchor}
                onClick={() => handleClick(anchor)}
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
