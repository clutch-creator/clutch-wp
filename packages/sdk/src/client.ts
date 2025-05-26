import { WP_REST_API_Search_Results } from "wp-types";
import { resolveClutchFields } from "./resolvers/clutch-nodes";
import { resolveMenu } from "./resolvers/menus";
import { resolvePost, resolvePosts } from "./resolvers/posts";
import { Resolver } from "./resolvers/resolver";
import { resolveSearchResults } from "./resolvers/search";
import {
  resolveTaxonomyTerm,
  resolveTaxonomyTerms,
} from "./resolvers/taxonomies";
import {
  MenuResponse,
  MenuResult,
  PostRestResult,
  PostResult,
  PostsRestResult,
  PostsResult,
  SearchResut,
  TaxonomyTermResult,
  TermRestResult,
  TermsRestResult,
  TermsResult,
  UserResult,
} from "./resolvers/types";
import { resolveUser, resolveUsers } from "./resolvers/users";
import {
  FetchPostsArgs,
  FetchSearchArgs,
  FetchTaxonomyTermsArgs,
  FetchUsersArgs,
  WPIdFilter,
} from "./types";
import {
  getProcessedParams,
  TParams,
  urlJoin,
  wpApiGet,
  wpIsValidUrl,
} from "./wordpress";

export interface WordPressClientConfig {
  /** The WordPress site URL (e.g., https://example.com) */
  apiUrl: string;
  /** Optional authentication token */
  authToken?: string;
  /** Whether to disable caching (useful for development) */
  cacheDisabled?: boolean;
  /** Whether to enable draft mode */
  draftMode?: boolean;
  /** Custom headers to include with requests */
  headers?: Record<string, string>;
  /** Cache revalidation time in seconds (default: 3600 = 1 hour) */
  revalidate?: number;
}

export class WordPressHttpClient {
  private config: WordPressClientConfig;
  private defaultResolver: Resolver;

  constructor(config: WordPressClientConfig) {
    this.config = {
      cacheDisabled: false,
      draftMode: false,
      revalidate: 3600, // 1 hour default
      ...config,
    };
    this.defaultResolver = this.createResolver();
  }

  private createResolver(): Resolver {
    return new Resolver(this.config.authToken, this.config.draftMode);
  }

  private async wpPluginGet<T>(
    path: string,
    params: TParams,
    tags: string[] = [],
    headers?: Record<string, string>
  ): Promise<T | undefined> {
    const processedParams = getProcessedParams(params);

    try {
      const url = urlJoin(this.config.apiUrl, `/wp-json/clutch/v1/${path}`);
      const cache =
        this.config.cacheDisabled || this.config.draftMode
          ? "no-cache"
          : "default";

      const requestHeaders: Record<string, string> = {
        "Content-Type": "application/json",
        ...this.config.headers,
        ...headers,
      };

      if (this.config.authToken) {
        requestHeaders.Authorization = `Bearer ${this.config.authToken}`;
      }

      if (this.config.draftMode) {
        requestHeaders["X-Draft-Mode"] = "true";
      }

      // For environments that support Next.js features
      const fetchOptions: RequestInit & {
        next?: { tags: string[]; revalidate: number };
      } = {
        cache,
        headers: requestHeaders,
      };

      // Add Next.js specific options if available
      if (typeof window === "undefined" && this.config.revalidate) {
        fetchOptions.next = {
          tags: ["wordpress", ...tags],
          revalidate: this.config.revalidate,
        };
      }

      const response = await fetch(`${url}?${processedParams}`, fetchOptions);

      if (!response.ok) {
        throw new Error("Network response was not ok");
      }

      return response.json();
    } catch (err) {
      return undefined;
    }
  }

  private async wpGet<T>(
    path: string,
    params?: TParams,
    tags: string[] = [],
    headers?: Record<string, string>
  ): Promise<T | undefined> {
    const fetchOptions: RequestInit & {
      next?: { tags: string[]; revalidate: number };
    } = {
      cache:
        this.config.cacheDisabled || this.config.draftMode
          ? "no-cache"
          : "default",
      headers: {
        "Content-Type": "application/json",
        ...this.config.headers,
        ...headers,
      },
    };

    // Add Next.js specific options if available
    if (typeof window === "undefined" && this.config.revalidate) {
      fetchOptions.next = {
        tags: ["wordpress", ...tags],
        revalidate: this.config.revalidate,
      };
    }

    return wpApiGet(this.config.apiUrl, path, params, fetchOptions);
  }

  /**
   * Validate if the WordPress URL is accessible
   */
  async isValidUrl(): Promise<boolean> {
    return wpIsValidUrl(this.config.apiUrl);
  }

  /**
   * Get the current configuration
   */
  getConfig(): Readonly<WordPressClientConfig> {
    return { ...this.config };
  }

  /**
   * Update the client configuration
   */
  updateConfig(newConfig: Partial<WordPressClientConfig>): void {
    this.config = { ...this.config, ...newConfig };
    this.defaultResolver = this.createResolver();
  }

  // Posts Methods
  async fetchPosts(
    args: FetchPostsArgs,
    _resolver?: Resolver
  ): Promise<PostsResult> {
    const resolver = _resolver || this.createResolver();
    const headers = await resolver.getHeaders();
    const postType = args.post_type || "post";

    const postsResponse = await this.wpPluginGet<PostsRestResult>(
      "posts",
      args,
      [postType],
      headers
    );

    if (!postsResponse) {
      return {
        posts: [],
        total_count: 0,
        total_pages: 0,
      };
    }

    const resPosts = await resolvePosts(postsResponse.posts, resolver);

    const res = {
      ...postsResponse,
      posts: resPosts,
    };

    // resolve nodes in seo
    if (res.seo) {
      await resolver.waitPromise(resolveClutchFields(res.seo, resolver));
    }

    return res;
  }

