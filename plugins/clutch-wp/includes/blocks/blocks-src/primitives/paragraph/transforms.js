/**
 * WordPress dependencies
 */
import { createBlock, getBlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import meta from './block.json';
import {
  HIGH_PRIORITY_TAGS,
  DEFAULT_PRIORITY_TAGS,
  REGULAR_PRIORITY_TAGS,
  HIGH_PRIORITY,
  DEFAULT_PRIORITY,
  MULTILINE_TEXT_PRIORITY,
  createSchemaForTags,
} from './tag-options.js';

const transforms = {
  from: [
    {
      type: 'raw',
      priority: DEFAULT_PRIORITY,
      selector: DEFAULT_PRIORITY_TAGS.join(','),
      schema: ({ phrasingContentSchema, isPaste }) =>
        createSchemaForTags(
          DEFAULT_PRIORITY_TAGS,
          phrasingContentSchema,
          isPaste
        ),
      transform(node) {
        const tagName = node.nodeName.toLowerCase();
        const attributes = getBlockAttributes(meta.name, node.outerHTML);
        attributes.tag = tagName;

        return createBlock(meta.name, attributes);
      },
    },
    {
      type: 'raw',
      priority: HIGH_PRIORITY, // Higher priority than core blocks
      selector: HIGH_PRIORITY_TAGS.join(','),
      schema: ({ phrasingContentSchema, isPaste }) =>
        createSchemaForTags(HIGH_PRIORITY_TAGS, phrasingContentSchema, isPaste),
      transform(node) {
        const tagName = node.nodeName.toLowerCase();

        // Encode tag information in a data attribute within the content
        const contentWithTag = `<span data-original-tag="${tagName}">${node.innerHTML}</span>`;

        const finalAttributes = {
          content: contentWithTag,
          tag: tagName, // WordPress will override this, but we'll extract from content
        };

        const block = createBlock(meta.name, finalAttributes);
        return block;
      },
    },
    {
      type: 'raw',
      priority: DEFAULT_PRIORITY,
      selector: REGULAR_PRIORITY_TAGS.join(','),
      schema: ({ phrasingContentSchema, isPaste }) =>
        createSchemaForTags(
          REGULAR_PRIORITY_TAGS,
          phrasingContentSchema,
          isPaste
        ),
      transform(node) {
        const tagName = node.nodeName.toLowerCase();
        const attributes = getBlockAttributes(meta.name, node.outerHTML);
        attributes.tag = tagName;

        return createBlock(meta.name, attributes);
      },
    },
    {
      type: 'raw',
      priority: MULTILINE_TEXT_PRIORITY, // Lower priority than heading transforms
      isMatch: node => {
        // Match text content that contains multiple paragraphs
        if (node.nodeType === Node.TEXT_NODE) {
          const text = node.textContent.trim();
          return text && text.includes('\n');
        }
        return false;
      },
      transform: node => {
        const text = node.textContent;
        const lines = text.split(/\n+/).filter(line => line.trim());

        if (lines.length <= 1) {
          return createBlock(meta.name, {
            content: text.trim(),
            // Let tag default to 'span' from block.json
          });
        }

        return lines.map(lineText =>
          createBlock(meta.name, {
            content: lineText.trim(),
            // Let tag default to 'span' from block.json
          })
        );
      },
    },
  ],
};

export default transforms;
