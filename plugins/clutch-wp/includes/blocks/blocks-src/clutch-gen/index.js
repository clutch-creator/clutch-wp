/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { widget as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block.js';
import edit from './edit.js';
import metadata from './block.json';
import save from './save.js';

const { name } = metadata;

export { metadata, name };

export const settings = {
  icon,
  __experimentalLabel(attributes, { context }) {
    const customName = attributes?.metadata?.name;

    if (context === 'list-view' && customName) {
      return customName;
    }

    if (context === 'accessibility') {
      if (customName) {
        return customName;
      }

      const { content } = attributes;
      return !content || content.length === 0 ? __('Empty') : content;
    }
  },
  merge(attributes, attributesToMerge) {
    return {
      content: (attributes.content || '') + (attributesToMerge.content || ''),
    };
  },
  edit,
  save,
};

initBlock({ name, metadata, settings });
