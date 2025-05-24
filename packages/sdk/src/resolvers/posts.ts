import { WP_REST_API_Post, WP_REST_API_Posts } from 'wp-types';
import { resolveClutchFields } from './clutch-nodes';
import { Resolver } from './resolver';
import { PostResult } from './types';

export async function resolvePost(
  post: WP_REST_API_Post,
  resolver: Resolver,
): Promise<PostResult> {
  const draftPost: Partial<PostResult> = { ...post };

  // Resolve all clutch nodes
  resolver.waitPromise(resolveClutchFields(draftPost, resolver));

  await resolver.waitAll();

  return draftPost as PostResult;
}

/**
 * Extract more info from post
 */
export async function resolvePosts(
  posts: WP_REST_API_Posts | undefined,
  resolver: Resolver,
): Promise<PostResult[]> {
  if (!posts?.length) return [];

  return Promise.all(posts.map((post) => resolvePost(post, resolver)));
}
