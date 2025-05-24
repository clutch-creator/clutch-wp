import { resolveMenu } from '../resolvers/menus';
import { Resolver } from '../resolvers/resolver.ts';
import { MenuResponse, MenuResult } from '../resolvers/types.ts';
import { WPIdFilter } from '../types.ts';
import { wpGetUrl, wpPluginGet } from '../wordpress.ts';

export async function fetchMenuById(
  id: WPIdFilter,
  _resolver?: Resolver,
): Promise<MenuResult | null> {
  const wpUrl = wpGetUrl();

  if (!id || !wpUrl) return null;

  const resolver = _resolver || new Resolver();

  // check if resolver is already resolving/resolved this resource
  const existingPromise = resolver.getAssetPromise('menus', id);

  if (existingPromise) return existingPromise;

  return resolver.addAssetPromise('menus', id, async () => {
    const menu = await wpPluginGet<MenuResponse>(wpUrl, `menus/${id}`, {}, [
      `menus-${id}`,
    ]);

    if (menu) {
      return resolveMenu(menu, resolver);
    }

    return null;
  });
}
