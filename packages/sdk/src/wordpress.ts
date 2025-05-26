import { PluginEnv } from "./statics";

export type TParams = {
  [key: string]: string | number | boolean | string[] | number[] | unknown;
};

export function wpGetUrl() {
  return process.env.WORDPRESS_URL;
}

export function isCacheDisabled() {
  return (
    process.env.NODE_ENV === "development" &&
    process.env.WORDPRESS_CACHE_DISABLED === "true"
  );
}

export function isClutchDraftMode() {
  return (
    process.env.NODE_ENV === "development" &&
    process.env[PluginEnv.DRAFT_MODE] === "true"
  );
}

export function urlJoin(url: string, path: string): string {
  return new URL(path, url).href;
}

export function getProcessedParams(params?: TParams): URLSearchParams {
  const urlParams = new URLSearchParams();

  if (!params) return urlParams;

  Object.keys(params).forEach((key) => {
    let val = params[key];

    if (typeof val === "number" || typeof val === "boolean")
      val = val.toString();

    if (Array.isArray(val))
      val.forEach((v) => {
        urlParams.append(key, v.toString());
      });

    if (typeof val === "string") urlParams.append(key, val);
  });

  return urlParams;
}

export async function wpApiGet<T>(
  wpUrl: string,
  path: string,
  params?: TParams,
  fetchOptions?: RequestInit
): Promise<T | undefined> {
  const processedParams = getProcessedParams(params);

  try {
    const url = urlJoin(wpUrl, `/wp-json/wp/v2/${path}`);

    // get posts using WordPress fetch api
    const response = await fetch(`${url}?${processedParams}`, fetchOptions);

    if (!response.ok) {
      throw new Error("Network response was not ok");
    }

    return response.json();
  } catch (err) {
    return undefined;
  }
}

export async function wpGet<T>(
  path: string,
  params?: TParams,
  tags: string[] = [],
  headers?: HeadersInit
): Promise<T | undefined> {
  const wpUrl = wpGetUrl();

  if (!wpUrl) return undefined;

  return wpApiGet(wpUrl, path, params, {
    cache: isCacheDisabled() || isClutchDraftMode() ? "no-cache" : "default",
    next: {
      tags: ["wordpress", ...tags],
      // revalidate every hour by default
      revalidate: 60 * 60,
    },
    headers,
  });
}

export async function wpPluginGet<T>(
  wpUrl: string,
  path: string,
  params: TParams,
  tags: string[] = [],
  headers?: HeadersInit
): Promise<T | undefined> {
  const processedParams = getProcessedParams(params);

  try {
    const url = urlJoin(wpUrl, `/wp-json/clutch/v1/${path}`);

    // get posts using WordPress fetch api
    const response = await fetch(`${url}?${processedParams}`, {
      cache: isCacheDisabled() || isClutchDraftMode() ? "no-cache" : "default",
      next: {
        tags: ["wordpress", ...tags],
        // revalidate every hour by default
        revalidate: 60 * 60,
      },
      headers,
    });

    if (!response.ok) {
      throw new Error("Network response was not ok");
    }

    return response.json();
  } catch (err) {
    return undefined;
  }
}

export async function wpIsValidUrl(wpUrl: string): Promise<boolean> {
  try {
    const url = urlJoin(wpUrl, `/wp-json/wp/v2/statuses`);

    const response = await fetch(url, {
      cache: "no-cache",
    });

    return response.ok;
  } catch (err) {
    return false;
  }
}

// export async function wpApiPost<T>(path: string, params: TParams) {
//   const baseUrl = wpGetUrl();

//   if (!baseUrl) return undefined;

//   const url = urlJoin(baseUrl, `/wp-json/wp/v2/${path}`);

//   console.log('requesting', url);
//   const resolver = new Resolver();
//   const headers = await resolver.getHeaders();

//   try {
//     const response = await fetch(url, {
//       method: 'POST',
//       body: JSON.stringify(params),
//       headers: {
//         'Content-Type': 'application/json',
//         ...headers,
//       },
//     });

//     console.log('response', response);

//     if (!response.ok) {
//       throw new Error('Network response was not ok');
//     }

//     const result = await response.json();

//     console.log('result', result);

//     return result;
//   } catch (err) {
//     return undefined;
//   }
// }
