import { WpPageType, WpPageView } from "../statics";
import {
  TPermalinkInfo,
  TWpTemplate,
  TWpTemplatePostType,
  TWpTemplateTaxonomy,
} from "../types";
import { Resolver } from "./resolver";

const SKIP_PATHS = ["wp-admin", "wp-content", "wp-json"];

type TLinkOptions = {
  absolute?: boolean;
  hostname?: string;
};

export async function getPermalinkInfo(
  resolver: Resolver,
  url: string,
  relativePath: string
): Promise<TPermalinkInfo | undefined> {
  const cacheKey = relativePath.replace(/\//g, "-").slice(0, -1);
  const client = resolver.getClient();
  const headers = await resolver.getHeaders();

  // Use explicit typing to access the private method
  const clientWithPrivateMethod = client as unknown as {
    wpPluginGet<T>(
      path: string,
      params: Record<string, unknown>,
      tags: string[],
      headers?: Record<string, string>
    ): Promise<T | undefined>;
  };

  const res = await clientWithPrivateMethod.wpPluginGet<TPermalinkInfo>(
    "permalink-info",
    {
      url,
    },
    ["permalinks", `perma-${cacheKey}`],
    headers
  );

  return res;
}

export function resolveLinkFromInfo(
  permalinkInfo: TPermalinkInfo,
  templates: TWpTemplate[],
  linkOptions?: TLinkOptions
): string | undefined {
  let matchedPath;

  if (!permalinkInfo) return matchedPath;

  switch (permalinkInfo.object_type) {
    case "post": {
      const { name, post_type } = permalinkInfo.details;
      const postTypeTemplates = templates.filter(
        (t): t is TWpTemplatePostType =>
          t.type === WpPageType.POST_TYPE && t.name === post_type
      );
      const singleSpecific = postTypeTemplates.find(
        (t) => t.template === WpPageView.SINGLE_SPECIFIC && t.slug === name
      );

      if (singleSpecific) {
        matchedPath = singleSpecific.path;
      } else {
        const singleAny = postTypeTemplates.find(
          (t) => t.template === WpPageView.SINGLE_ANY
        );

        if (singleAny) matchedPath = singleAny.path.replace("[slug]", name);
        else {
          const archive = postTypeTemplates.find(
            (t) => t.template === WpPageView.ARCHIVE
          );

          if (archive) matchedPath = archive.path;
        }
      }
      break;
    }

    case "taxonomy": {
      const { name } = permalinkInfo.details;
      const taxTemplates = templates.filter(
        (t): t is TWpTemplateTaxonomy =>
          t.type === WpPageType.TAXONOMY && t.name === name
      );
      const archive = taxTemplates.find(
        (t) => t.template === WpPageView.ARCHIVE
      );

      if (archive) matchedPath = archive.path;
      break;
    }
    case "taxonomy_term": {
      const { name, taxonomy_name } = permalinkInfo.details;
      const taxTemplates = templates.filter(
        (t): t is TWpTemplateTaxonomy =>
          t.type === WpPageType.TAXONOMY && t.name === taxonomy_name
      );
      const singleAny = taxTemplates.find(
        (t) => t.template === WpPageView.SINGLE_ANY
      );

      if (singleAny) matchedPath = singleAny.path.replace("[slug]", name);

      break;
    }

    default:
      break;
  }

  if (!matchedPath) {
    matchedPath = "/";
  }

  if (linkOptions?.absolute) {
    const hostname =
      linkOptions.hostname || process.env.NEXT_PUBLIC_WEBSITE_URL;

    try {
      const url = new URL(matchedPath, hostname).href;

      return url;
    } catch (err) {
      // ignore
    }
  }

  return matchedPath;
}

/**
 * Resolve a link converting from wp installation domain to clutch project
 */
export async function resolveLink(
  url: string,
  resolver: Resolver,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLink(
  url: string,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLink(
  url: string,
  resolverOrOptions?: Resolver | TLinkOptions,
  linkOptions?: TLinkOptions
): Promise<string> {
  // Handle overloaded parameters
  let resolver: Resolver | undefined;
  let options: TLinkOptions | undefined;

  if (resolverOrOptions && "getPages" in resolverOrOptions) {
    resolver = resolverOrOptions;
    options = linkOptions;
  } else {
    options = resolverOrOptions as TLinkOptions;
  }

  // If no resolver provided, return original url (backward compatibility)
  if (!resolver) {
    return url;
  }

  const wpUrl = resolver.getClient().getConfig().apiUrl;

  if (!wpUrl) return url;

  let relativePath = url.replace(wpUrl, "");

  if (relativePath.startsWith("/")) {
    relativePath = relativePath.substring(1);
  }

  if (SKIP_PATHS.some((path) => relativePath.startsWith(path))) {
    return url;
  }

  const permalinkInfo = await getPermalinkInfo(resolver, url, relativePath);

  // Get templates from resolver if provided, otherwise fallback to empty array
  let templates: TWpTemplate[];

  if (resolver) {
    templates = resolver.getPages();
  } else {
    // Fallback: when no resolver is provided, use empty array
    // This maintains backward compatibility but templates won't be resolved
    templates = [];
  }

  if (!permalinkInfo) {
    return url;
  }

  const resolvedPath = resolveLinkFromInfo(permalinkInfo, templates, options);

  return resolvedPath || url;
}

export async function resolveLinksInHtmlStr(
  content: string,
  resolver: Resolver,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLinksInHtmlStr(
  content: string,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLinksInHtmlStr(
  content: string,
  resolverOrOptions?: Resolver | TLinkOptions,
  linkOptions?: TLinkOptions
): Promise<string> {
  // Handle overloaded parameters
  let resolver: Resolver | undefined;
  let options: TLinkOptions | undefined;

  if (resolverOrOptions && "getPages" in resolverOrOptions) {
    resolver = resolverOrOptions;
    options = linkOptions;
  } else {
    options = resolverOrOptions as TLinkOptions;
  }

  // If no resolver provided, return original content (backward compatibility)
  if (!resolver) {
    return content;
  }

  const wpUrl = resolver.getClient().getConfig().apiUrl;

  if (!wpUrl) return content;

  const pattern = /(['"])(https?:\/\/[^'"]+)(['"])/g;
  const matches = [...content.matchAll(pattern)];

  const replacements = matches.map(async (match) => {
    const [fullMatch, p1, link, p3] = match;
    let resolvedLink: string;

    if (link.startsWith(wpUrl)) {
      if (resolver) {
        resolvedLink = await resolveLink(link, resolver, options);
      } else {
        resolvedLink = await resolveLink(link, options);
      }
    } else {
      resolvedLink = link;
    }

    return { match, fullMatch, resolvedLink, p1, p3 };
  });

  const resolved = await Promise.all(replacements);

  let newContent = "";
  let lastIndex = 0;

  for (const { match, fullMatch, resolvedLink, p1, p3 } of resolved) {
    newContent += content.slice(lastIndex, match.index);
    newContent += p1 + resolvedLink + p3;
    lastIndex = match.index! + fullMatch.length;
  }

  newContent += content.slice(lastIndex);

  return newContent;
}

export async function resolveLinksInString(
  str: string,
  resolver: Resolver,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLinksInString(
  str: string,
  linkOptions?: TLinkOptions
): Promise<string>;
export async function resolveLinksInString(
  str: string,
  resolverOrOptions?: Resolver | TLinkOptions,
  linkOptions?: TLinkOptions
): Promise<string> {
  // Handle overloaded parameters
  let resolver: Resolver | undefined;
  let options: TLinkOptions | undefined;

  if (resolverOrOptions && "getPages" in resolverOrOptions) {
    resolver = resolverOrOptions;
    options = linkOptions;
  } else {
    options = resolverOrOptions as TLinkOptions;
  }

  // If no resolver provided, just return the string (backward compatibility)
  if (!resolver) {
    return str;
  }

  const wpUrl = resolver.getClient().getConfig().apiUrl;

  if (str.startsWith(wpUrl)) {
    return resolveLink(str, resolver, options);
  }

  return resolveLinksInHtmlStr(str, resolver, options);
}

export function checkForLinksInString(
  str: string,
  resolver?: Resolver
): boolean {
  if (!resolver) {
    return false; // Can't check without resolver (backward compatibility)
  }

  const wpUrl = resolver.getClient().getConfig().apiUrl;

  return typeof str === "string" && str.includes(wpUrl);
}
