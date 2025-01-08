import {getHeadingsFromDom} from '../TableOfContents/getHeadingsFromDom';

export const SecondaryNavigationFrontend = ({levels}) => {
  const headings = getHeadingsFromDom(levels);
  const setActive = event => {
    const allLinks = document.querySelectorAll('.secondary-navigation-link');
    allLinks.forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
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
                className="secondary-navigation-link"
                href={`#${anchor}`}
                onClick={setActive}
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
