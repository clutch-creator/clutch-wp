import {
  WP_REST_API_Attachment,
  WP_REST_API_Post,
  WP_REST_API_Term,
  WP_REST_API_User,
} from 'wp-types';

export type UserResult = Omit<
  WP_REST_API_User,
  'link' | '_embedded' | '_links'
> & {
  link: string;
  url: string;
};

export type TaxonomyTermResult = Omit<
  WP_REST_API_Term,
  'link' | '_embedded' | '_links'
> & {
  link: string;
};

export type TMediaResult = Omit<
  WP_REST_API_Attachment,
  | 'author'
  | 'date'
  | 'date_gmt'
  | 'modified'
  | 'modified_gmt'
  | '_embedded'
  | '_links'
> & {
  author: UserResult;
  date: Date;
  modified: Date;
  source_url: string;
};

export type PostResult = Omit<
  WP_REST_API_Post,
  | 'author'
  | 'categories'
  | 'featured_media'
  | 'tags'
  | 'title'
  | 'content'
  | 'excerpt'
  | 'link'
  | 'date'
  | 'date_gmt'
  | 'modified'
  | 'modified_gmt'
  | '_embedded'
  | '_links'
> & {
  author: UserResult;
  categories: TaxonomyTermResult[];
  featured_media: TMediaResult;
  tags: TaxonomyTermResult[];
  title: string;
  content: string;
  excerpt: string;
  link: string;
  date: Date;
  modified: Date;
};

export type PostsResult = {
  posts: PostResult[];
  total_count: number;
  total_pages: number;
};

export type TSeo = any;

export type PostsRestResult = {
  posts: WP_REST_API_Post[];
  total_count: number;
  total_pages: number;
  seo?: TSeo;
};

export type PostRestResult = WP_REST_API_Post;

export type TermRestResult = WP_REST_API_Term;

export type TermsRestResult = {
  terms: WP_REST_API_Term[];
  total_count: number;
  total_pages: number;
  seo?: TSeo;
};

export type TermsResult = {
  terms: TaxonomyTermResult[];
  total_count: number;
  total_pages: number;
};

export type SearchResut = PostResult | TaxonomyTermResult;

type TPermalinkUnkownInfo = {
  object_type: 'unknown';
};

type TPermalinkPostInfo = {
  object_type: 'post';
  details: {
    id: number;
    name: string;
    post_type: string;
    rest_base: string;
    rest_namespace: string;
  };
};

type TPermalinkTaxonomyInfo = {
  object_type: 'taxonomy';
  details: {
    name: string;
    rest_base: string;
    rest_namespace: string;
  };
};

type TPermalinkTaxonomyTermInfo = {
  object_type: 'taxonomy_term';
  details: {
    id: number;
    name: string;
    taxonomy_name: string;
    rest_base: string;
    rest_namespace: string;
  };
};

export type TPermalinkInfo =
  | TPermalinkUnkownInfo
  | TPermalinkPostInfo
  | TPermalinkTaxonomyInfo
  | TPermalinkTaxonomyTermInfo;

export type MenuItemResponse = {
  id: number;
  title: string;
  url: string;
  url_info: TPermalinkInfo;
  children: MenuItemResponse[];
};

export type MenuLocationResponse = {
  id: string;
  name: string;
  menu: {
    id: number;
    name: string;
    slug: string;
  } | null;
};

export type MenuResponse = {
  id: number;
  name: string;
  slug: string;
  items: MenuItemResponse[];
};

export type MenuItemResult = {
  id: number;
  title: string;
  url: string;
  children: MenuItemResult[];
};

export type MenuResult = {
  id: number;
  name: string;
  slug: string;
  items: MenuItemResult[];
};
