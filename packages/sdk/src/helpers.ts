export function generateRandomToken() {
  const array = new Uint8Array(32); // 32 bytes = 256 bits

  window.crypto.getRandomValues(array);

  return Array.from(array, (byte) => byte.toString(16).padStart(2, '0')).join(
    '',
  );
}
