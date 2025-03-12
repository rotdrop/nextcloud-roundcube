const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except');
const CssoWebpackPlugin = require('csso-webpack-plugin').default;
const DeadCodePlugin = require('webpack-deadcode-plugin');
const ESLintPlugin = require('eslint-webpack-plugin');
const fs = require('fs');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const webpack = require('webpack');
const webpackConfig = require('@nextcloud/webpack-vue-config');
const Visualizer = require('webpack-visualizer-plugin2');
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
  'admin-settings': path.join(__dirname, 'src', 'admin-settings.ts'),
  'personal-settings': path.join(__dirname, 'src', 'personal-settings.ts'),
  app: path.join(__dirname, 'src', 'app.ts'),
};

webpackConfig.output = {
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
      // 'src/toolkit/**',
      'src/toolkit/util/ajax.js',
      'src/toolkit/util/dialogs.js',
      'src/toolkit/util/file-download.js',
      'src/toolkit/util/file-node-helper.js',
      'src/toolkit/util/generate-url.js',
      'src/toolkit/util/jquery.js',
      'src/toolkit/util/on-document-loaded.js',
      'src/toolkit/util/pangram.js',
      'src/toolkit/util/print-r.js',
    ],
  }),
  new BundleAnalyzerPlugin({
    analyzerPort: 11111,
    analyzerMode: 'static',
    openAnalyzer: false,
    reportFilename: './statistics/bundle-analyzer.html',
  }),
  new Visualizer({
    filename: './statistics/visualizer-stats.html',
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
          additionalData: '$appName: ' + appName + ';'
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
    exclude: BabelLoaderExcludeNodeModulesExcept([
      'vue-material-design-icons',
      'emoji-mart-vue-fast',
      '@rotdrop/nextcloud-vue-components',
      '@nextcloud/vue',
    ]),
  },
  {
    test: /\.tsx?$/,
    use: [
      'babel-loader',
      {
        // Fix TypeScript syntax errors in Vue
        loader: 'ts-loader',
        options: {
          transpileOnly: true,
        },
      },
    ],
    exclude: BabelLoaderExcludeNodeModulesExcept([
      '@rotdrop/nextcloud-vue-components',
    ]),
  },
  {
    test: /\.js$/,
    loader: 'babel-loader',
    exclude: BabelLoaderExcludeNodeModulesExcept([
      '@nextcloud/dialogs',
      '@nextcloud/event-bus',
      'davclient.js',
      'nextcloud-vue-collections',
      'p-finally',
      'p-limit',
      'p-locate',
      'p-queue',
      'p-timeout',
      'p-try',
      'semver',
      'striptags',
      'toastify-js',
      'v-tooltip',
      'yocto-queue',
    ]),
  },
];

webpackConfig.resolve.modules = [
  path.resolve(__dirname, 'style'),
  path.resolve(__dirname, 'src'),
  path.resolve(__dirname, '.'),
  'node_modules',
];

webpackConfig.stats = {
  errorDetails: true,
};

module.exports = webpackConfig;
