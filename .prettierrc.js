import wpConfig from '@wordpress/prettier-config';

/**
 * @type {import("prettier").Config}
 */
const config = {
	...wpConfig,
	plugins: ['@prettier/plugin-php'],
};

export default config;
