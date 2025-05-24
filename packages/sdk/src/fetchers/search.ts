import { WP_REST_API_Search_Results } from 'wp-types';
import { Resolver } from '../resolvers/resolver';
import { resolveSearchResults } from '../resolvers/search';
import { SearchResut } from '../resolvers/types';
import { wpGet } from '../wordpress';

export async function fetchSearchResults(
  args: any,
  _resolver?: Resolver,
): Promise<SearchResut[]> {
  const resolver = _resolver || new Resolver();
  const results = await wpGet<WP_REST_API_Search_Results>('search', args, []);

  return resolveSearchResults(results, resolver);
}
