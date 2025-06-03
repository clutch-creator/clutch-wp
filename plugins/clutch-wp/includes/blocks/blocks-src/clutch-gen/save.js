/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const props = useBlockProps.save();

  return <p {...props}>Saved Clutch Block</p>;
}
