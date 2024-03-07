import {registerPostsListBlock} from './blocks/PostsList';
import {registerActionsListBlock} from './blocks/ActionsList';
import {registerSubmenuBlock} from './blocks/Submenu/SubmenuBlock';
import {registerTakeActionBoxoutBlock} from './blocks/TakeActionBoxout/TakeActionBoxoutBlock';
import {registerHappypointBlock} from './blocks/Happypoint/HappypointBlock';
import {setupCustomSidebar} from './block-editor/setupCustomSidebar';
import {setupQueryLoopBlockExtension} from './block-editor/QueryLoopBlockExtension';

wp.domReady(() => {
  // Make sure to unregister the posts-list native variation before registering planet4-blocks/posts-list
  wp.blocks.unregisterBlockVariation('core/query', 'posts-list');

  // Blocks
  registerSubmenuBlock();
  registerTakeActionBoxoutBlock();
  registerHappypointBlock();

  // Beta blocks
  registerActionsListBlock();
  registerPostsListBlock();
});

setupCustomSidebar();

// Setup new attributes to the core/query.
// It should be executed after the DOM is ready
setupQueryLoopBlockExtension();
