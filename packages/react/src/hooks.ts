import {
  MenuLocationResponse,
  TClutchPostType,
  TClutchTaxonomyType,
  TFrontPageInfo,
  WordPressHttpClient,
  type FetchPostsArgs,
  type FetchSearchArgs,
  type FetchTaxonomyTermsArgs,
  type FetchUsersArgs,
  type MenuResult,
  type PostResult,
  type PostsResult,
  type TaxonomyTermResult,
  type TermsResult,
  type UserResult,
  type WPIdFilter,
} from "@clutch-wp/sdk";
import {
  useQuery,
  useQueryClient,
  type UseQueryOptions,
} from "@tanstack/react-query";
import { useCallback, useContext } from "react";
import { WordPressContext, type WordPressContextValue } from "./context";

// Query key factories for consistent caching
const queryKeys = {
  all: ["wordpress"],
  posts: (args: FetchPostsArgs) => [...queryKeys.all, "posts", args],
  postBySlug: (postType: string, slug: string, includeSeo: boolean) => [
    ...queryKeys.all,
    "post",
    "slug",
    postType,
    slug,
    includeSeo,
  ],
  postById: (postType: string, id: string | number, includeSeo: boolean) => [
    ...queryKeys.all,
    "post",
    "id",
    postType,
    id,
    includeSeo,
  ],
  users: (args: FetchUsersArgs) => [...queryKeys.all, "users", args],
  userBySlug: (slug: string) => [...queryKeys.all, "user", "slug", slug],
  userById: (id: string | number) => [...queryKeys.all, "user", "id", id],
  taxonomyTerms: (args: FetchTaxonomyTermsArgs) => [
    ...queryKeys.all,
    "taxonomy-terms",
    args,
  ],
  taxonomyTermBySlug: (taxonomy: string, slug: string, includeSeo: boolean) => [
    ...queryKeys.all,
    "taxonomy-term",
    "slug",
    taxonomy,
    slug,
    includeSeo,
  ],
  taxonomyTermById: (
    taxonomy: string,
    id: string | number,
    includeSeo: boolean
  ) => [...queryKeys.all, "taxonomy-term", "id", taxonomy, id, includeSeo],
  search: (args: FetchSearchArgs) => [...queryKeys.all, "search", args],
  menu: (id: WPIdFilter) => [...queryKeys.all, "menu", id],
  draftMode: () => [...queryKeys.all, "draft-mode"],
};

/**
 * Hook to access the WordPress client context
 * @throws Error if used outside of WordPressProvider
 */
export function useWordPress(): WordPressContextValue {
  const context = useContext(WordPressContext);

  if (!context) {
    throw new Error("useWordPress must be used within a WordPressProvider");
  }

  return context;
}

/**
 * Hook to access the WordPress HTTP client directly
 */
export function useWordPressClient(): WordPressHttpClient {
  const { client } = useWordPress();

  return client;
}

/**
 * Hook to check if WordPress is connected
 */
export function useIsConnected(): boolean {
  const { isConnected } = useWordPress();

  return isConnected;
}

