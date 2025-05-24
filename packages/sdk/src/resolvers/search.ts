import { WP_REST_API_Search_Result } from 'wp-types';
import { fetchPostById } from '../fetchers/posts';
import { fetchTaxonomyTermById } from '../fetchers/taxonomies';
import { Resolver } from './resolver';
import { SearchResut } from './types';

export async function resolveSearchResult(
  searchResult: WP_REST_API_Search_Result,
  resolver: Resolver,
): Promise<SearchResut | undefined> {
  const { id, type } = searchResult;
  const restLink = searchResult._links?.['self']?.[0]?.href;
  const restBase = restLink?.split('/wp/v2/')?.[1]?.split('/')[0];

  if (type === 'post') {
    return fetchPostById(restBase, id, false, resolver);
  }

  if (type === 'term') {
    return fetchTaxonomyTermById(restBase, id, false, resolver);
  }

  return undefined;
}

export async function resolveSearchResults(
  searchResults: WP_REST_API_Search_Result[],
  resolver: Resolver,
): Promise<SearchResut[]> {
  const resolvedResults = await Promise.all(
    searchResults.map((result) => resolveSearchResult(result, resolver)),
  );

  return resolvedResults.filter((res) => !!res);
}
