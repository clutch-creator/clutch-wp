import { WP_REST_API_Term, WP_REST_API_Terms } from 'wp-types';
import { resolveClutchFields } from './clutch-nodes';
import { Resolver } from './resolver';
import { TaxonomyTermResult } from './types';

export async function resolveTaxonomyTerm(
  term: WP_REST_API_Term,
  resolver: Resolver,
): Promise<TaxonomyTermResult> {
  const draftTerm: Partial<TaxonomyTermResult> = { ...term };

  // Resolve all clutch nodes
  resolver.waitPromise(resolveClutchFields(draftTerm, resolver));

  await resolver.waitAll();

  return draftTerm as TaxonomyTermResult;
}

export async function resolveTaxonomyTerms(
  terms: WP_REST_API_Terms | undefined,
  resolver: Resolver,
): Promise<TaxonomyTermResult[]> {
  if (!terms?.length) return [];

  return Promise.all(terms.map((term) => resolveTaxonomyTerm(term, resolver)));
}
