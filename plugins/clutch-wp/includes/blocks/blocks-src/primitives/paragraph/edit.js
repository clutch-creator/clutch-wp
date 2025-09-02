/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
  BlockControls,
  InspectorControls,
  RichText,
  useBlockProps,
  useBlockEditingMode,
} from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  PanelRow,
  ToggleControl,
  ToolbarGroup,
  ToolbarButton,
  Dropdown,
  MenuGroup,
  MenuItem,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import { create } from '@wordpress/rich-text';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import { useOnEnter } from './use-enter.js';
import { TAG_OPTIONS } from './tag-options.js';

function ParagraphBlock({
  attributes,
  mergeBlocks,
  onReplace,
  onRemove,
  setAttributes,
  clientId,
  isSelected: isSingleSelected,
  name,
}) {
  const [availableClasses, setAvailableClasses] = useState([]);
  const { content, placeholder, className, tag } = attributes;

  // Extract original tag from content if it exists (workaround for copy-paste)
  useEffect(() => {
    if (content && typeof content === 'string') {
      const match = content.match(/data-original-tag="([^"]+)"/);
      if (match && match[1] !== tag) {
        // Set the tag attribute and clean up the content
        const cleanContent = content.replace(
          /<span data-original-tag="[^"]*">([^<]*)<\/span>/,
          '$1'
        );
        setAttributes({
          tag: match[1],
          content: cleanContent,
        });
      }
    }
  }, [content, tag, setAttributes]);

  const blockProps = useBlockProps({
    ref: useOnEnter({ clientId, content }),
  });
  const blockEditingMode = useBlockEditingMode();
  const classesMap = availableClasses.reduce(
    (acc, { className: clutchClass }) => {
      acc[clutchClass] = className?.split(' ').includes(clutchClass);

      return acc;
    },
    {}
  );

  const currentTagOption =
    TAG_OPTIONS.find(option => option.tag === tag) ||
    TAG_OPTIONS.find(option => option.tag === 'span');

  // Handle splitting content into multiple blocks
  const onSplit = (value, isOriginal) => {
    let newAttributes = { ...attributes };

    if (isOriginal || value) {
      newAttributes = {
        ...newAttributes,
        content: value,
      };
    }

    const block = createBlock(name, newAttributes);

    if (isOriginal) {
      block.clientId = clientId;
    }

    return block;
  };

  // Handle pasted content with multiple paragraphs
  const handlePaste = ({ value, onChange, plainText }) => {
    if (plainText && plainText.includes('\n')) {
      const lines = plainText.split(/\n+/).filter(line => line.trim());

      if (lines.length > 1) {
        // Create multiple blocks for multi-line content
        const blocks = lines.map((lineText, index) =>
          createBlock(name, {
            content: lineText.trim(),
            tag: tag || 'span',
          })
        );

        // Replace current block with multiple blocks
        onReplace(blocks);
        return;
      }
    }

    // For single line or rich content, use default behavior
    return false;
  };

  useEffect(() => {
    // Get available classes from the REST API (requires edit_posts permission)
    apiFetch({
      path: 'clutch/v1/block-styles',
    }).then(setAvailableClasses);
  }, []);

  return (
    <>
      <InspectorControls>
        <Panel header={__('Settings')}>
          <PanelBody title={__('Paragraph styles')}>
            {availableClasses.map(({ label, className: clutchClass }) => (
              <PanelRow>
                <ToggleControl
                  key={clutchClass}
                  label={label}
                  checked={classesMap[clutchClass]}
                  onChange={state => {
                    classesMap[clutchClass] = state;

                    setAttributes({
                      className: availableClasses
                        .reduce((acc, { className: clutchClass }) => {
                          if (classesMap[clutchClass]) {
                            acc.push(clutchClass);
                          }

                          return acc;
                        }, [])
                        .join(' '),
                    });
                  }}
                />
              </PanelRow>
            ))}
          </PanelBody>
        </Panel>
      </InspectorControls>
      {blockEditingMode === 'default' && (
        <BlockControls group='block'>
          <ToolbarGroup>
            <Dropdown
              popoverProps={{ placement: 'bottom-start' }}
              renderToggle={({ isOpen, onToggle }) => (
                <ToolbarButton
                  onClick={onToggle}
                  aria-haspopup='true'
                  aria-expanded={isOpen}
                  text={currentTagOption.label}
                />
              )}
              renderContent={() => (
                <MenuGroup>
                  {TAG_OPTIONS.map(option => (
                    <MenuItem
                      key={option.tag}
                      isSelected={tag === option.tag}
                      onClick={() => setAttributes({ tag: option.tag })}
                    >
                      {option.label}
                    </MenuItem>
                  ))}
                </MenuGroup>
              )}
            />
          </ToolbarGroup>
        </BlockControls>
      )}
      <RichText
        identifier='content'
        tagName={tag || 'span'}
        {...blockProps}
        value={content}
        onChange={newContent => setAttributes({ content: newContent })}
        onMerge={mergeBlocks}
        onSplit={onSplit}
        onReplace={onReplace}
        onRemove={onRemove}
        onPaste={handlePaste}
        aria-label={
          RichText.isEmpty(content)
            ? __(
                'Empty block; start writing or type forward slash to choose a block'
              )
            : __('Block: Paragraph')
        }
        data-empty={RichText.isEmpty(content)}
        placeholder={placeholder || __('Type / to choose a block')}
        data-custom-placeholder={placeholder ? true : undefined}
        preserveWhiteSpace
        __unstableEmbedURLOnPaste
        __unstableAllowPrefixTransformations
        __unstableMarkAutomaticChange
      />
    </>
  );
}

export default ParagraphBlock;
