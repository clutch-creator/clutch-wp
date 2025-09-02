/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { isRTL } from '@wordpress/i18n';

export default function save({ attributes }) {
  const { content, dropCap, direction, tag } = attributes;
  const Tag = tag || 'span';
  return (
    <Tag {...useBlockProps.save({ dir: direction })}>
      <RichText.Content value={content} />
    </Tag>
  );
}
