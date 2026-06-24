module.exports = {
  extends: [
    '@nextcloud',
  ],
  // some unused toolgit files
  ignorePatterns: [
    'src/toolkit/util/file-download.js',
    'src/toolkit/util/dialogs.js',
    'src/toolkit/util/ajax.js',
    'src/toolkit/util/jquery.js',
    'src/toolkit/types/axios-type-guards.ts',
    'src/toolkit/types/event-bus.d.ts',
    'src/toolkit/util/axios-file-download.ts',
    'src/toolkit/util/file-node-helper.ts',
    'src/toolkit/util/nextcloud-sidebar-root.ts',
  ],
  rules: {
    'no-tabs': ['error', { allowIndentationTabs: false }],
    indent: ['error', 2],
    'no-mixed-spaces-and-tabs': 'error',
    'vue/html-indent': ['error', 2],
    semi: ['error', 'always'],
    'no-console': 'off',
    'n/no-missing-require': [
      'error', {
        resolvePaths: [
          './src',
          './style',
          './',
        ],
        tryExtensions: ['.js', '.json', '.node', '.css', '.scss', '.xml', '.vue'],
      },
    ],
    // Do allow line-break before closing brackets
    'vue/html-closing-bracket-newline': ['error', { singleline: 'never', multiline: 'always' }],
    'n/no-unpublished-import': 'off',
    'n/no-unpublished-require': 'off',
  },
  overrides: [
    {
      // Vue files with TypeScript need the TypeScript parser
      files: ['*.vue'],
      parser: 'vue-eslint-parser',
      parserOptions: {
        parser: '@typescript-eslint/parser',
        sourceType: 'module',
        ecmaVersion: 2022,
      },
      rules: {
        semi: ['error', 'never'],
        // False positive with TypeScript generics like defineEmits<{...}>()
        'func-call-spacing': 'off',
        '@typescript-eslint/func-call-spacing': 'off',
        // Webpack ?raw query not recognized by eslint resolver
        'import/no-unresolved': ['error', { ignore: ['\\?raw$'] }],
      },
    },
    {
      files: ['*.ts', '*.cts', '*.mts', '*.tsx'],
      rules: {
        '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
      },
    },
  ],
};
