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
  const { content, tag } = attributes;
  const Tag = tag || 'span';

  return (
    <Tag {...useBlockProps.save()}>
      <RichText.Content value={content} />
    </Tag>
  );
}
