import { TParams } from './types';

export function generateRandomToken() {
  const array = new Uint8Array(32); // 32 bytes = 256 bits

  window.crypto.getRandomValues(array);

  return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

export function urlJoin(url: string, path: string): string {
  return new URL(path, url).href;
}

export function getProcessedUrlSearchParams(
  params: TParams | undefined
): URLSearchParams {
  const urlParams = new URLSearchParams();

  if (!params) return urlParams;

  Object.keys(params).forEach(key => {
    let val = params[key];

    if (typeof val === 'number' || typeof val === 'boolean')
      val = val.toString();

    if (Array.isArray(val))
      val.forEach(v => {
        urlParams.append(key, v.toString());
      });

    if (typeof val === 'string') urlParams.append(key, val);
  });

  return urlParams;
}

export async function isValidWordpressUrl(wpUrl: string): Promise<boolean> {
  try {
    const url = urlJoin(wpUrl, `/wp-json/wp/v2/statuses`);

    const response = await fetch(url, {
      cache: 'no-cache',
    });

    return response.ok;
  } catch (err) {
    return false;
  }
}
