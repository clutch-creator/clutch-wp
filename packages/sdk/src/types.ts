export * from './types/pages';
export * from './types/plugin-info';
export * from './types/wordpress';

export type WPId = number;

export type WPIdFilter = string | number;

export type TClutchPostType = {
  name: string;
  description: string;
  label: string;
  singular_label: string;
  rewrite: string | boolean;
  menu_icon: string | undefined;
  rest_base: string;
  rest_namespace: string;
  first_post_slug: string | undefined;
};

export type TClutchTaxonomyType = {
  name: string;
  description: string;
  label: string;
  singular_label: string;
  rest_base: string;
  rest_namespace: string;
  first_term_slug: string | undefined;
};

export type TClutchPluginInfo = {
  name: string;
  version: string;
  uri: string;
};

export type TFrontPageInfoError = {
  message: string;
};

export type TFrontPageInfoSuccess = {
  id: number;
  title: string;
  slug: string;
};

export type TFrontPageInfo = TFrontPageInfoError | TFrontPageInfoSuccess;

export type TTaxonomiesFilter = {
  [taxonomy: string]: (string | number)[];
};

export type TFilter = {
  name: string;
  operator: string;
  value: string | number | boolean | (string | number)[];
};

export type TFilters = TFilter[];

export type TParams = {
  [key: string]: string | number | boolean | string[] | number[] | unknown;
};

// Fetch function argument types
export type FetchPostsArgs = {
  post_type?: string;
  page?: number | string;
  per_page?: number | string;
  order?: 'asc' | 'desc';
  order_by?: string;
  seo?: boolean;
  [key: string]: unknown;
};

export type FetchUsersArgs = {
  page?: number | string;
  per_page?: number | string;
  search?: string;
  exclude?: WPIdFilter[];
  include?: WPIdFilter[];
  offset?: number | string;
  orderby?: string;
  order?: 'asc' | 'desc';
  slug?: string;
  roles?: string[];
  [key: string]: unknown;
};

export type FetchTaxonomyTermsArgs = {
  taxonomy?: string;
  page?: number | string;
  per_page?: number | string;
  search?: string;
  exclude?: WPIdFilter[];
  include?: WPIdFilter[];
  order?: 'asc' | 'desc';
  orderby?: string;
  hide_empty?: boolean | string;
  parent?: number | string;
  post?: number | string;
  slug?: string | string[];
  [key: string]: unknown;
};

export type FetchSearchArgs = {
  search?: string;
  type?: string;
  subtype?: string;
  page?: number | string;
  per_page?: number | string;
  [key: string]: unknown;
};
