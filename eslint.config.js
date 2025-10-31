/* eslint-disable n/no-extraneous-require */
const {
	defineConfig,
} = require('@eslint/config-helpers')

const js = require('@eslint/js')

const {
	FlatCompat,
} = require('@eslint/eslintrc')

const compat = new FlatCompat({
	baseDirectory: __dirname,
	recommendedConfig: js.configs.recommended,
	allConfig: js.configs.all,
})

module.exports = defineConfig([{
	extends: compat.extends('@nextcloud'),

	settings: {
		'import/resolver': {
			alias: {
				map: [['@', './src']],
				extensions: ['.js', '.ts', '.vue', '.json'],
			},
		},
	},

	rules: {
		'jsdoc/require-jsdoc': 'off',
		'jsdoc/no-undefined-types': 'off', // disable undefined types as TS already handles this.
		'vue/first-attribute-linebreak': 'off',
		'n/no-missing-import': 'off',
	},
}])
