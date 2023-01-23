const path = require('path');
const webpack = require('webpack');
const webpackConfig = require('@nextcloud/webpack-vue-config');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssoWebpackPlugin = require('csso-webpack-plugin').default;
const ESLintPlugin = require('eslint-webpack-plugin');
const DeadCodePlugin = require('webpack-deadcode-plugin');
const fs = require('fs');
const xml2js = require('xml2js');

const infoFile = path.join(__dirname, 'appinfo/info.xml');
let appInfo;
xml2js.parseString(fs.readFileSync(infoFile), function(err, result) {
  if (err) {
    throw err;
  }
  appInfo = result;
});
const appName = appInfo.info.id[0];
const productionMode = process.env.NODE_ENV === 'production';

webpackConfig.entry = {
  'admin-settings': path.join(__dirname, 'src', 'admin-settings.js'),
  'personal-settings': path.join(__dirname, 'src', 'personal-settings.js'),
  app: path.join(__dirname, 'src', 'app.js'),
  error: path.join(__dirname, 'src', 'error.js'),
};

webpackConfig.output = {
  // path: path.resolve(__dirname, 'js'),
  path: path.resolve(__dirname, '.'),
  publicPath: '',
  filename: 'js/[name]-[contenthash].js',
  assetModuleFilename: 'js/assets/[name]-[hash][ext][query]',
  chunkFilename: 'js/chunks/[name]-[contenthash].js',
  clean: false,
  compareBeforeEmit: true, // true would break the Makefile
};

webpackConfig.plugins = webpackConfig.plugins.concat([
  new webpack.DefinePlugin({
    APP_NAME: JSON.stringify(appName),
  }),
  new ESLintPlugin({
    extensions: ['js', 'vue'],
    exclude: [
      'node_modules',
      '3rdparty',
      'src/legacy',
    ],
  }),
  new HtmlWebpackPlugin({
    inject: false,
    filename: 'js/asset-meta.json',
    minify: false,
    templateContent(arg) {
      return JSON.stringify(arg.htmlWebpackPlugin.files, null, 2);
    },
  }),
  new webpack.ProvidePlugin({
    $: 'jquery',
    jQuery: 'jquery',
    jquery: 'jquery',
    'window.$': 'jquery',
    'window.jQuery': 'jquery',
  }),
  new MiniCssExtractPlugin({
    filename: 'css/[name]-[contenthash].css',
  }),
  new CssoWebpackPlugin(
    {
      pluginOutputPostfix: productionMode ? null : 'min',
    },
    productionMode ? /\.css$/ : /^$/
  ),
  new DeadCodePlugin({
    patterns: [
      'src/**/*.(js|jsx|css)',
      'style/**/*.scss',
    ],
    exclude: [
      'src/toolkit/**',
    ],
  }),
]);

// webpackConfig.module.rules = webpackConfig.module.rules.concat([
webpackConfig.module.rules = [
  {
    test: /\.xml$/i,
    use: 'xml-loader',
  },
  {
    test: /\.css$/,
    use: [
      // 'style-loader',
      MiniCssExtractPlugin.loader,
      'css-loader',
    ],
  },
  {
    test: /\.s(a|c)ss$/,
    use: [
      // 'style-loader',
      MiniCssExtractPlugin.loader,
      'css-loader',
      {
        loader: 'sass-loader',
        options: {
          // Prefer `dart-sass`
          implementation: require('sass'),
          additionalData: '$appName: ' + appName + '; $cssPrefix: ' + appName + '-;',
        },
      },
    ],
  },
  {
    test: /\.(jpe?g|png|gif)$/i,
    type: 'asset', // 'asset/resource',
    generator: {
      filename: './css/img/[name]-[hash][ext]',
      publicPath: '../',
    },
  },
  {
    test: /\.svg$/i,
    use: 'svgo-loader',
    type: 'asset', // 'asset/resource',
    generator: {
      filename: './css/img/[name]-[hash][ext]',
      publicPath: '../',
    },
  },
  {
    test: /\.vue$/,
    loader: 'vue-loader',
  },
];

webpackConfig.resolve.modules = [
  path.resolve(__dirname, 'node_modules'),
  path.resolve(__dirname, 'style'),
  path.resolve(__dirname, 'src'),
  path.resolve(__dirname, '.'),
];

module.exports = webpackConfig;
