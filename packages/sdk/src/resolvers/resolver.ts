import { cookies, draftMode } from "next/headers";
import { TWpTemplateList } from "../../plugin/types";
import { PluginEnv } from "../statics.ts";
import { isClutchDraftMode } from "../wordpress";

type Headers = Record<string, string>;

export class Resolver {
  private assetsPromises: Record<string, Record<string, Promise<unknown>>> = {};

  private waitPromises: Promise<void>[] = [];

  private templates: TWpTemplateList = [];

  private loadedTemplates = false;

  private inDraftMode: boolean | undefined;

  private headers: Headers | undefined;

  constructor() {
    this.headers = {
      "Content-Type": "application/json",
    };
  }

  getAssetPromise(
    typeName: string,
    id: string | number
  ): Promise<unknown> | undefined {
    return this.assetsPromises[typeName]?.[id];
  }

  addAssetPromise(
    typeName: string,
    id: string | number,
    promiseFn: () => Promise<unknown>
  ): Promise<unknown> {
    this.assetsPromises[typeName] = this.assetsPromises[typeName] || {};
    this.assetsPromises[typeName][id] =
      this.assetsPromises[typeName][id] || promiseFn();

    return this.assetsPromises[typeName][id];
  }

  waitUntil(fn: () => Promise<void>) {
    this.waitPromises.push(fn());
  }

  waitPromise(promise: Promise<unknown>) {
    this.waitPromises.push(promise.then(() => undefined));
  }

  async waitAll() {
    const promises = this.waitPromises;

    this.waitPromises = [];

    await Promise.all(promises);
  }

  async getTemplates(): Promise<TWpTemplateList> {
    if (!this.loadedTemplates) {
      try {
        const { templates } = await import("clutch/wp-templates.json");

        this.templates = templates as TWpTemplateList;
        this.loadedTemplates = true;
      } catch (e) {
        // Error loading templates - using empty array
      }
    }

    return this.templates;
  }

  async isInDraftMode(): Promise<boolean> {
    if (this.inDraftMode === undefined) {
      this.inDraftMode = isClutchDraftMode() || (await draftMode()).isEnabled;
    }

    return this.inDraftMode;
  }

  async getHeaders(): Promise<Headers> {
    this.headers = this.headers || {};

    if (!this.headers?.Authorization) {
      const cookieStore = await cookies();
      const authToken =
        cookieStore.get("wpAuthToken")?.value ||
        process.env[PluginEnv.WP_AUTH_TOKEN];

      if (authToken) {
        this.headers.Authorization = `Bearer ${authToken}`;
      }
    }

    const draftMode = await this.isInDraftMode();

    if (draftMode) {
      this.headers["X-Draft-Mode"] = "true";
    }

    return this.headers;
  }
}
