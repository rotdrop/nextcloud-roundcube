module.exports = {
  extends: [
    '@nextcloud',
  ],
  ignorePatterns: [
    'src/toolkit/util/file-download.js',
  ],
  rules: {
    'no-tabs': ['error', { allowIndentationTabs: false }],
    indent: ['error', 2],
    'no-mixed-spaces-and-tabs': 'error',
    'vue/html-indent': ['error', 2],
    semi: ['error', 'always'],
    'node/no-unpublished-import': 'off',
    'node/no-unpublished-require': 'off',
    'no-console': 'off',
    'node/no-missing-require': [
      'error', {
        // 'allowModules': [],
        resolvePaths: [
          './src',
          './style',
          './',
        ],
        tryExtensions: ['.js', '.json', '.node', '.css', '.scss', '.xml', '.vue'],
      },
    ],
    'node/no-missing-import': [
      'error', {
        // 'allowModules': [],
        resolvePaths: [
          './src',
          './',
        ],
        tryExtensions: ['.js', '.vue'],
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
