import { WP_REST_API_Search_Results, WP_REST_API_User } from 'wp-types';
import {
  generateRandomToken,
  getProcessedUrlSearchParams,
  isValidWordpressUrl,
  urlJoin,
} from './helpers';
import { resolveClutchFields } from './resolvers/clutch-nodes';
import { resolveLink } from './resolvers/links';
import { resolveMenu } from './resolvers/menus';
import { Resolver } from './resolvers/resolver';
import { resolveSearchResults } from './resolvers/search';
import { resolveUser, resolveUsers } from './resolvers/users';
import {
  CreatePostArgs,
  FetchPostsArgs,
  FetchSearchArgs,
  FetchTaxonomyTermsArgs,
  FetchUsersArgs,
  MenuLocationResponse,
  MenuResponse,
  MenuResult,
  PluginInfoResponse,
  PostRestResult,
  PostResult,
  PostsRestResult,
  PostsResult,
  SearchResut,
  TaxonomyTermResult,
  TClutchPostType,
  TClutchTaxonomyType,
  TermRestResult,
  TermsRestResult,
  TermsResult,
  TFrontPageInfo,
  TParams,
  TPermalinkInfo,
  TWpTemplateList,
  UserResult,
  VersionValidationResult,
  WPIdFilter,
} from './types';
import { VersionConfig } from './version-config';

type TComponentsMap = {
  RichText: React.ComponentType<{
    tag: string;
    className?: string;
    children?: React.ReactNode;
  }>;
  Image: React.ComponentType<{ src: string; alt?: string; className?: string }>;
  blockComponents?: Record<string, React.ComponentType<unknown>>;
};

export interface WordPressClientConfig {
  /** The WordPress site URL (e.g., https://example.com) */
  apiUrl: string;
  /** WordPress pages/templates configuration */
  pages?: TWpTemplateList;
  /** Components to use for rendering blocks */
  components?: TComponentsMap;
  /** Authentication token */
  authToken: string;
  /** Whether to disable caching (useful for development) */
  cacheDisabled?: boolean;
  /** Whether to enable draft mode */
  draftMode?: boolean;
  /** Custom headers to include with requests */
  headers?: Record<string, string>;
  /** Cache revalidation time in seconds (default: 3600 = 1 hour) */
  revalidate?: number;
  /** Optional disables resolving of fields */
  disableResolving?: boolean;
}

export class WordPressHttpClient {
  private config: WordPressClientConfig;

  constructor(config: WordPressClientConfig) {
    const {
      cacheDisabled = false,
      draftMode = false,
      revalidate = 3600,
      ...restConfig
    } = config;

    this.config = {
      cacheDisabled,
      draftMode,
      revalidate,
      ...restConfig,
    };
  }

  private createResolver(): Resolver {
    return new Resolver(this);
  }

  private async wpPluginGet<T>(
    path: string,
    params: TParams,
    tags: string[] = [],
    headers?: Record<string, string>
  ): Promise<T | undefined> {
    const {
      apiUrl,
      cacheDisabled,
      draftMode,
      headers: configHeaders,
      authToken,
      revalidate,
    } = this.config;
    const processedParams = getProcessedUrlSearchParams(params);

    try {
      const url = urlJoin(apiUrl, `/wp-json/clutch/v1/${path}`);
      const cache = cacheDisabled || draftMode ? 'no-cache' : 'default';

      const requestHeaders: Record<string, string> = {
        'Content-Type': 'application/json',
        ...configHeaders,
        ...headers,
      };

      if (authToken) {
        requestHeaders.Authorization = `Bearer ${authToken}`;
      }

      if (draftMode) {
        requestHeaders['X-Draft-Mode'] = 'true';
      }

      // For environments that support Next.js features
      const fetchOptions: RequestInit & {
        next?: { tags: string[]; revalidate: number };
      } = {
        cache,
        headers: requestHeaders,
      };

      // Add Next.js specific options if available
      if (typeof window === 'undefined' && revalidate) {
        fetchOptions.next = {
          tags: ['wordpress', ...tags],
          revalidate,
        };
      }

      const response = await fetch(`${url}?${processedParams}`, fetchOptions);

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      return response.json();
    } catch (err) {
      return undefined;
    }
  }

