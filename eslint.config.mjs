import wpPlugin from '@wordpress/eslint-plugin';
import { createRequire } from 'node:module';
import globals from 'globals';

const require = createRequire( import.meta.url );

// Packages provided by WordPress at runtime (externalized by wp-scripts).
// Same exclusion list as the legacy (eslintrc) recommended-with-formatting config.
const wpPackagesRegExp = '^@wordpress/(?!(icons|interface|style-engine))';

export default [
	{
		ignores: [ 'assets/**', 'vendor/**', 'node_modules/**' ],
	},
	...wpPlugin.configs[ 'recommended-with-formatting' ],
	{
		languageOptions: {
			parserOptions: {
				requireConfigFile: false,
				babelOptions: {
					presets: [
						require.resolve( '@wordpress/babel-preset-default' ),
					],
				},
			},
			globals: {
				...globals.browser,
				...globals.jquery,
				wp: 'readonly',
				jQuery: 'readonly',
			},
		},
		settings: {
			'import/internal-regex': wpPackagesRegExp,
		},
		rules: {
			'import/no-unresolved': [
				'error',
				{
					ignore: [ wpPackagesRegExp ],
				},
			],
			'no-alert': 'off',
			'prettier/prettier': 'off',
			'object-shorthand': 'off',
			'jsdoc/require-jsdoc': 'off',
			'jsdoc/no-undefined-types': 'off',
			'jsdoc/check-tag-names': 'off',
			'no-prototype-builtins': 'off',
			'object-curly-newline': 'off',
			'object-property-newline': 'off',
			yoda: 'off',
			strict: 'off',
		},
	},
];
