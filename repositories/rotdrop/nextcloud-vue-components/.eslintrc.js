module.exports = {
  extends: [
    '@nextcloud',
  ],
  globals: {
    APP_NAME: true,
  },
  rules: {
    'no-tabs': ['error', { allowIndentationTabs: false }],
    indent: ['error', 2],
    'no-mixed-spaces-and-tabs': 'error',
    'vue/html-indent': ['error', 2],
    semi: ['error', 'always'],
    'node/no-unpublished-import': 'off',
    'node/no-unpublished-require': 'off',
    'n/no-unpublished-import': 'off',
    'n/no-unpublished-require': 'off',
    'no-console': 'off',
    'n/no-missing-require': [
      'error', {
        resolvePaths: [
          './src',
          './style',
          './',
          '../img',
        ],
        tryExtensions: ['.js', '.ts', '.json', '.node', '.css', '.scss', '.xml', '.vue'],
      },
    ],
    // Do allow line-break before closing brackets
    'vue/html-closing-bracket-newline': ['error', { singleline: 'never', multiline: 'always' }],
  },
  overrides: [
    {
      files: ['*.vue'],
      rules: {
        semi: ['error', 'never'],
      },
    },
  ],
};
