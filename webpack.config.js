const path = require('path');
const webpack = require('webpack');
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const Visualizer = require('webpack-visualizer-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = {
  entry: {
    app: './src/index.js',
    'personal-settings': './src/personal-settings.js',
    'admin-settings': './src/admin-settings.js',
  },
  output: {
    path: path.resolve(__dirname, '.'),
    filename: 'js/[name].js',
  },
  devtool: 'source-map',
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        cache: true,
        parallel: true,
        sourceMap: true, // Must be set to true if using source-maps in production
        terserOptions: {
          // https://github.com/webpack-contrib/terser-webpack-plugin#terseroptions
        },
      }),
      new CssMinimizerPlugin(),
    ],
  },
  plugins: [
    new BundleAnalyzerPlugin({
      analyzerPort: 11111,
      analyzerMode: 'static',
      openAnalyzer: false,
    }),
    new Visualizer({
      filename: './visualizer-stats.html',
    }),
    new MiniCssExtractPlugin({
      filename: 'css/[name].css',
    }),
  ],
  module: {
    rules: [
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
        test: /\.(jpe?g|png|gif|svg)$/i,
        loader: 'file-loader',
        options: {
          name: '[name].[ext]',
          outputPath: 'css/img/',
          publicPath: 'img',
          useRelativePaths: true,
        },
      },
    ],
  },
  resolve: {
    modules: [
      'node_modules',
      'style',
      'src',
      path.resolve(__dirname, '.'),
    ],
  },
};

/**
 * Local Variables: ***
 * js-indent-level: 2 ***
 * indent-tabs-mode: nil ***
 * End: ***
 */