/**
 * Hook to fetch WordPress posts with built-in loading and error states
 * @param args - Configuration for fetching posts including filters, pagination, and sorting options
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePosts(
  args: FetchPostsArgs,
  options?: UseQueryOptions<PostsResult, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.posts(args),
    queryFn: () => client.fetchPosts(args),
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress post by slug
 * @param postType - The WordPress post type to query (defaults to "post")
 * @param slug - The unique slug identifier for the post
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePostBySlug(
  postType: string = "post",
  slug: string,
  includeSeo: boolean = false,
  options?: UseQueryOptions<PostResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.postBySlug(postType, slug, includeSeo),
    queryFn: () => client.fetchPostBySlug(postType, slug, includeSeo),
    enabled: !!slug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress post by ID
 * @param postType - The WordPress post type to query (defaults to "post")
 * @param id - The unique numeric or string ID for the post
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePostById(
  postType: string = "post",
  id: string | number,
  includeSeo: boolean = false,
  options?: UseQueryOptions<PostResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.postById(postType, id, includeSeo),
    queryFn: () => client.fetchPostById(postType, id, includeSeo),
    enabled: !!id && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress post by either slug or ID
 * @param postType - The WordPress post type to query (defaults to "post")
 * @param identifier - Whether to search by "slug" or "id"
 * @param idOrSlug - The slug or ID value to search for
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePost(
  postType: string = "post",
  identifier: "slug" | "id",
  idOrSlug: string | number,
  includeSeo: boolean = false,
  options?: UseQueryOptions<PostResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.postById(postType, idOrSlug, includeSeo),
    queryFn: () =>
      identifier === "id"
        ? client.fetchPostById(postType, idOrSlug, includeSeo)
        : client.fetchPostBySlug(postType, idOrSlug.toString(), includeSeo),
    enabled: !!idOrSlug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch all available WordPress post types
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePostTypes(
  options?: UseQueryOptions<TClutchPostType[] | undefined, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "post-types"],
    queryFn: () => client.fetchPostTypes(),
    ...options,
  });
}

/**
 * Hook to fetch a specific WordPress post type configuration
 * @param postType - The name of the post type to retrieve
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function usePostType(
  postType: string,
  options?: UseQueryOptions<TClutchPostType | undefined, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "post-type", postType],
    queryFn: () => client.fetchPostType(postType),
    enabled: !!postType && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch WordPress users with filtering and pagination
 * @param args - Configuration for fetching users including filters and pagination options
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useUsers(
  args: FetchUsersArgs,
  options?: UseQueryOptions<UserResult[], Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.users(args),
    queryFn: () => client.fetchUsers(args),
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress user by slug
 * @param slug - The unique slug identifier for the user
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useUserBySlug(
  slug: string,
  options?: UseQueryOptions<UserResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.userBySlug(slug),
    queryFn: () => client.fetchUserBySlug(slug),
    enabled: !!slug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress user by ID
 * @param id - The unique numeric or string ID for the user
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useUserById(
  id: string | number,
  options?: UseQueryOptions<UserResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.userById(id),
    queryFn: () => client.fetchUserById(id),
    enabled: !!id && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single WordPress user by either slug or ID
 * @param identifier - Whether to search by "slug" or "id"
 * @param idOrSlug - The slug or ID value to search for
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useUser(
  identifier: "slug" | "id",
  idOrSlug: string | number,
  options?: UseQueryOptions<UserResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.userById(idOrSlug),
    queryFn: () =>
      identifier === "id"
        ? client.fetchUserById(idOrSlug)
        : client.fetchUserBySlug(idOrSlug.toString()),
    enabled: !!idOrSlug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch all available WordPress taxonomies
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomies(
  options?: UseQueryOptions<TClutchTaxonomyType[], Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "taxonomies"],
    queryFn: () => client.fetchTaxonomies(),
    ...options,
  });
}

/**
 * Hook to fetch a specific WordPress taxonomy configuration
 * @param taxonomy - The name of the taxonomy to retrieve
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomy(
  taxonomy: string,
  options?: UseQueryOptions<TClutchTaxonomyType | undefined, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "taxonomy", taxonomy],
    queryFn: () => client.fetchTaxonomy(taxonomy),
    enabled: !!taxonomy && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch taxonomy terms with filtering and pagination
 * @param args - Configuration for fetching terms including taxonomy, filters, and pagination options
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomyTerms(
  args: FetchTaxonomyTermsArgs,
  options?: UseQueryOptions<TermsResult, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.taxonomyTerms(args),
    queryFn: () => client.fetchTaxonomyTerms(args),
    ...options,
  });
}

/**
 * Hook to fetch a single taxonomy term by slug
 * @param taxonomy - The taxonomy the term belongs to
 * @param slug - The unique slug identifier for the term
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomyTermBySlug(
  taxonomy: string,
  slug: string,
  includeSeo: boolean = false,
  options?: UseQueryOptions<TaxonomyTermResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.taxonomyTermBySlug(taxonomy, slug, includeSeo),
    queryFn: () => client.fetchTaxonomyTermBySlug(taxonomy, slug, includeSeo),
    enabled: !!taxonomy && !!slug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single taxonomy term by ID
 * @param taxonomy - The taxonomy the term belongs to
 * @param id - The unique numeric or string ID for the term
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomyTermById(
  taxonomy: string,
  id: string | number,
  includeSeo: boolean = false,
  options?: UseQueryOptions<TaxonomyTermResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.taxonomyTermById(taxonomy, id, includeSeo),
    queryFn: () => client.fetchTaxonomyTermById(taxonomy, id, includeSeo),
    enabled: !!taxonomy && !!id && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch a single taxonomy term by either slug or ID
 * @param taxonomy - The taxonomy the term belongs to
 * @param identifier - Whether to search by "slug" or "id"
 * @param idOrSlug - The slug or ID value to search for
 * @param includeSeo - Whether to include SEO metadata in the response
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useTaxonomyTerm(
  taxonomy: string,
  identifier: "slug" | "id",
  idOrSlug: string | number,
  includeSeo: boolean = false,
  options?: UseQueryOptions<TaxonomyTermResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.taxonomyTermById(taxonomy, idOrSlug, includeSeo),
    queryFn: () =>
      identifier === "id"
        ? client.fetchTaxonomyTermById(taxonomy, idOrSlug, includeSeo)
        : client.fetchTaxonomyTermBySlug(
            taxonomy,
            idOrSlug.toString(),
            includeSeo
          ),
    enabled: !!taxonomy && !!idOrSlug && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to fetch all available WordPress menu locations
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useMenusLocations(
  options?: UseQueryOptions<MenuLocationResponse[], Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "menus", "locations"],
    queryFn: () => client.fetchMenusLocations(),
    ...options,
  });
}

/**
 * Hook to fetch a WordPress menu by ID or slug
 * @param id - The menu identifier (numeric ID or slug string)
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useMenu(
  id: WPIdFilter,
  options?: UseQueryOptions<MenuResult | null, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.menu(id),
    queryFn: () => client.fetchMenuById(id),
    enabled: !!id && options?.enabled !== false,
    ...options,
  });
}

/**
 * Hook to check if WordPress is currently in draft mode
 * Automatically refetches every minute to stay current
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useDraftMode(options?: UseQueryOptions<boolean, Error>) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: queryKeys.draftMode(),
    queryFn: () => client.isInDraftMode(),
    refetchInterval: 60 * 1000, // Refetch every minute
    ...options,
  });
}

/**
 * Hook to fetch WordPress front page configuration information
 * @param options - Additional React Query options for customizing cache behavior and query execution
 */
