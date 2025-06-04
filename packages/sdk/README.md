# @clutch-wp/sdk

A TypeScript SDK for interacting with WordPress sites using the Clutch WordPress plugin. This package provides a powerful HTTP client for fetching posts, pages, users, taxonomies, and more from WordPress with built-in caching, authentication, and data resolution.

## Installation

```bash
npm install @clutch-wp/sdk
```

## Quick Start

```typescript
import { WordPressHttpClient } from '@clutch-wp/sdk';

// Basic configuration
const client = new WordPressHttpClient({
  apiUrl: 'https://your-wordpress-site.com',
  authToken: 'your-auth-token',
});

// Fetch posts
const posts = await client.fetchPosts({
  post_type: 'post',
  per_page: 10,
});

// Fetch a single post by slug
const post = await client.fetchPostBySlug('post', 'my-post-slug');
```

## Configuration

### WordPressClientConfig

The `WordPressHttpClient` accepts a configuration object with the following options:

```typescript
interface WordPressClientConfig {
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
```

### Example Configuration

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://my-wordpress-site.com',
  authToken: 'your-auth-token',
  cacheDisabled: false,
  draftMode: false,
  revalidate: 3600,
  headers: {
    'X-Custom-Header': 'value',
  },
  components: {
    RichText: MyRichTextComponent,
    Image: MyImageComponent,
    blockComponents: {
      'core/paragraph': MyParagraphComponent,
      'core/heading': MyHeadingComponent,
    },
  },
});
```

## Core Methods

### Client Configuration

#### `getConfig()`

Get the current client configuration.

```typescript
const config = client.getConfig();
```

#### `updateConfig(newConfig)`

Update the client configuration.

```typescript
client.updateConfig({
  authToken: 'new-token',
  draftMode: true,
});
```

#### `isValidUrl()`

Validate if the WordPress URL is accessible.

```typescript
const isValid = await client.isValidUrl();
```

### Posts

#### `fetchPosts(args)`

Fetch multiple posts with filtering and pagination.

```typescript
const result = await client.fetchPosts({
  post_type: 'post',
  per_page: 10,
  page: 1,
  order: 'desc',
  order_by: 'date',
  seo: true,
});

// Returns: PostsResult
interface PostsResult {
  posts: PostResult[];
  total_count: number;
  total_pages: number;
}
```

#### `fetchPostBySlug(postType, slug, includeSeo)`

Fetch a single post by its slug.

```typescript
const post = await client.fetchPostBySlug('post', 'my-post-slug', true);
// Returns: PostResult | null
```

#### `fetchPostById(postType, id, includeSeo)`

Fetch a single post by its ID.

```typescript
const post = await client.fetchPostById('post', 123, true);
// Returns: PostResult | null
```

### Post Types

#### `fetchPostTypes()`

Get all available post types.

```typescript
const postTypes = await client.fetchPostTypes();
// Returns: TClutchPostType[] | undefined
```

#### `fetchPostType(postType)`

Get a specific post type configuration.

```typescript
const postType = await client.fetchPostType('product');
// Returns: TClutchPostType | undefined
```

### Users

#### `fetchUsers(args)`

Fetch multiple users with filtering and pagination.

```typescript
const users = await client.fetchUsers({
  per_page: 20,
  page: 1,
  search: 'john',
  roles: ['author', 'editor'],
});
// Returns: UserResult[]
```

#### `fetchUserBySlug(slug)`

Fetch a single user by slug.

```typescript
const user = await client.fetchUserBySlug('john-doe');
// Returns: UserResult | null
```

#### `fetchUserById(id)`

Fetch a single user by ID.

```typescript
const user = await client.fetchUserById(123);
// Returns: UserResult | null
```

### Taxonomies

#### `fetchTaxonomies()`

Get all available taxonomies.

```typescript
const taxonomies = await client.fetchTaxonomies();
// Returns: TClutchTaxonomyType[]
```

#### `fetchTaxonomy(taxonomy)`

Get a specific taxonomy configuration.

```typescript
const taxonomy = await client.fetchTaxonomy('category');
// Returns: TClutchTaxonomyType | undefined
```

#### `fetchTaxonomyTerms(args)`

Fetch taxonomy terms with filtering and pagination.

```typescript
const result = await client.fetchTaxonomyTerms({
  taxonomy: 'category',
  per_page: 20,
  page: 1,
  hide_empty: true,
  order: 'asc',
  orderby: 'name',
});

