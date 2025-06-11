/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const props = useBlockProps.save();

  return (
    <div {...props}>
      <InnerBlocks.Content />
    </div>
  );
}
