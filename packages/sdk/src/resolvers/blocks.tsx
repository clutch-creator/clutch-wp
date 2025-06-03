import { WP_Block_Parsed } from 'wp-types';
import { resolveClutchFields } from './clutch-nodes';
import { Resolver } from './resolver';

export async function resolveBlock(block: WP_Block_Parsed, resolver: Resolver) {
  const draftBlock = { ...block };
  const client = resolver.getClient();
  const blockResolver = new Resolver(client);
  const components = client.getComponents();

  if (!components) {
    return block;
  }

  const { RichText, Image, blockComponents } = components;

  // Resolve all clutch nodes
  blockResolver.waitPromise(resolveClutchFields(draftBlock, blockResolver));
  await blockResolver.waitAll();

  // Convert empty attributes to an empty object
  if (!draftBlock.attrs || Array.isArray(draftBlock.attrs)) {
    draftBlock.attrs = {};
  }

  const { innerHTML } = draftBlock;
  const { source_url: src, alt_text: alt, className } = draftBlock.attrs;
  const componentId = draftBlock.blockName
    ?.replace('clutch/composition-', '')
    ?.replace('-', '_');
  const Component = blockComponents?.[componentId];

  switch (draftBlock.blockName) {
    case 'clutch/paragraph':
    case 'core/heading':
      return (
        <RichText tag='div' className={className}>
          {innerHTML}
        </RichText>
      );
    case 'core/list':
      return (
        <RichText tag={draftBlock.attrs.ordered ? 'ol' : 'ul'}>
          {/* @ts-expect-error fix this */}
          {draftBlock.innerBlocks}
        </RichText>
      );
    case 'core/list-item':
      return <RichText tag='span'>{draftBlock.innerHTML}</RichText>;
    case 'core/image':
      return <Image src={src} alt={alt} className={className} />;
    default:
      return Component ? (
        <Component {...draftBlock.attrs} />
      ) : (
        <RichText tag='span'>{innerHTML}</RichText>
      );
  }
}
