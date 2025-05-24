import { TWpTemplate, WpPageType } from '../../plugin/types';
import { wpGetUrl, wpPluginGet } from '../wordpress';
import { TPermalinkInfo } from './types.ts';

const SKIP_PATHS = ['wp-admin', 'wp-content', 'wp-json'];

type TLinkOptions = {
  absolute?: boolean;
  hostname?: string;
};

export async function getPermalinkInfo(
  url: string,
  relativePath: string,
): Promise<TPermalinkInfo | undefined> {
  const wpUrl = wpGetUrl();

  const cacheKey = relativePath.replace(/\//g, '-').slice(0, -1);
  const res = await wpPluginGet<TPermalinkInfo>(
    wpUrl,
    'permalink-info',
    {
      url,
    },
    ['permalinks', `perma-${cacheKey}`],
  );

  return res;
}

export function resolveLinkFromInfo(
  permalinkInfo: TPermalinkInfo,
  templates: TWpTemplate[],
  linkOptions?: TLinkOptions,
): string | undefined {
  let matchedPath;

  if (!permalinkInfo) return matchedPath;

  switch (permalinkInfo.object_type) {
    case 'post': {
      const { name, post_type } = permalinkInfo.details;
      const postTypeTemplates = templates.filter(
        (t) => t.type === WpPageType.POST_TYPE && t.name === post_type,
      );
      const singleSpecific = postTypeTemplates.find(
        (t) =>
          t.type === WpPageType.POST_TYPE &&
          t.template === 'SINGLE_SPECIFIC' &&
          t.slug === name,
      );

      if (singleSpecific) {
        matchedPath = singleSpecific.path;
      } else {
        const singleAny = postTypeTemplates.find(
          (t) => t.template === 'SINGLE_ANY',
        );

        if (singleAny) matchedPath = singleAny.path.replace('[slug]', name);
        else {
          const archive = postTypeTemplates.find(
            (t) => t.template === 'ARCHIVE',
          );
          if (archive) matchedPath = archive.path;
        }
      }
      break;
    }
    case 'taxonomy': {
      const { name } = permalinkInfo.details;
      const taxTemplates = templates.filter(
        (t) => t.type === 'taxonomy' && t.name === name,
      );
      const archive = taxTemplates.find((t) => t.template === 'ARCHIVE');

      if (archive) matchedPath = archive.path;
      break;
    }
    case 'taxonomy_term': {
      const { name, taxonomy_name } = permalinkInfo.details;
      const taxTemplates = templates.filter(
        (t) => t.type === 'taxonomy' && t.name === taxonomy_name,
      );
      const singleAny = taxTemplates.find((t) => t.template === 'SINGLE_ANY');

      if (singleAny) matchedPath = singleAny.path.replace('[slug]', name);

      break;
    }
    default:
      break;
  }

  if (!matchedPath) {
    matchedPath = '/';
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
  linkOptions?: TLinkOptions,
): Promise<string> {
  const wpUrl = wpGetUrl();

  if (!wpUrl) return url;

  let relativePath = url.replace(wpUrl, '');

  if (relativePath.startsWith('/')) {
    relativePath = relativePath.substring(1);
  }

  if (SKIP_PATHS.some((path) => relativePath.startsWith(path))) {
    return url;
  }

  const permalinkInfo = await getPermalinkInfo(url, relativePath);
  const { templates } = await import('clutch/wp-templates.json');

  return resolveLinkFromInfo(permalinkInfo, templates, linkOptions);
}

export async function resolveLinksInHtmlStr(
  content: string,
  linkOptions?: TLinkOptions,
): Promise<string> {
  const wpUrl = wpGetUrl();
  if (!wpUrl) return content;

  const pattern = /(['"])(https?:\/\/[^'"]+)(['"])/g;
  const matches = [...content.matchAll(pattern)];

  const replacements = matches.map(async (match) => {
    const [fullMatch, p1, link, p3] = match;
    const resolvedLink = link.startsWith(wpUrl)
      ? await resolveLink(link, linkOptions)
      : link;
    return { match, fullMatch, resolvedLink, p1, p3 };
  });

  const resolved = await Promise.all(replacements);

  let newContent = '';
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
  linkOptions?: TLinkOptions,
): Promise<string> {
  const wpUrl = wpGetUrl();

  if (str.startsWith(wpUrl)) {
    return resolveLink(str, linkOptions);
  }

  return resolveLinksInHtmlStr(str, linkOptions);
}

export function checkForLinksInString(str: string) {
  const wpUrl = wpGetUrl();

  return typeof str === 'string' && str.includes(wpUrl);
}
