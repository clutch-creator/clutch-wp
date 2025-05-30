import {
  WordPressHttpClient,
  type FetchPostsArgs,
  type FetchSearchArgs,
  type FetchTaxonomyTermsArgs,
  type FetchUsersArgs,
  type MenuResult,
  type PostResult,
  type PostsResult,
  type SearchResut,
  type TaxonomyTermResult,
  type TermsResult,
  type UserResult,
  type WPIdFilter,
} from "@clutch-wp/sdk";
import { useCallback, useContext, useEffect, useState } from "react";
import { WordPressContext, type WordPressContextValue } from "./context";

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
 */
export function usePosts(args: FetchPostsArgs) {
  const client = useWordPressClient();
  const [data, setData] = useState<PostsResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchPosts = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchPosts(args);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch posts"));
    } finally {
      setLoading(false);
    }
  }, [client, JSON.stringify(args)]);

  useEffect(() => {
    fetchPosts();
  }, [fetchPosts]);

  return { data, loading, error, refetch: fetchPosts };
}

/**
 * Hook to fetch a single WordPress post by slug
 */
export function usePost(
  postType: string = "post",
  slug: string,
  includeSeo: boolean = false
) {
  const client = useWordPressClient();
  const [data, setData] = useState<PostResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchPost = useCallback(async () => {
    if (!slug) {
      setData(null);
      setLoading(false);

      return;
    }

    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchPostBySlug(postType, slug, includeSeo);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch post"));
    } finally {
      setLoading(false);
    }
  }, [client, postType, slug, includeSeo]);

  useEffect(() => {
    fetchPost();
  }, [fetchPost]);

  return { data, loading, error, refetch: fetchPost };
}

/**
 * Hook to fetch WordPress users
 */
export function useUsers(args: FetchUsersArgs) {
  const client = useWordPressClient();
  const [data, setData] = useState<UserResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchUsers = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchUsers(args);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch users"));
    } finally {
      setLoading(false);
    }
  }, [client, JSON.stringify(args)]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  return { data, loading, error, refetch: fetchUsers };
}

/**
 * Hook to fetch a single WordPress user
 */
export function useUser(
  identifier: string | number,
  type: "slug" | "id" = "slug"
) {
  const client = useWordPressClient();
  const [data, setData] = useState<UserResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchUser = useCallback(async () => {
    if (!identifier) {
      setData(null);
      setLoading(false);

      return;
    }

    try {
      setLoading(true);
      setError(null);
      const result =
        type === "slug"
          ? await client.fetchUserBySlug(String(identifier))
          : await client.fetchUserById(identifier);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch user"));
    } finally {
      setLoading(false);
    }
  }, [client, identifier, type]);

  useEffect(() => {
    fetchUser();
  }, [fetchUser]);

  return { data, loading, error, refetch: fetchUser };
}

/**
 * Hook to fetch taxonomy terms
 */
export function useTaxonomyTerms(args: FetchTaxonomyTermsArgs) {
  const client = useWordPressClient();
  const [data, setData] = useState<TermsResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchTerms = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchTaxonomyTerms(args);

      setData(result);
    } catch (err) {
      setError(
        err instanceof Error ? err : new Error("Failed to fetch taxonomy terms")
      );
    } finally {
      setLoading(false);
    }
  }, [client, JSON.stringify(args)]);

  useEffect(() => {
    fetchTerms();
  }, [fetchTerms]);

  return { data, loading, error, refetch: fetchTerms };
}

/**
 * Hook to fetch a single taxonomy term
 */
export function useTaxonomyTerm(
  taxonomy: string,
  identifier: string | number,
  type: "slug" | "id" = "slug",
  includeSeo: boolean = false
) {
  const client = useWordPressClient();
  const [data, setData] = useState<TaxonomyTermResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchTerm = useCallback(async () => {
    if (!taxonomy || !identifier) {
      setData(null);
      setLoading(false);

      return;
    }

    try {
      setLoading(true);
      setError(null);
      const result =
        type === "slug"
          ? await client.fetchTaxonomyTermBySlug(
              taxonomy,
              String(identifier),
              includeSeo
            )
          : await client.fetchTaxonomyTermById(
              taxonomy,
              identifier,
              includeSeo
            );

      setData(result);
    } catch (err) {
      setError(
        err instanceof Error ? err : new Error("Failed to fetch taxonomy term")
      );
    } finally {
      setLoading(false);
    }
  }, [client, taxonomy, identifier, type, includeSeo]);

  useEffect(() => {
    fetchTerm();
  }, [fetchTerm]);

  return { data, loading, error, refetch: fetchTerm };
}

/**
 * Hook to search WordPress content
 */
export function useSearch(args: FetchSearchArgs) {
  const client = useWordPressClient();
  const [data, setData] = useState<SearchResut[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const search = useCallback(async () => {
    if (!args.search) {
      setData([]);
      setLoading(false);

      return;
    }

    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchSearchResults(args);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to search"));
    } finally {
      setLoading(false);
    }
  }, [args, client]);

  useEffect(() => {
    search();
  }, [search]);

  return { data, loading, error, search };
}

/**
 * Hook to fetch a WordPress menu
 */
export function useMenu(id: WPIdFilter) {
  const client = useWordPressClient();
  const [data, setData] = useState<MenuResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const fetchMenu = useCallback(async () => {
    if (!id) {
      setData(null);
      setLoading(false);

      return;
    }

    try {
      setLoading(true);
      setError(null);
      const result = await client.fetchMenuById(id);

      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err : new Error("Failed to fetch menu"));
    } finally {
      setLoading(false);
    }
  }, [client, id]);

  useEffect(() => {
    fetchMenu();
  }, [fetchMenu]);

  return { data, loading, error, refetch: fetchMenu };
}

/**
 * Hook to check if WordPress is in draft mode
 */
export function useDraftMode() {
  const client = useWordPressClient();
  const [isDraftMode, setIsDraftMode] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkDraftMode = async () => {
      try {
        setLoading(true);
        const result = await client.isInDraftMode();

        setIsDraftMode(result);
      } catch (err) {
        console.warn("Failed to check draft mode:", err);
        setIsDraftMode(false);
      } finally {
        setLoading(false);
      }
    };

    checkDraftMode();
  }, [client]);

  return { isDraftMode, loading };
}
