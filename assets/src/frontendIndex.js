// This ESLint error is disabled since 'regenerator-runtime/runtime' has already been added by another package.
// There is no need to explicitly include it in the list of dependencies in the package.json file.
// eslint-disable-next-line import/no-extraneous-dependencies
import 'regenerator-runtime/runtime';

import {createRoot} from 'react-dom/client';
import {TableOfContentsFrontend} from './blocks/TableOfContents/TableOfContentsFrontend';
import {HappyPointFrontend} from './blocks/HappyPoint/HappyPointFrontend';
import {ColumnsFrontend} from './blocks/Columns/ColumnsFrontend';
import {TopicLinkFrontend} from './blocks/TopicLink/TopicLinkFrontend';
import {setupLightboxForImages} from './blocks/components/Lightbox/setupLightboxForImages';
import {setupParallax} from './blocks/components/Parallax/setupParallax';
import {SecondaryNavigationFrontend} from './blocks/SecondaryNavigation/SecondaryNavigationFrontend';

// Render React components
const COMPONENTS = {
  'planet4-blocks/submenu': TableOfContentsFrontend,
  'planet4-blocks/happypoint': HappyPointFrontend,
  'planet4-blocks/columns': ColumnsFrontend,
  'planet4-blocks/topic-link': TopicLinkFrontend,
  'planet4-blocks/secondary-navigation': SecondaryNavigationFrontend,
};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-render]').forEach(
    blockNode => {
      const blockName = blockNode.dataset.render;
      if (!COMPONENTS[blockName]) {
        return;
      }

      const BlockFrontend = COMPONENTS[blockName];
      if (!BlockFrontend) {
        return;
      }
      const attributes = JSON.parse(blockNode.dataset.attributes);
      const rootElement = createRoot(blockNode);
      rootElement.render(<BlockFrontend {...attributes.attributes} />);
    }
  );

  setupLightboxForImages();
  setupParallax();
});
