import {v4 as uuid} from 'uuid';
import {Covers} from './Covers';
const {__} = wp.i18n;

export const CoversCarouselLayout = ({covers, amountOfCoversPerRow, ...props}) => {
  const uniqueId = `covers-${uuid()}`;
  return (
    <div id={uniqueId} className="carousel slide">
      {covers.length > amountOfCoversPerRow &&
        <ol className="carousel-indicators">
          {covers.map((cover, index) => {
            if (index % amountOfCoversPerRow !== 0) {
              return null;
            }

            return (
              <li
                key={`indicator-${index / amountOfCoversPerRow}`}
                data-bs-target={`#${uniqueId}`}
                data-bs-slide-to={index / amountOfCoversPerRow}
                {...index === 0 && {className: 'active', 'aria-current': 'true'}}
              />
            );
          })}
        </ol>
      }
      <div className="carousel-inner">
        {covers.map((cover, index) => {
          if (index % amountOfCoversPerRow !== 0) {
            return null;
          }

          return (
            <div className={`carousel-item ${index === 0 ? 'active' : ''}`} key={`slide-${index / amountOfCoversPerRow}`}>
              <Covers covers={covers.slice(index, index + amountOfCoversPerRow)} {...props} />
            </div>
          );
        })}
      </div>
      {covers.length > amountOfCoversPerRow &&
        <>
          <button className="carousel-control-prev" data-bs-target={`#${uniqueId}`} data-bs-slide="prev">
            <span className="carousel-control-prev-icon" aria-hidden="true"></span>
            <span className="visually-hidden">{__('Prev', 'planet4-blocks')}</span>
          </button>
          <button className="carousel-control-next" data-bs-target={`#${uniqueId}`} data-bs-slide="next">
            <span className="carousel-control-next-icon" aria-hidden="true"></span>
            <span className="visually-hidden">{__('Next', 'planet4-blocks')}</span>
          </button>
        </>
      }
    </div>
  );
};
