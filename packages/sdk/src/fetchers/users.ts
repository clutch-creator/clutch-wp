import { WP_REST_API_User, WP_REST_API_Users } from 'wp-types';
import { Resolver } from '../resolvers/resolver';
import { UserResult } from '../resolvers/types';
import { resolveUser, resolveUsers } from '../resolvers/users';
import { WPIdFilter } from '../types';
import { wpGet } from '../wordpress';

export async function fetchUsers(
  args: any,
  _resolver?: Resolver,
): Promise<UserResult[]> {
  const resolver = _resolver || new Resolver();
  const users = await wpGet<WP_REST_API_Users>('users', args, ['users']);

  return resolveUsers(users, resolver);
}

export async function fetchUserBySlug(
  slug: string,
  _resolver?: Resolver,
): Promise<UserResult | null> {
  if (!slug) return null;

  const resolver = _resolver || new Resolver();

  const existingPromise = resolver.getAssetPromise('users', slug);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise('users', slug, async () => {
    const users = await wpGet<WP_REST_API_Users>('users', { slug }, [
      `users-${slug}`,
    ]);

    if (users?.length) {
      return resolveUser(users[0], resolver);
    }

    return null;
  });
}

export async function fetchUserById(
  id: WPIdFilter,
  _resolver?: Resolver,
): Promise<UserResult | null> {
  if (!id) return null;

  const resolver = _resolver || new Resolver();

  const existingPromise = resolver.getAssetPromise('users', id);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise('users', id, async () => {
    const user = await wpGet<WP_REST_API_User>(`users/${id}`, {}, [
      `users-${id}`,
    ]);

    if (user) {
      return resolveUser(user, resolver);
    }

    return null;
  });
}
