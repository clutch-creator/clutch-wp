/**
 * WordPress dependencies
 */
import { createBlock, getBlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import meta from './block.json';

const transforms = {
	from: [
		{
			type: 'raw',
			// Paragraph is a fallback and should be matched last.
			priority: 20,
			selector: 'p',
			schema: ({ phrasingContentSchema, isPaste }) => ({
				p: {
					children: phrasingContentSchema,
					attributes: isPaste ? [] : ['style', 'id'],
				},
			}),
			transform(node) {
				const attributes = getBlockAttributes(
					meta.name,
					node.outerHTML
				);
				const { textAlign } = node.style || {};

				if (
					textAlign === 'left' ||
					textAlign === 'center' ||
					textAlign === 'right'
				) {
					attributes.align = textAlign;
				}

				return createBlock(meta.name, attributes);
			},
		},
	],
};

export default transforms;
