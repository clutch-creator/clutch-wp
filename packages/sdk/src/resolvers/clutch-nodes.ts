import { WP_Block_Parsed } from 'wp-types';
import { TPermalinkInfo } from '../types';
import { resolveBlock } from './blocks';
import {
  checkForLinksInString,
  resolveLinkFromInfo,
  resolveLinksInString,
} from './links';
import { Resolver } from './resolver';

type TClutchFieldBase = {
  _clutch_type: string;
};

type TClutchFieldUser = TClutchFieldBase & {
  _clutch_type: 'user';
  id: number;
};

type TClutchFieldMedia = TClutchFieldBase & {
  _clutch_type: 'media';
  id: number;
};

type TClutchFieldPost = TClutchFieldBase & {
  _clutch_type: 'post';
  id: number;
  post_type: string;
};

type TClutchFieldTaxonomyTerm = TClutchFieldBase & {
  _clutch_type: 'taxonomy_term';
  id: number;
  taxonomy: string;
};

type TClutchFieldDate = TClutchFieldBase & {
  _clutch_type: 'date';
  date: string;
};

type TClutchFieldLink = TClutchFieldBase & {
  _clutch_type: 'link';
} & TPermalinkInfo;

type TClutchFieldBlock = TClutchFieldBase & {
  _clutch_type: 'block';
} & WP_Block_Parsed;

type TClutchField =
  | TClutchFieldUser
  | TClutchFieldMedia
  | TClutchFieldPost
  | TClutchFieldTaxonomyTerm
  | TClutchFieldDate
  | TClutchFieldLink
  | TClutchFieldBlock;

function isClutchField(value: unknown): value is TClutchField {
  return value !== null && typeof value === 'object' && '_clutch_type' in value;
}

async function resolveClutchField(value: TClutchField, resolver: Resolver) {
  const client = resolver.getClient();

  if (value._clutch_type === 'media') {
    return client.fetchPostById('attachment', value.id, false, resolver);
  }

  if (value._clutch_type === 'user') {
    return client.fetchUserById(value.id, resolver);
  }

  if (value._clutch_type === 'post') {
    return client.fetchPostById(value.post_type, value.id, false, resolver);
  }

  if (value._clutch_type === 'taxonomy_term') {
    return client.fetchTaxonomyTermById(
      value.taxonomy,
      value.id,
      false,
      resolver
    );
  }

  if (value._clutch_type === 'date') {
    return new Date(value.date);
  }

  if (value._clutch_type === 'link') {
    const templates = resolver.getPages();

    return resolveLinkFromInfo(value, templates);
  }

  if (value._clutch_type === 'block') {
    return resolveBlock(value, resolver);
  }

  return value;
}

function traverseClutchFields(
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  draftField: any,
  resolver: Resolver
): undefined {
  if (!draftField) return;

  // loop through acf fields and resolve objects
  if (typeof draftField !== 'object') return;

  Object.entries(draftField).forEach(async ([key, value]) => {
    const isObject = value && typeof value === 'object';

    if (isClutchField(value)) {
      resolver.waitUntil(async () => {
        const resolvedValue = await resolveClutchField(value, resolver);

        draftField[key] = resolvedValue;
      });
    } else if (isObject) {
      traverseClutchFields(value as Record<string, unknown>, resolver);
    } else if (
      typeof value === 'string' &&
      checkForLinksInString(value, resolver)
    ) {
      resolver.waitUntil(async () => {
        const resolvedValue = await resolveLinksInString(value, resolver);

        draftField[key] = resolvedValue;
      });
    }
  });
}

export async function resolveClutchFields<T = unknown>(
  acfValue: unknown,
  resolver: Resolver
): Promise<T> {
  if (!resolver.getClient().getConfig().disableResolving) {
    traverseClutchFields(acfValue, resolver);

    await resolver.waitAll();
  }

  return acfValue as T;
}
