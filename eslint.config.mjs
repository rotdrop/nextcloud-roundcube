import { recommended } from '@nextcloud/eslint-config';
import {
  defineConfig,
  globalIgnores,
} from 'eslint/config';

const configOptions = [
  ...recommended,
  {
    name: 'undo gitignores',
    ignores: [
      '!build',
      'build/*',
      '!build/ts-types/',
    ],
  },
  {
    languageOptions: {
      globals: {
        DEV_MODE: 'readonly',
      },
    },
  },
  {
    files: ['**/*.vue'],
    rules: {
      'vue/attribute-hyphenation': ['error', 'never'],
      'vue/html-indent': ['error', 2],
      'vue/html-closing-bracket-newline': ['error', {
        singleline: 'never',
        multiline: 'always',
      }],
      'vue/first-attribute-linebreak': [
        'error',
        {
          multiline: 'beside',
        },
      ],
      semi: ['error', 'never'],
      'no-tabs': ['error', {
        allowIndentationTabs: false,
      }],
      indent: [
        'error',
        2,
        {
          SwitchCase: 1,
        },
      ],
      '@stylistic/implicit-arrow-linebreak': 'off',
      '@stylistic/indent': ['error', 2],
      '@stylistic/indent-binary-ops': ['error', 2],
      '@stylistic/padded-blocks': 'off',
      '@stylistic/space-infix-ops': [
        'error',
        {
          ignoreTypes: true,
        },
      ],
      '@stylistic/function-paren-newline': [
        'error',
        'consistent',
      ],
      'no-mixed-spaces-and-tabs': 'error',
      'no-console': 'off',
      'antfu/top-level-function': 'off',
    },
  },
  {
    files: ['*.ts', '*.vue'],
    rules: {
      // Note: you must disable the base rule as it can report incorrect errors
      'no-use-before-define': 'off',
      '@typescript-eslint/no-use-before-define': ['error', { functions: false }],
    },
  },
  {
    files: ['**/*.js', '**/*.mjs', '**/*.ts', '**/*.cts', '**/*.mts', '**/*.tsx'],
    rules: {
      '@stylistic/function-paren-newline': [
        'error',
        'consistent',
      ],
      '@stylistic/semi': ['error', 'always'],
      '@stylistic/space-infix-ops': [
        'error',
        {
          ignoreTypes: true,
        },
      ],
      '@stylistic/implicit-arrow-linebreak': 'off',
      '@stylistic/member-delimiter-style': [
        'error',
        {
          multiline: {
            delimiter: 'semi',
          },
        },
      ],
      '@stylistic/indent': ['error', 2],
      '@stylistic/indent-binary-ops': ['error', 2],
      '@stylistic/padded-blocks': 'off',
      // 'n/no-unpublished-import': 'off',
      // 'n/no-unpublished-require': 'off',
      'no-tabs': ['error', {
        allowIndentationTabs: false,
      }],
      indent: ['error', 2],
      'no-mixed-spaces-and-tabs': 'error',
      semi: ['error', 'always'],
      'no-console': 'off',
      // 'n/no-missing-require': ['error', {
      //   resolvePaths: ['./src', './style', './'],
      //   tryExtensions: ['.js', '.json', '.node', '.css', '.scss', '.ts', '.xml', '.vue'],
      // }],
      'antfu/top-level-function': 'off',
      '@stylistic/operator-linebreak': [
        'error',
        'after',
        {
          overrides: {
            '=': 'after',
            '|': 'before',
            '||': 'before',
            '&&': 'before',
            '?': 'before',
            '+': 'before',
            ':': 'before',
          },
        },
      ],
    },
  },
  globalIgnores([
    // not all toolkit files are actually used in this project (git subrepo
    'src/toolkit/services/entity-factory.ts',
    'src/toolkit/services/entity-repository.ts',
    // 'src/toolkit/types/axios-type-guards.ts',
    // 'src/toolkit/types/errors.ts',
    // 'src/toolkit/types/event-bus.d.ts',
    // 'src/toolkit/types/nextcloud-files.d.ts',
    'src/toolkit/types/nextcloud.d.ts',
    'src/toolkit/types/type-traits.ts',
    // 'src/toolkit/types/vue-shim.d.ts',
    'src/toolkit/util/axios-file-download.ts',
    // 'src/toolkit/util/cloud-version-classes.ts',
    // 'src/toolkit/util/console.ts',
    // 'src/toolkit/util/dialog-alert.ts',
    // 'src/toolkit/util/dialog-confirm.ts',
    // 'src/toolkit/util/file-node-busy-indicator.ts',
    // 'src/toolkit/util/generate-url.ts',
    // 'src/toolkit/util/initial-state.ts',
    // 'src/toolkit/util/on-document-loaded.ts',
    // 'src/toolkit/util/pangram.ts',
    // 'src/toolkit/util/settings-sync.ts',
    // 'src/toolkit/util/file-node-helper.ts',
  ]),
  {
    files: ['**/*.vue'],

    rules: {
    },
  },
  {
    files: [
      '**/*.ts',
      '**/*.cts',
      '**/*.mts',
      '**/*.tsx',
      '**/*.vue',
    ],
    rules: {
      '@typescript-eslint/no-unused-vars': [
        'warn',
        {
          argsIgnorePattern: '^_',
        },
      ],
    },
  },
];

// console.info(configOptions);

export default defineConfig(configOptions);