  async fetchPostBySlug(
    postType: string = "post",
    slug: string,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<PostResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise(postType, slug);

    if (existingPromise) return existingPromise as Promise<PostResult | null>;

    return resolver.addAssetPromise(postType, slug, async () => {
      const headers = await resolver.getHeaders();
      const postResponse = await this.wpPluginGet<PostRestResult>(
        "post",
        {
          slug,
          seo: includeSeo,
        },
        [`${postType}-${slug}`],
        headers
      );

      if (postResponse) {
        return resolvePost(postResponse, resolver);
      }

      return null;
    }) as Promise<PostResult | null>;
  }

  async fetchPostById(
    postType: string = "post",
    id: WPIdFilter,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<PostResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise(postType, id);

    if (existingPromise) return existingPromise as Promise<PostResult | null>;

    return resolver.addAssetPromise(postType, id, async () => {
      const headers = await resolver.getHeaders();
      const postResponse = await this.wpPluginGet<PostRestResult>(
        "post",
        {
          id,
          seo: includeSeo,
        },
        [`${postType}-${id}`],
        headers
      );

      if (postResponse) {
        return resolvePost(postResponse, resolver);
      }

      return null;
    }) as Promise<PostResult | null>;
  }

  // Users Methods
  async fetchUsers(
    args: FetchUsersArgs,
    _resolver?: Resolver
  ): Promise<UserResult[]> {
    const resolver = _resolver || this.createResolver();
    const users = await this.wpGet<unknown[]>("users", args, ["users"]);

    return resolveUsers(users, resolver);
  }

  async fetchUserBySlug(
    slug: string,
    _resolver?: Resolver
  ): Promise<UserResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise("users", slug);

    if (existingPromise) return existingPromise as Promise<UserResult | null>;

    return resolver.addAssetPromise("users", slug, async () => {
      const users = await this.wpGet<unknown[]>("users", { slug }, [
        `users-${slug}`,
      ]);

      if (users?.length) {
        return resolveUser(users[0], resolver);
      }

      return null;
    }) as Promise<UserResult | null>;
  }

  async fetchUserById(
    id: WPIdFilter,
    _resolver?: Resolver
  ): Promise<UserResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise("users", id);

    if (existingPromise) return existingPromise as Promise<UserResult | null>;

    return resolver.addAssetPromise("users", id, async () => {
      const user = await this.wpGet<unknown>(`users/${id}`, {}, [
        `users-${id}`,
      ]);

      if (user) {
        return resolveUser(user, resolver);
      }

      return null;
    }) as Promise<UserResult | null>;
  }

  // Taxonomies Methods
  async fetchTaxonomyTerms(
    args: FetchTaxonomyTermsArgs,
    _resolver?: Resolver
  ): Promise<TermsResult> {
    const resolver = _resolver || this.createResolver();
    const headers = await resolver.getHeaders();
    const taxonomy = args.taxonomy || "category";
    const response = await this.wpPluginGet<TermsRestResult>(
      "terms",
      args,
      [taxonomy],
      headers
    );

    if (!response) {
      return {
        terms: [],

        total_count: 0,

        total_pages: 0,
      };
    }

    const resTerms = await resolveTaxonomyTerms(response.terms, resolver);
    const res = {
      ...response,
      terms: resTerms,
    };

    // resolve nodes in seo
    if (res.seo) {
      await resolver.waitPromise(resolveClutchFields(res.seo, resolver));
    }

    return res;
  }

  async fetchTaxonomyTermBySlug(
    taxonomy: string,
    slug: string,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<TaxonomyTermResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise(taxonomy, slug);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise(taxonomy, slug, async () => {
      const headers = await resolver.getHeaders();
      const term = await this.wpPluginGet<TermRestResult>(
        "term",
        {
          slug,
          taxonomy,
          seo: includeSeo,
        },
        [`${taxonomy}-${slug}`],
        headers
      );

      return term ? await resolveTaxonomyTerm(term, resolver) : null;
    });
  }

  async fetchTaxonomyTermById(
    taxonomy: string,
    id: WPIdFilter,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<TaxonomyTermResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise(taxonomy, id);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise(taxonomy, id, async () => {
      const headers = await resolver.getHeaders();
      const term = await this.wpPluginGet<TermRestResult>(
        "term",
        {
          id,
          taxonomy,
          seo: includeSeo,
        },
        [`${taxonomy}-${id}`],
        headers
      );

      return term ? await resolveTaxonomyTerm(term, resolver) : null;
    });
  }

  // Search Methods
  async fetchSearchResults(
    args: FetchSearchArgs,
    _resolver?: Resolver
  ): Promise<SearchResut[]> {
    const resolver = _resolver || this.createResolver();
    const results = await this.wpGet<WP_REST_API_Search_Results>(
      "search",
      args,
      []
    );

    return resolveSearchResults(results, resolver);
  }

  // Menus Methods
  async fetchMenuById(
    id: WPIdFilter,
    _resolver?: Resolver
  ): Promise<MenuResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise("menus", id);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise("menus", id, async () => {
      const menu = await this.wpPluginGet<MenuResponse>(`menus/${id}`, {}, [
        `menus-${id}`,
      ]);

      if (menu) {
        return resolveMenu(menu, resolver);
      }

      return null;
    });
  }
}