  private async wpPluginPost<T>(
    path: string,
    data: Record<string, unknown>,
    headers?: Record<string, string>
  ): Promise<T | undefined> {
    const { apiUrl, headers: configHeaders, authToken } = this.config;

    try {
      const url = urlJoin(apiUrl, `/wp-json/clutch/v1/${path}`);

      const requestHeaders: Record<string, string> = {
        'Content-Type': 'application/json',
        ...configHeaders,
        ...headers,
      };

      if (authToken) {
        requestHeaders.Authorization = `Bearer ${authToken}`;
      }

      // For environments that support Next.js features
      const fetchOptions: RequestInit & {
        next?: { tags: string[]; revalidate: number };
      } = {
        method: 'POST',
        headers: requestHeaders,
        body: JSON.stringify(data),
      };

      const response = await fetch(url, fetchOptions);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));

        throw new Error(
          `Network response was not ok: ${response.status} ${response.statusText}`,
          { cause: errorData }
        );
      }

      return response.json();
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error('wpPluginPost error:', err);
      throw err;
    }
  }

  private async wpGet<T>(
    path: string,
    params?: TParams,
    tags: string[] = [],
    headers?: Record<string, string>
  ): Promise<T | undefined> {
    const {
      apiUrl,
      cacheDisabled,
      draftMode,
      headers: configHeaders,
      revalidate,
    } = this.config;

    const fetchOptions: RequestInit & {
      next?: { tags: string[]; revalidate: number };
    } = {
      cache: cacheDisabled || draftMode ? 'no-cache' : 'default',
      headers: {
        'Content-Type': 'application/json',
        ...configHeaders,
        ...headers,
      },
    };

    // Add Next.js specific options if available
    if (typeof window === 'undefined' && revalidate) {
      fetchOptions.next = {
        tags: ['wordpress', ...tags],
        revalidate,
      };
    }

    const processedParams = getProcessedUrlSearchParams(params);

    try {
      const url = urlJoin(apiUrl, `/wp-json/wp/v2/${path}`);

      // get posts using WordPress fetch api
      const response = await fetch(`${url}?${processedParams}`, fetchOptions);

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      return response.json();
    } catch (err) {
      return undefined;
    }
  }

  /**
   * Validate if the WordPress URL is accessible
   */
  async isValidUrl(): Promise<boolean> {
    const { apiUrl } = this.config;

    return isValidWordpressUrl(apiUrl);
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
  }

  getComponents() {
    return this.config.components;
  }

  /**
   * Get plugin information from the WordPress site
   */
  async getPluginInfo(): Promise<PluginInfoResponse | null> {
    try {
      const response = await this.wpPluginGet<PluginInfoResponse>('info', {});

      return response || null;
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn('Failed to fetch plugin info:', error);

      return null;
    }
  }

  /**
   * Validate the plugin version compatibility
   */
  async validatePluginVersion(): Promise<VersionValidationResult> {
    const pluginInfo = await this.getPluginInfo();

    if (!pluginInfo) {
      const result: VersionValidationResult = {
        isCompatible: false,
        isAuthenticated: false,
        pluginVersion: 'unknown',
        requiredVersion: VersionConfig.getMinimumPluginVersion(),
        supportedRange: VersionConfig.getSupportedVersionRange(),
        message:
          'Unable to fetch plugin information. Please ensure the Clutch WordPress plugin is installed and activated.',
        severity: 'error',
      };

      return result;
    }

    const compatibilityInfo = VersionConfig.getCompatibilityInfo(
      pluginInfo.version
    );
    const result: VersionValidationResult = {
      ...compatibilityInfo,
      isAuthenticated: pluginInfo.isAuthenticated,
      severity: compatibilityInfo.isCompatible ? 'info' : 'error',
    };

    return result;
  }

  async setupNewAuthToken(): Promise<string | undefined> {
    const token = generateRandomToken();
    const adminUrl = urlJoin(
      this.config.apiUrl,
      `/wp-admin/admin.php?page=clutch-approve-token&token=${token}`
    );

    open(adminUrl, '_blank');

    return token;
  }

  // Posts Methods
  async fetchPosts(
    args: FetchPostsArgs,
    _resolver?: Resolver
  ): Promise<PostsResult> {
    const resolver = _resolver || this.createResolver();
    const postType = args.post_type || 'post';

    const postsResponse = await this.wpPluginGet<PostsRestResult>(
      'posts',
      args,
      [postType]
    );

    if (!postsResponse) {
      return {
        posts: [],
        total_count: 0,
        total_pages: 0,
      };
    }

    return resolveClutchFields<PostsResult>(postsResponse, resolver);
  }

  async fetchPostBySlug(
    postType: string = 'post',
    slug: string,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<PostResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise<PostResult | null>(
      postType,
      slug
    );

    if (existingPromise) return existingPromise as Promise<PostResult | null>;

    return resolver.addAssetPromise(postType, slug, async () => {
      const postResponse = await this.wpPluginGet<PostRestResult>(
        'post',
        {
          slug,
          seo: includeSeo,
        },
        [`${postType}-${slug}`]
      );

      if (postResponse) {
        return resolveClutchFields<PostResult>(postResponse, resolver);
      }

      return null;
    });
  }

  async fetchPostById(
    postType: string = 'post',
    id: WPIdFilter,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<PostResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise<PostResult>(postType, id);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise(postType, id, async () => {
      const postResponse = await this.wpPluginGet<PostRestResult>(
        'post',
        {
          id,
          seo: includeSeo,
        },
        [`${postType}-${id}`]
      );

      if (postResponse) {
        return resolveClutchFields<PostResult>(postResponse, resolver);
      }

      return null;
    });
  }

  async fetchPostType(
    postType: string = 'post'
  ): Promise<TClutchPostType | undefined> {
    const postTypeResponse = await this.wpPluginGet<TClutchPostType>(
      `post-type/${postType}`,
      {},
      [`post-type-${postType}`]
    );

    return postTypeResponse ? postTypeResponse : undefined;
  }

  async fetchPostTypes(): Promise<TClutchPostType[] | undefined> {
    const postTypesResponse = await this.wpPluginGet<TClutchPostType[]>(
      `post-types`,
      {},
      [`post-types`]
    );

    return postTypesResponse ? postTypesResponse : undefined;
  }

  async createPost(
    args: CreatePostArgs,
    _resolver?: Resolver
  ): Promise<number> {
    const postResponse = await this.wpPluginPost<PostRestResult>('post', args);

    if (!postResponse) {
      throw new Error('Failed to create post');
    }

    return postResponse.id;
  }

  // Users Methods
  async fetchUsers(
    args: FetchUsersArgs,
    _resolver?: Resolver
  ): Promise<UserResult[]> {
    const resolver = _resolver || this.createResolver();
    const users = await this.wpGet<WP_REST_API_User[]>('users', args, [
      'users',
    ]);

    return resolveUsers(users, resolver);
  }

  async fetchUserBySlug(
    slug: string,
    _resolver?: Resolver
  ): Promise<UserResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise<UserResult>('users', slug);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise('users', slug, async () => {
      const users = await this.wpGet<WP_REST_API_User[]>('users', { slug }, [
        `users-${slug}`,
      ]);

      if (users?.length) {
        return resolveUser(users[0], resolver);
      }

      return null;
    });
  }

  async fetchUserById(
    id: WPIdFilter,
    _resolver?: Resolver
  ): Promise<UserResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise<UserResult>('users', id);

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise('users', id, async () => {
      const user = await this.wpGet<WP_REST_API_User>(`users/${id}`, {}, [
        `users-${id}`,
      ]);

      if (user) {
        return resolveUser(user, resolver);
      }

      return null;
    });
  }

  async fetchTaxonomies(): Promise<TClutchTaxonomyType[]> {
    const taxonomies = await this.wpPluginGet<TClutchTaxonomyType[]>(
      'taxonomies',
      {},
      ['taxonomies']
    );

    return taxonomies || [];
  }

  async fetchTaxonomy(
    taxonomy: string
  ): Promise<TClutchTaxonomyType | undefined> {
    const taxonomyRes = await this.wpPluginGet<TClutchTaxonomyType>(
      `taxonomy/${taxonomy}`,
      {},
      ['taxonomies', `taxonomy-${taxonomy}`]
    );

    return taxonomyRes;
  }

  // Taxonomies Methods
  async fetchTaxonomyTerms(
    args: FetchTaxonomyTermsArgs,
    _resolver?: Resolver
  ): Promise<TermsResult> {
    const resolver = _resolver || this.createResolver();
    const taxonomy = args.taxonomy || 'category';
    const response = await this.wpPluginGet<TermsRestResult>('terms', args, [
      taxonomy,
    ]);

    if (!response) {
      return {
        terms: [],
        total_count: 0,
        total_pages: 0,
      };
    }

    return resolveClutchFields<TermsResult>(response, resolver);
  }

  async fetchTaxonomyTermBySlug(
    taxonomy: string,
    slug: string,
    includeSeo: boolean = false,
    _resolver?: Resolver
  ): Promise<TaxonomyTermResult | null> {
    if (!slug) return null;

    const resolver = _resolver || this.createResolver();

    const existingPromise = resolver.getAssetPromise<TaxonomyTermResult>(
      taxonomy,
      slug
    );

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise(taxonomy, slug, async () => {
      const term = await this.wpPluginGet<TermRestResult>(
        'term',
        {
          slug,
          taxonomy,
          seo: includeSeo,
        },
        [`${taxonomy}-${slug}`]
      );

      if (term) {
        return resolveClutchFields<TaxonomyTermResult>(term, resolver);
      }

      return null;
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

    const existingPromise = resolver.getAssetPromise<TaxonomyTermResult>(
      taxonomy,
      id
    );

    if (existingPromise) return existingPromise;

    return resolver.addAssetPromise(taxonomy, id, async () => {
      const term = await this.wpPluginGet<TermRestResult>(
        'term',
        {
          id,
          taxonomy,
          seo: includeSeo,
        },
        [`${taxonomy}-${id}`]
      );

      if (term) {
        return resolveClutchFields<TaxonomyTermResult>(term, resolver);
      }

      return null;
    });
  }

  // Search Methods
  async fetchSearchResults(
    args: FetchSearchArgs,
    _resolver?: Resolver
  ): Promise<SearchResut[]> {
    const resolver = _resolver || this.createResolver();
    const results = await this.wpGet<WP_REST_API_Search_Results>(
      'search',
      args,
      []
    );

    return resolveSearchResults(results || [], resolver);
  }

  // Menus Methods
  async fetchMenuById(
    id: WPIdFilter,
    _resolver?: Resolver
  ): Promise<MenuResult | null> {
    if (!id) return null;

    const resolver = _resolver || this.createResolver();

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise('menus', id);

    if (existingPromise) return existingPromise as Promise<MenuResult | null>;

    return resolver.addAssetPromise('menus', id, async () => {
      const menu = await this.wpPluginGet<MenuResponse>(`menus/${id}`, {}, [
        `menus-${id}`,
      ]);

      if (menu) {
        return resolveMenu(menu, resolver);
      }

      return null;
    }) as Promise<MenuResult | null>;
  }

  async fetchMenusLocations(): Promise<MenuLocationResponse[]> {
    const locations = await this.wpPluginGet<MenuLocationResponse[]>(
      'menus',
      {},
      ['menus']
    );

    return locations || [];
  }

  async fetchFrontPageInfo(): Promise<TFrontPageInfo | undefined> {
    const frontPageInfo = await this.wpPluginGet<TFrontPageInfo>(
      'front-page',
      {},
      ['front-page']
    );

    return frontPageInfo;
  }

  async getPermalinkInfo(
    url: string,
    _resolver?: Resolver
  ): Promise<TPermalinkInfo | undefined> {
    const resolver = _resolver || this.createResolver();

    const { apiUrl } = this.config;
    const relativePath = url.replace(apiUrl, '');
    const cacheKey = relativePath.replace(/\//g, '-').slice(0, -1);

    // check if resolver is already resolving/resolved this resource
    const existingPromise = resolver.getAssetPromise('permalinks', cacheKey);

    if (existingPromise)
      return existingPromise as Promise<TPermalinkInfo | undefined>;

    return resolver.addAssetPromise('permalinks', cacheKey, async () => {
      const res = await this.wpPluginGet<TPermalinkInfo>(
        'permalink-info',
        { url },
        ['permalinks', `perma-${cacheKey}`]
      );

      return res;
    });
  }

  async resolveLink(url: string, _resolver?: Resolver): Promise<string> {
    const resolver = _resolver || this.createResolver();

    return resolveLink(url, resolver);
  }

  isInDraftMode(): boolean {
    const { draftMode } = this.config;

    return !!draftMode;
  }
}
