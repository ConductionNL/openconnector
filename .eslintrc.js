module.exports = {
	extends: [
		'@nextcloud',
	],
	settings: {
		// Resolve '@/...' via simple alias to avoid webpack resolver in ESLint
		'import/resolver': {
			alias: {
				map: [
					['@', './src'],
				],
				extensions: ['.js', '.ts', '.vue', '.json'],
			},
		},
	},
	rules: {
		'jsdoc/require-jsdoc': 'off',
		'vue/first-attribute-linebreak': 'off',
		// The Node resolver doesn't know webpack aliases; rely on import/no-unresolved instead
		'n/no-missing-import': 'off',
	},
}
