import { TWpTemplateList } from '../../plugin/types';
import { resolveLinkFromInfo } from './links';
import { Resolver } from './resolver';
import {
  MenuItemResponse,
  MenuItemResult,
  MenuResponse,
  MenuResult,
} from './types';

export function resolveMenuItem(
  menuItemResponse: MenuItemResponse,
  templates: TWpTemplateList,
): MenuItemResult {
  const { url_info, children, ...menu } = menuItemResponse;
  const draftMenu: MenuItemResult = {
    ...menu,
    url: resolveLinkFromInfo(url_info, templates),
    children: children.map((child) => resolveMenuItem(child, templates)),
  };

  return draftMenu;
}

export async function resolveMenu(
  menuResponse: MenuResponse,
  resolver: Resolver,
): Promise<MenuResult> {
  const templates = await resolver.getTemplates();

  const draftMenu: MenuResult = {
    ...menuResponse,
    items: menuResponse.items.map((item) => resolveMenuItem(item, templates)),
  };

  await resolver.waitAll();

  return draftMenu as MenuResponse;
}

export async function resolveMenus(
  menus: MenuResponse[] | undefined,
  resolver: Resolver,
): Promise<MenuResult[]> {
  if (!menus?.length) return [];

  return Promise.all(menus.map((menu) => resolveMenu(menu, resolver)));
}
