/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	AlignmentControl,
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
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import { useOnEnter } from './use-enter.js';

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
	const { align, content, placeholder, className } = attributes;

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
						{availableClasses.map(
							({ label, className: clutchClass }) => (
								<PanelRow>
									<ToggleControl
										key={clutchClass}
										label={label}
										checked={classesMap[clutchClass]}
										onChange={(state) => {
											classesMap[clutchClass] = state;

											setAttributes({
												className: availableClasses
													.reduce(
														(
															acc,
															{
																className:
																	clutchClass,
															}
														) => {
															if (
																classesMap[
																	clutchClass
																]
															) {
																acc.push(
																	clutchClass
																);
															}

															return acc;
														},
														[]
													)
													.join(' '),
											});
										}}
									/>
								</PanelRow>
							)
						)}
					</PanelBody>
				</Panel>
			</InspectorControls>
			{blockEditingMode === 'default' && (
				<BlockControls group="block">
					<AlignmentControl
						value={align}
						onChange={(newAlign) =>
							setAttributes({
								align: newAlign,
							})
						}
					/>
				</BlockControls>
			)}
			<RichText
				identifier="content"
				tagName="p"
				{...blockProps}
				value={content}
				onChange={(newContent) =>
					setAttributes({ content: newContent })
				}
				onMerge={mergeBlocks}
				onReplace={onReplace}
				onRemove={onRemove}
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
				__unstableEmbedURLOnPaste
				__unstableAllowPrefixTransformations
			/>
		</>
	);
}

export default ParagraphBlock;
