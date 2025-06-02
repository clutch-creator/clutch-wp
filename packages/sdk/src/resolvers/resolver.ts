import type { WordPressHttpClient } from "../client";
import type { TWpTemplateList } from "../types";

type Headers = Record<string, string>;

/**
 * Resolver class for managing WordPress API requests and asset promises
 */
export class Resolver {
  /**
   * Cache for storing asset promises by type and ID
   */
  private assetsPromises: Record<string, Record<string, Promise<unknown>>> = {};

  /**
   * Array of promises to wait for during resolution
   */
  private waitPromises: Promise<void>[] = [];

  /**
   * HTTP headers for API requests
   */
  private headers: Headers | undefined;

  /**
   * WordPress HTTP client instance
   */
  private client: WordPressHttpClient;

  /**
   * Creates a new Resolver instance
   * @param client - WordPress HTTP client for making API requests
   */
  constructor(client: WordPressHttpClient) {
    this.client = client;
    this.headers = {
      "Content-Type": "application/json",
    };
  }

  /**
   * Gets the pages/templates configuration from the client
   * @returns The pages configuration
   */
  getPages(): TWpTemplateList {
    return this.client.getConfig().pages || [];
  }

  /**
   * Retrieves an existing asset promise from the cache
   * @param typeName - The type name of the asset
   * @param id - The unique identifier of the asset
   * @returns The cached promise or undefined if not found
   */
  getAssetPromise<T = unknown>(
    typeName: string,
    id: string | number
  ): Promise<T> | undefined {
    return this.assetsPromises[typeName]?.[id] as Promise<T> | undefined;
  }

  /**
   * Adds an asset promise to the cache or returns existing one
   * @param typeName - The type name of the asset
   * @param id - The unique identifier of the asset
   * @param promiseFn - Function that returns the promise to cache
   * @returns The cached or newly created promise
   */
  addAssetPromise<T>(
    typeName: string,
    id: string | number,
    promiseFn: () => Promise<T>
  ): Promise<T> {
    this.assetsPromises[typeName] = this.assetsPromises[typeName] || {};
    this.assetsPromises[typeName][id] =
      this.assetsPromises[typeName][id] || promiseFn();

    return this.assetsPromises[typeName][id] as Promise<T>;
  }

  /**
   * Adds a function that returns a promise to the wait queue
   * @param fn - Function that returns a promise to wait for
   */
  waitUntil(fn: () => Promise<void>) {
    this.waitPromises.push(fn());
  }

  /**
   * Adds a promise to the wait queue
   * @param promise - Promise to wait for
   */
  waitPromise(promise: Promise<unknown>) {
    this.waitPromises.push(promise.then(() => undefined));
  }

  /**
   * Waits for all queued promises to resolve
   * @returns Promise that resolves when all queued promises complete
   */
  async waitAll() {
    const promises = this.waitPromises;

    this.waitPromises = [];

    await Promise.all(promises);
  }

  /**
   * Returns the WordPress HTTP client instance
   * @returns The WordPress HTTP client
   */
  getClient(): WordPressHttpClient {
    return this.client;
  }
}
