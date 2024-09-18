import metadata from './block.json';
import {frontendRendered} from '../../functions/frontendRendered';

const v1 = {
  save: frontendRendered(metadata.name),
};

export default [v1];
