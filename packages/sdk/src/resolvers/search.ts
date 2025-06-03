import { WP_REST_API_Search_Result } from 'wp-types';
import { SearchResut } from '../types';
import { Resolver } from './resolver';

export async function resolveSearchResult(
  searchResult: WP_REST_API_Search_Result,
  resolver: Resolver
): Promise<SearchResut | undefined> {
  const client = resolver.getClient();

  const { id, type } = searchResult;
  const restLink = searchResult._links?.['self']?.[0]?.href;
  const restBase = restLink?.split('/wp/v2/')?.[1]?.split('/')[0];

  if (type === 'post') {
    return client.fetchPostById(restBase, id, false, resolver) as Promise<
      SearchResut | undefined
    >;
  }

  if (type === 'term') {
    return client.fetchTaxonomyTermById(
      restBase,
      id,
      false,
      resolver
    ) as Promise<SearchResut | undefined>;
  }

  return undefined;
}

export async function resolveSearchResults(
  searchResults: WP_REST_API_Search_Result[],
  resolver: Resolver
): Promise<SearchResut[]> {
  const resolvedResults = await Promise.all(
    searchResults.map(result => resolveSearchResult(result, resolver))
  );

  return resolvedResults.filter(res => !!res);
}
