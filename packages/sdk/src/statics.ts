export enum PluginSetting {
  CONTROLLED = 'pluginControlled',
  WP_TEMPLATE = 'wpTemplate',
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

export enum WpPageType {
  POST_TYPE = 'post-type',
  TAXONOMY = 'taxonomy',
  FRONT_PAGE = 'front-page',
  SEARCH = 'search',
  NOT_FOUND = 'not-found',
  AUTHOR = 'author',
  NONE = 'none',
}

export enum WpPageView {
  ARCHIVE = 'ARCHIVE',
  SINGLE_ANY = 'SINGLE_ANY',
  SINGLE_SPECIFIC = 'SINGLE_SPECIFIC',
}
