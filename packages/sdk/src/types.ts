import { WpPageType, WpPageView } from "./statics.ts";

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
  page?: number;
  per_page?: number;
  search?: string;
  slug?: string;
  status?: string;
  author?: number;
  categories?: number[];
  tags?: number[];
  orderby?: string;
  order?: "asc" | "desc";
  filters?: TFilters;
  [key: string]: unknown;
};

export type FetchUsersArgs = {
  page?: number;
  per_page?: number;
  search?: string;
  exclude?: number[];
  include?: number[];
  offset?: number;
  orderby?: string;
  order?: "asc" | "desc";
  slug?: string;
  roles?: string[];
  [key: string]: unknown;
};

export type FetchTaxonomyTermsArgs = {
  taxonomy?: string;
  page?: number;
  per_page?: number;
  search?: string;
  exclude?: number[];
  include?: number[];
  order?: "asc" | "desc";
  orderby?: string;
  hide_empty?: boolean;
  parent?: number;
  post?: number;
  slug?: string;
  filters?: TFilters;
  [key: string]: unknown;
};

export type FetchSearchArgs = {
  search?: string;
  type?: string;
  subtype?: string;
  page?: number;
  per_page?: number;
  [key: string]: unknown;
};

export type TWpTemplateViewArchive = {
  template: WpPageView.ARCHIVE;
};

export type TWpTemplateViewSingleAny = {
  template: WpPageView.SINGLE_ANY;
  slug?: string;
};

export type TWpTemplateViewSingleSpecific = {
  template: WpPageView.SINGLE_SPECIFIC;
  slug?: string;
};

export type TWpTemplateView =
  | TWpTemplateViewArchive
  | TWpTemplateViewSingleAny
  | TWpTemplateViewSingleSpecific;

export type TWpTemplatePostType = {
  type: WpPageType.POST_TYPE;
  name: string;
  path: string;
} & TWpTemplateView;

export type TWpTemplateTaxonomy = {
  type: WpPageType.TAXONOMY;
  name: string;
  path: string;
} & TWpTemplateView;

export type TWpTemplateFrontPage = {
  type: WpPageType.FRONT_PAGE;
  path: string;
};

export type TWpTemplateNone = {
  type: WpPageType.NONE;
  path: string;
};

export type TWpTemplateAuthor = {
  type: WpPageType.AUTHOR;
  path: string;
};

export type TWpTemplateSearch = {
  type: WpPageType.SEARCH;
  path: string;
};

export type TWpTemplateNotFound = {
  type: WpPageType.NOT_FOUND;
  path: string;
};

export type TWpTemplate =
  | TWpTemplatePostType
  | TWpTemplateTaxonomy
  | TWpTemplateFrontPage
  | TWpTemplateNone
  | TWpTemplateAuthor
  | TWpTemplateSearch
  | TWpTemplateNotFound;

export type TWpTemplateList = TWpTemplate[];
