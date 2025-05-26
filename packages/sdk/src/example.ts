import { WordPressHttpClient, type WordPressClientConfig } from "./index";

/**
 * Example usage of the WordPressHttpClient
 */

// Basic configuration
const config: WordPressClientConfig = {
  apiUrl: "https://your-wordpress-site.com",
  authToken: "your-auth-token-here", // Optional
  cacheDisabled: false, // Optional, defaults to false
  draftMode: false, // Optional, defaults to false
};

// Create client instance
const wpClient = new WordPressHttpClient(config);

// Example usage
async function exampleUsage() {
  // Validate the WordPress URL
  const isValid = await wpClient.isValidUrl();

  if (!isValid) {
    throw new Error("Invalid WordPress URL");
  }

  // Fetch posts
  const posts = await wpClient.fetchPosts({
    post_type: "post",
    per_page: 10,
    page: 1,
  });

  console.log(`Found ${posts.total_count} posts`);

  // Fetch a specific post by slug
  const post = await wpClient.fetchPostBySlug("post", "hello-world", true);

  if (post) {
    console.log(`Post title: ${post.title}`);
  }

  // Fetch users
  const users = await wpClient.fetchUsers({
    per_page: 5,
  });

  console.log(`Found ${users.length} users`);

  // Fetch taxonomy terms
  const categories = await wpClient.fetchTaxonomyTerms({
    taxonomy: "category",
    per_page: 10,
  });

  console.log(`Found ${categories.total_count} categories`);

  // Search content
  const searchResults = await wpClient.fetchSearchResults({
    search: "wordpress",
    per_page: 5,
  });

  console.log(`Found ${searchResults.length} search results`);

  // Update configuration if needed
  wpClient.updateConfig({
    authToken: "new-auth-token",
    draftMode: true,
  });
}

// Export for usage
export { exampleUsage, wpClient };