// Returns: TermsResult
interface TermsResult {
  terms: TaxonomyTermResult[];
  total_count: number;
  total_pages: number;
}
```

#### `fetchTaxonomyTermBySlug(taxonomy, slug, includeSeo)`

Fetch a single taxonomy term by slug.

```typescript
const term = await client.fetchTaxonomyTermBySlug(
  'category',
  'technology',
  true
);
// Returns: TaxonomyTermResult | null
```

#### `fetchTaxonomyTermById(taxonomy, id, includeSeo)`

Fetch a single taxonomy term by ID.

```typescript
const term = await client.fetchTaxonomyTermById('category', 123, true);
// Returns: TaxonomyTermResult | null
```

### Search

#### `fetchSearchResults(args)`

Search WordPress content across posts and terms.

```typescript
const results = await client.fetchSearchResults({
  search: 'my search query',
  type: 'post',
  subtype: 'post',
  per_page: 10,
});
// Returns: SearchResult[] (can be PostResult or TaxonomyTermResult)
```

### Menus

#### `fetchMenuById(id)`

Fetch a WordPress menu by ID.

```typescript
const menu = await client.fetchMenuById(123);
// Returns: MenuResult | null
```

#### `fetchMenusLocations()`

Get all menu locations.

```typescript
const locations = await client.fetchMenusLocations();
// Returns: MenuLocationResponse[]
```

### Plugin Information

#### `getPluginInfo()`

Get information about the Clutch WordPress plugin.

```typescript
const info = await client.getPluginInfo();
// Returns: PluginInfoResponse | null
```

#### `validatePluginVersion()`

Validate plugin version compatibility.

```typescript
const validation = await client.validatePluginVersion();
// Returns: VersionValidationResult
```

### Utility Methods

#### `getFrontPageInfo()`

Get front page configuration.

```typescript
const frontPage = await client.fetchFrontPageInfo();
// Returns: TFrontPageInfo | undefined
```

#### `getPermalinkInfo(url)`

Get information about a specific URL/permalink.

```typescript
const info = await client.getPermalinkInfo('https://mysite.com/my-page/');
// Returns: TPermalinkInfo | undefined
```

#### `isInDraftMode()`

Check if the client is in draft mode.

```typescript
const isDraft = client.isInDraftMode();
// Returns: boolean
```

#### `getComponents()`

Get the configured components.

```typescript
const components = client.getComponents();
// Returns: TComponentsMap | undefined
```

## Authentication

To access private content or perform authenticated requests, you need to set up an authentication token:

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://your-site.com',
  authToken: 'your-auth-token',
});

// Or update it later
client.updateConfig({ authToken: 'your-auth-token' });
```

### Setting up a new auth token

```typescript
const token = await client.setupNewAuthToken();
// This will open the WordPress admin approval page in a new tab
```

## Caching

The SDK includes built-in caching support with configurable revalidation:

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://your-site.com',
  cacheDisabled: false, // Enable caching
  revalidate: 3600, // Revalidate every hour
});
```

## Next.js Integration

The SDK has built-in support for Next.js features like ISR (Incremental Static Regeneration):

```typescript
// Automatically uses Next.js cache tags and revalidation
const posts = await client.fetchPosts({
  post_type: 'post',
  per_page: 10,
});
```

## Draft Mode

Enable draft mode to bypass caching and fetch the latest content:

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://your-site.com',
  draftMode: true, // Bypass cache, fetch fresh content
});
```

## Data Types

The SDK provides comprehensive TypeScript types for all WordPress data:

- `PostResult` - WordPress posts with resolved relationships
- `UserResult` - WordPress users
- `TaxonomyTermResult` - Taxonomy terms (categories, tags, etc.)
- `MenuResult` - WordPress menus
- `TClutchPostType` - Post type configuration
- `TClutchTaxonomyType` - Taxonomy configuration
- `PostsResult` - Paginated posts response
- `TermsResult` - Paginated terms response

## Error Handling

The SDK handles errors gracefully and returns `undefined` or `null` for failed requests:

```typescript
try {
  const post = await client.fetchPostBySlug('post', 'non-existent-slug');
  if (!post) {
    console.log('Post not found');
  }
} catch (error) {
  console.error('Request failed:', error);
}
```

## Advanced Usage

### Custom Headers

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://your-site.com',
  headers: {
    'X-Custom-App': 'MyApp',
    Authorization: 'Bearer custom-token',
  },
});
```

### Component Mapping

Configure custom components for block rendering:

```typescript
const client = new WordPressHttpClient({
  apiUrl: 'https://your-site.com',
  components: {
    RichText: ({ tag, className, children }) => {
      const Tag = tag as keyof JSX.IntrinsicElements;
      return <Tag className={className}>{children}</Tag>;
    },
    Image: ({ src, alt, className }) => (
      <img src={src} alt={alt} className={className} />
    ),
    blockComponents: {
      'core/paragraph': ParagraphBlock,
      'core/heading': HeadingBlock,
      'custom/hero': HeroBlock,
    },
  },
});
```

## Requirements

- WordPress site with Clutch WordPress plugin installed and activated
- Node.js 16+
- TypeScript 4.5+ (recommended)

## License

GPL-2.0 - same as WordPress