export function useFrontPageInfo(
  options?: UseQueryOptions<TFrontPageInfo | undefined, Error>
) {
  const client = useWordPressClient();

  return useQuery({
    queryKey: [...queryKeys.all, "front-page"],
    queryFn: () => client.fetchFrontPageInfo(),
    ...options,
  });
}

/**
 * Hook to access query utilities for cache management and prefetching
 * Provides methods to invalidate caches and prefetch data for better performance
 */
export function useWordPressQueries() {
  const queryClient = useQueryClient();
  const client = useWordPressClient();

  const invalidateAll = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: queryKeys.all });
  }, [queryClient]);

  const invalidatePosts = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: [...queryKeys.all, "posts"] });
  }, [queryClient]);

  const invalidateUsers = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: [...queryKeys.all, "users"] });
  }, [queryClient]);

  const prefetchPostBySlug = useCallback(
    async (postType: string, slug: string, includeSeo: boolean = false) => {
      await queryClient.prefetchQuery({
        queryKey: queryKeys.postBySlug(postType, slug, includeSeo),
        queryFn: () => client.fetchPostBySlug(postType, slug, includeSeo),
      });
    },
    [queryClient, client]
  );

  const prefetchPostById = useCallback(
    async (
      postType: string,
      id: string | number,
      includeSeo: boolean = false
    ) => {
      await queryClient.prefetchQuery({
        queryKey: queryKeys.postById(postType, id, includeSeo),
        queryFn: () => client.fetchPostById(postType, id, includeSeo),
      });
    },
    [queryClient, client]
  );

  const prefetchPosts = useCallback(
    async (args: FetchPostsArgs) => {
      await queryClient.prefetchQuery({
        queryKey: queryKeys.posts(args),
        queryFn: () => client.fetchPosts(args),
      });
    },
    [queryClient, client]
  );

  return {
    invalidateAll,
    invalidatePosts,
    invalidateUsers,
    prefetchPost: prefetchPostBySlug, // Keep for backward compatibility
    prefetchPostBySlug,
    prefetchPostById,
    prefetchPosts,
    queryClient,
  };
}
