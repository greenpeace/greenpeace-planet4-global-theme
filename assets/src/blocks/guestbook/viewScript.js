import {createRoot} from 'react-dom/client';
import metadata from './block.json';
import GuestBook from './guestbook';

// Fallback for non migrated content. Remove after migration.
document.querySelectorAll(`[data-render="${metadata.name}"]`).forEach(
  blockNode => {
    const rootElement = createRoot(blockNode);
    rootElement.render(<GuestBook />);
  }
);
