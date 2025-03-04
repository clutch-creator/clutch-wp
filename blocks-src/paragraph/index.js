/**
 * Based off of https://github.com/WordPress/gutenberg/tree/v20.3.0/packages/block-library/src/paragraph
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { paragraph as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block.js';
import edit from './edit.js';
import metadata from './block.json';
import save from './save.js';
import transforms from './transforms.js';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	example: {
		attributes: {
			content: __(
				'In a village of La Mancha, the name of which I have no desire to call to mind, there lived not long since one of those gentlemen that keep a lance in the lance-rack, an old buckler, a lean hack, and a greyhound for coursing.'
			),
		},
	},
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
	transforms,
	merge(attributes, attributesToMerge) {
		return {
			content:
				(attributes.content || '') + (attributesToMerge.content || ''),
		};
	},
	edit,
	save,
};

initBlock({ name, metadata, settings });
