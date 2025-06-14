/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as blocksStore } from '@wordpress/blocks';
import {
  InspectorControls,
  useBlockProps,
  InnerBlocks,
} from '@wordpress/block-editor';
import { Panel, PanelBody, Dashicon } from '@wordpress/components';

function ClutchBlockEditingInterface({
  attributes,
  // setAttributes,
  // isSelected,
}) {
  const blockProps = useBlockProps();
  const { name } = attributes || {};

  return (
    <>
      <InspectorControls>
        <Panel header={__('Settings')}>
          <PanelBody title={`${name} config`}>{''}</PanelBody>
        </Panel>
      </InspectorControls>
      <div
        {...blockProps}
        style={{ padding: '10px 20px', border: '1px solid #ccc' }}
      >
        <h2>{name || 'children'}</h2>
        <InnerBlocks
          templateLock={false}
          renderAppender={() => <InnerBlocks.ButtonBlockAppender />}
        />
      </div>
    </>
  );
}

export default ClutchBlockEditingInterface;
