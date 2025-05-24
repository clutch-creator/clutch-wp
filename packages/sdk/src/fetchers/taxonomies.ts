import { resolveClutchFields } from '../resolvers/clutch-nodes';
import { Resolver } from '../resolvers/resolver';
import {
  resolveTaxonomyTerm,
  resolveTaxonomyTerms,
} from '../resolvers/taxonomies';
import {
  TaxonomyTermResult,
  TermRestResult,
  TermsRestResult,
  TermsResult,
} from '../resolvers/types';
import { WPIdFilter } from '../types';
import { wpGetUrl, wpPluginGet } from '../wordpress';

export async function fetchTaxonomyTerms(
  args: any,
  _resolver?: Resolver,
): Promise<TermsResult> {
  const resolver = _resolver || new Resolver();
  const url = wpGetUrl();
  const headers = await resolver.getHeaders();
  const taxonomy = args.taxonomy || 'category';

  if (!url)
    return {
      terms: [],
      total_count: 0,
      total_pages: 0,
    };

  const response = await wpPluginGet<TermsRestResult>(
    url,
    'terms',
    args,
    [taxonomy],
    headers,
  );
  const resTerms = await resolveTaxonomyTerms(response.terms, resolver);

  const res = {
    ...response,
    terms: resTerms,
  };

  // resolve nodes in seo
  if (res.seo) {
    await resolver.waitPromise(resolveClutchFields(res.seo, resolver));
  }

  return res;
}

export async function fetchTaxonomyTermBySlug(
  taxonomy: string,
  slug: string,
  includeSeo: boolean = false,
  _resolver?: Resolver,
): Promise<TaxonomyTermResult | null> {
  const url = wpGetUrl();

  if (!slug || !url) return null;

  const resolver = _resolver || new Resolver();

  const existingPromise = resolver.getAssetPromise(taxonomy, slug);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise(taxonomy, slug, async () => {
    const headers = await resolver.getHeaders();
    const term = await wpPluginGet<TermRestResult>(
      url,
      'term',
      {
        slug,
        taxonomy,
        seo: includeSeo,
      },
      [`${taxonomy}-${slug}`],
      headers,
    );

    return term ? await resolveTaxonomyTerm(term, resolver) : null;
  });
}

export async function fetchTaxonomyTermById(
  taxonomy: string,
  id: WPIdFilter,
  includeSeo: boolean = false,
  _resolver?: Resolver,
): Promise<TaxonomyTermResult | null> {
  const url = wpGetUrl();

  if (!id || !url) return null;

  const resolver = _resolver || new Resolver();

  const existingPromise = resolver.getAssetPromise(taxonomy, id);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise(taxonomy, id, async () => {
    const headers = await resolver.getHeaders();
    const term = await wpPluginGet<TermRestResult>(
      url,
      'term',
      {
        id,
        taxonomy,
        seo: includeSeo,
      },
      [`${taxonomy}-${id}`],
      headers,
    );

    return term ? await resolveTaxonomyTerm(term, resolver) : null;
  });
}
