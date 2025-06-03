import { WP_REST_API_User, WP_REST_API_Users } from 'wp-types';
import { UserResult } from '../types';
import { resolveClutchFields } from './clutch-nodes';
import { resolveLink } from './links';
import { Resolver } from './resolver';

const REMOVE_PROPS = ['_links', '_embedded'];

export async function resolveUser(
  user: WP_REST_API_User,
  resolver: Resolver
): Promise<UserResult> {
  const draftUser: Partial<UserResult> = { ...user };

  REMOVE_PROPS.forEach(prop => {
    delete draftUser[prop];
  });

  if (draftUser.link)
    resolver.waitUntil(async () => {
      if (draftUser.link)
        draftUser.link = await resolveLink(draftUser.link, resolver);
    });

  if (draftUser.url)
    resolver.waitUntil(async () => {
      if (draftUser.url)
        draftUser.url = await resolveLink(draftUser.url, resolver);
    });

  // Resolve all clutch nodes
  resolver.waitPromise(resolveClutchFields(draftUser, resolver));

  await resolver.waitAll();

  return draftUser as UserResult;
}

export async function resolveUsers(
  users: WP_REST_API_Users | undefined,
  resolver: Resolver
): Promise<UserResult[]> {
  if (!users?.length) return [];

  return Promise.all(users.map(user => resolveUser(user, resolver)));
}
