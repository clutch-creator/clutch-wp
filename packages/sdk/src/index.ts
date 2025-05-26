// Main client export
export { WordPressHttpClient, type WordPressClientConfig } from "./client";

// Types exports
export type {
  MenuResult,
  PostResult,
  PostsResult,
  SearchResut,
  TaxonomyTermResult,
  TermsResult,
  TMediaResult,
  TSeo,
  UserResult,
} from "./resolvers/types";

export type {
  FetchPostsArgs,
  FetchSearchArgs,
  FetchTaxonomyTermsArgs,
  FetchUsersArgs,
  TFilter,
  TFilters,
  WPIdFilter,
} from "./types";

// Resolver export for advanced usage
export { Resolver } from "./resolvers/resolver";

// WordPress utility functions
export {
  getProcessedParams,
  urlJoin,
  wpApiGet,
  wpIsValidUrl,
  type TParams,
} from "./wordpress";
