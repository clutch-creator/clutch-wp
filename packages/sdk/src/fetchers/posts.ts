import { resolveClutchFields } from '../resolvers/clutch-nodes';
import { resolvePost, resolvePosts } from '../resolvers/posts';
import { Resolver } from '../resolvers/resolver';
import {
  PostRestResult,
  PostResult,
  PostsRestResult,
  PostsResult,
} from '../resolvers/types';
import { WPIdFilter } from '../types';
import { wpGetUrl, wpPluginGet } from '../wordpress';

export async function fetchPosts(
  args: any,
  _resolver?: Resolver,
): Promise<PostsResult> {
  const resolver = _resolver || new Resolver();
  const url = wpGetUrl();
  const headers = await resolver.getHeaders();
  const postType = args.post_type || 'post';

  if (!url)
    return {
      posts: [],
      total_count: 0,
      total_pages: 0,
    };

  const postsResponse = await wpPluginGet<PostsRestResult>(
    url,
    'posts',
    args,
    [postType],
    headers,
  );

  const resPosts = await resolvePosts(postsResponse.posts, resolver);

  const res = {
    ...postsResponse,
    posts: resPosts,
  };

  // resolve nodes in seo
  if (res.seo) {
    await resolver.waitPromise(resolveClutchFields(res.seo, resolver));
  }

  return res;
}

export async function fetchPostBySlug(
  postType: string = 'post',
  slug: string,
  includeSeo: boolean = false,
  _resolver?: Resolver,
): Promise<PostResult | null> {
  const url = wpGetUrl();

  if (!slug || !url) return null;

  const resolver = _resolver || new Resolver();

  // check if resolver is already resolving/resolved this resource
  const existingPromise = resolver.getAssetPromise(postType, slug);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise(postType, slug, async () => {
    const headers = await resolver.getHeaders();
    const postResponse = await wpPluginGet<PostRestResult>(
      url,
      'post',
      {
        slug,
        seo: includeSeo,
      },
      [`${postType}-${slug}`],
      headers,
    );

    if (postResponse) {
      return resolvePost(postResponse, resolver);
    }

    return null;
  });
}

export async function fetchPostById(
  postType: string = 'post',
  id: WPIdFilter,
  includeSeo: boolean = false,
  _resolver?: Resolver,
): Promise<PostResult | null> {
  const url = wpGetUrl();

  if (!id || !url) return null;

  const resolver = _resolver || new Resolver();

  // check if resolver is already resolving/resolved this resource
  const existingPromise = resolver.getAssetPromise(postType, id);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise(postType, id, async () => {
    const headers = await resolver.getHeaders();
    const postResponse = await wpPluginGet<PostRestResult>(
      url,
      'post',
      {
        id,
        seo: includeSeo,
      },
      [`${postType}-${id}`],
      headers,
    );

    if (postResponse) {
      return resolvePost(postResponse, resolver);
    }

    return null;
  });
}
