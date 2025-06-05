/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as blocksStore } from '@wordpress/blocks';
import {
  InspectorControls,
  useBlockProps,
  MediaUpload,
  MediaUploadCheck,
} from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  TextControl,
  __experimentalNumberControl as NumberControl,
  SelectControl,
  Button,
  BaseControl,
  useBaseControlProps,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

function MediaPicker({ open, media, label }) {
  const { baseControlProps } = useBaseControlProps({ label: label || 'your media' });

  return (
    <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
      <BaseControl {...baseControlProps}>
        <div style={{ marginBottom: '1em', border: '1px solid #ccc', padding: '10px' }}>
          {media ? (
            <div>
              <img
                src={media.source_url}
                alt={media.alt || ''}
                style={{ maxWidth: '100%', height: 'auto' }}
              />
              <p>{media.alt || __('No alt text provided')}</p>
            </div>
          ) : null}
          <Button onClick={open} isPrimary>
            {media ? __('Change Image') : __('Select Image')}
          </Button>
        </div>
      </BaseControl>
    </div>
  );
}

function MediaControl({ label, value, onChange }) {
  const { media } = useSelect(
    select => ({
      media: select('core').getMedia(value),
    }),
    [value]
  );

  return (
    <MediaUploadCheck>
      <MediaUpload
        onSelect={onChange}
        render={({ open }) => (
          <MediaPicker
            open={open}
            media={media}
            label={label}
          />
        )}
        value={value}
        allowedTypes={['image']}
        gallery={false}
        multiple={false}
        mediaId={value}
        media={media}
        onError={error => {
          console.error('Media upload error:', error);
        }}
      />
    </MediaUploadCheck>
  );
}

function TextArrayControl({ name, value, defaultValue, onChange }) {
  const { baseControlProps } = useBaseControlProps({ label: name });

  return (
    <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
      <BaseControl {...baseControlProps}>
        <div style={{ padding: '5px', border: '1px solid #ccc', marginBottom: '1em' }}>
          {(value || defaultValue || ['']).map((item, index) => (
            <TextControl
              key={index}
              value={item}
              onChange={(newValue) => {
                const newArray = [...(value || [])];
                newArray[index] = newValue;
                onChange({ [name]: newArray });
              }}
            />
          ))}
          <Button
            isSecondary
            onClick={() => {
              const newArray = [...(value || []), ''];
              onChange({ [name]: newArray });
            }}
          >
            {__(`Add ${name} entry`)}
          </Button>
        </div>
      </BaseControl>
    </div>
  );
}

function FieldControl(props) {
  const { name, value, onChange, schema } = props;
  const { type, media, enum: enumValues, default: defaultValue } = schema;

  if (media) {
    if (type !== 'number') {
      return null;
    }

    const originalLabel = name.endsWith('_media_id')
      ? name.replace('_media_id', '')
      : name;

    return (
      <MediaControl
        label={originalLabel}
        value={value || defaultValue || 0}
        onChange={({ id, url }) => {
          onChange({
            [originalLabel]: url,
            [name]: id,
          });
        }}
      />
    );
  }

  if (type === 'string') {
    return (
      <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
        <TextControl
          label={name}
          value={value || defaultValue || ''}
          onChange={newValue => {
            onChange({ [name]: newValue });
          }}
        />
      </div>
    );
  }

  if (type === 'number') {
    return (
      <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
        <NumberControl
          label={name}
          value={value || defaultValue || 0}
          onChange={newValue => {
            onChange({ [name]: newValue });
          }}
        />
      </div>
    );
  }

  if (type === 'boolean') {
    return (
      <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
        <SelectControl
          label={name}
          value={value || defaultValue || false}
          onChange={newValue => {
            onChange({ [name]: newValue === 'true' });
          }}
          options={[
            { label: __('True'), value: 'true' },
            { label: __('False'), value: 'false' },
          ]}
        />
      </div>
    );
  }

  if (Array.isArray(enumValues)) {
    return (
      <div style={{ marginBottom: '1em', paddingLeft: '5px' }}>
        <SelectControl
          label={name}
          value={value || defaultValue || ''}
          onChange={newValue => {
            onChange({ [name]: newValue });
          }}
          options={enumValues.map(enumValue => ({
            label: enumValue,
            value: enumValue,
          }))}
        />
      </div>
    );
  }

  if (type === 'array') {
    return (
      <TextArrayControl {...props} />
    );
  }

  return null;
}

function ClutchBlockEditingInterface({
  name,
  attributes,
  setAttributes,
  isSelected,
}) {
  const blockProps = useBlockProps();
  const blockType = useSelect(
    select => select(blocksStore).getBlockType(name),
    [name]
  );
  const fields = blockType?.attributes || {};

  return (
    <>
      <InspectorControls>
        <Panel header={__('Settings')}>
          <PanelBody title={`${blockType.title} config`}>{''}</PanelBody>
        </Panel>
      </InspectorControls>
      <div
        {...blockProps}
        style={{ padding: '10px 20px', border: '1px solid #ccc' }}
      >
        <h2>{blockType.title || 'Clutch Block'}</h2>
        {Object.entries(fields).map(([key, value]) => {
          // Skip non-clutch attributes
          if (!value?.clutch) {
            return null;
          }

          return (
            <FieldControl
              key={key}
              name={key}
              schema={value}
              value={attributes[key] || null}
              onChange={setAttributes}
            />
          );
        })}
      </div>
    </>
  );
}

export default ClutchBlockEditingInterface;
