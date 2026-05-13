module.exports = {
  plugins: [
    '@babel/plugin-syntax-dynamic-import',
    '@babel/plugin-transform-class-properties',
    '@babel/plugin-transform-private-methods',
  ],
  presets: [
    // https://babeljs.io/docs/en/babel-preset-typescript
    '@babel/preset-typescript',
    [
      '@babel/preset-env',
      {
        useBuiltIns: false,
        modules: 'auto',
      },
    ],
  ],
  env: {
    development: {
      compact: false,
    },
  },
};
