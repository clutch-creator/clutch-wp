export enum PluginSetting {
  CONTROLLED = 'pluginControlled',
  WP_TEMPLATE = 'wpTemplate',
}

export enum PluginEnv {
  WORDPRESS_URL = 'WORDPRESS_URL',
  ISR_TOKEN = 'ISR_TOKEN',
  CACHE_DISABLED = 'WORDPRESS_CACHE_DISABLED',
  DRAFT_MODE = 'WORDPRESS_DRAFT_MODE',
  WP_AUTH_TOKEN = 'WORDPRESS_AUTH_TOKEN',
}

export enum FilterOperator {
  EQ = 'eq',
  NEQ = 'neq',
  LT = 'lt',
  LTE = 'lte',
  GT = 'gt',
  GTE = 'gte',
  LIKE = 'like',
  NOT_LIKE = 'not_like',
  CONTAINS = 'contains',
  IN = 'in',
  NIN = 'nin',
  BETWEEN = 'between',
  EXISTS = 'exists',
  NOT_EXISTS = 'not_exists',
}

export const FILTER_OPERATORS = Object.values(FilterOperator);
