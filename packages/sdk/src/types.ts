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
