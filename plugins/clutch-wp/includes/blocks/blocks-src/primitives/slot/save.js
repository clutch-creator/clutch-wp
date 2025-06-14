/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const { name } = attributes || {};
  const blockProps = useBlockProps.save({
    className: 'clutch-slot',
    'data-slot-name': name,
  });

  return (
    <div {...blockProps}>
      <InnerBlocks.Content />
    </div>
  );
}
