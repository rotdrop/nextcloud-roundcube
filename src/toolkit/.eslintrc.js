module.exports = {
  settings: {
    jsdoc: {
      tagNamePreference: {
        returns: 'return',
      },
    },
  },
  rules: {
    'operator-linebreak': [
      'error',
      'before',
      {
        overrides: {
          '=': 'after',
          '+=': 'after',
          '-=': 'after',
        },
      },
    ],
    indent: ['error', 2, { SwitchCase: 1 }],
  },
};
