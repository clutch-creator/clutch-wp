import {
  checkForLinksInString,
  resolveLinkFromInfo,
  resolveLinksInString,
} from "./links";
import { Resolver } from "./resolver";
import { TPermalinkInfo } from "./types";

type TClutchFieldBase = {
  _clutch_type: string;
};

type TClutchFieldUser = TClutchFieldBase & {
  _clutch_type: "user";
  id: number;
};

type TClutchFieldMedia = TClutchFieldBase & {
  _clutch_type: "media";
  id: number;
};

type TClutchFieldPost = TClutchFieldBase & {
  _clutch_type: "post";
  id: number;
  post_type: string;
};

type TClutchFieldTaxonomyTerm = TClutchFieldBase & {
  _clutch_type: "taxonomy_term";
  id: number;
  taxonomy: string;
};

type TClutchFieldDate = TClutchFieldBase & {
  _clutch_type: "date";
  date: string;
};

type TClutchFieldLink = TClutchFieldBase & {
  _clutch_type: "link";
} & TPermalinkInfo;

type TClutchField =
  | TClutchFieldUser
  | TClutchFieldMedia
  | TClutchFieldPost
  | TClutchFieldTaxonomyTerm
  | TClutchFieldDate
  | TClutchFieldLink;

function isClutchField(value: unknown): value is TClutchField {
  return value !== null && typeof value === "object" && "_clutch_type" in value;
}

async function resolveClutchField(value: TClutchField, resolver: Resolver) {
  const client = resolver.getClient();

  if (value._clutch_type === "media") {
    return client.fetchPostById("attachment", value.id, false, resolver);
  }

  if (value._clutch_type === "user") {
    return client.fetchUserById(value.id, resolver);
  }

  if (value._clutch_type === "post") {
    return client.fetchPostById(value.post_type, value.id, false, resolver);
  }

  if (value._clutch_type === "taxonomy_term") {
    return client.fetchTaxonomyTermById(
      value.taxonomy,
      value.id,
      false,
      resolver
    );
  }

  if (value._clutch_type === "date") {
    return new Date(value.date);
  }

  if (value._clutch_type === "link") {
    const templates = await resolver.getTemplates();

    return resolveLinkFromInfo(value, templates);
  }
}

function traverseClutchFields(
  fieldValue: Record<string, unknown>,
  resolver: Resolver
): undefined {
  if (!fieldValue) return;

  // loop through acf fields and resolve objects
  if (typeof fieldValue !== "object") return;

  Object.entries(fieldValue).forEach(async ([key, value]) => {
    const isObject = value && typeof value === "object";

    if (isClutchField(value)) {
      resolver.waitUntil(async () => {
        const resolvedValue = await resolveClutchField(value, resolver);
        const mutableFieldValue = fieldValue as Record<string, unknown>;

        mutableFieldValue[key] = resolvedValue;
      });
    } else if (isObject) {
      traverseClutchFields(value as Record<string, unknown>, resolver);
    } else if (typeof value === "string" && checkForLinksInString(value)) {
      resolver.waitUntil(async () => {
        const resolvedValue = await resolveLinksInString(value);
        const mutableFieldValue = fieldValue as Record<string, unknown>;

        mutableFieldValue[key] = resolvedValue;
      });
    }
  });
}

export async function resolveClutchFields(
  acfValue: Record<string, unknown>,
  resolver: Resolver
): Promise<void> {
  traverseClutchFields(acfValue, resolver);

  await resolver.waitAll();
}
