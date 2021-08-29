const path = require('path');
const pathToEnv = path.join(__dirname, '../env/.env');
require('dotenv').config({ path: pathToEnv });
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const DotenvWebpack = require('dotenv-webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  let publicPath = process.env.PUBLIC_PATH;
  let apiUrl = process.env.API_URL;
  let oauthClientId = process.env.OAUTH_CLIENT_ID;
  let mode = 'production';
  let isDevelopmentMode = false;

  const distPath = process.env.DIST_PATH || 'dist';
  const templatePath = process.env.TEMPLATE_PATH || 'public';

  // Version/development-based configuration of PUBLIC_PATH and API_URL from env
  if (argv.mode === 'development') {
    mode = argv.mode;
    isDevelopmentMode = true;
    publicPath = '/';
    apiUrl = process.env.LVH_API_URL;
    oauthClientId = process.env.LVH_OAUTH_CLIENT_ID;
  }

  return {
    mode: mode,
    entry: {
      'octoprint-pool': `./src/index.tsx`
      // 'service-worker': './src/service-worker.ts',
    },
    output: {
      path: path.join(__dirname, distPath),
      filename: 'assets/js/[name].js',
      publicPath: publicPath
    },
    module: {
      rules: [
        /**
         * @see {@link https://stackoverflow.com/a/62222479/294171 | Suppress errors on source map refs in node_modules}
         */
        {
          test: /\.js$/,
          enforce: 'pre',
          use: 'source-map-loader'
        },
        {
          test: /\.tsx?$/,
          use: 'ts-loader',
          exclude: /node_modules/
        },
        {
          test: /\.(sa|sc|c)ss$/,
          exclude: /node_modules/,
          use: [
            isDevelopmentMode
              ? 'style-loader'
              : MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                // apply postcss-loader (but not sass-loader) to @imports (sass-loader process @uses)
                importLoaders: 1
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    [
                      'postcss-preset-env',
                      'autoprefixer'
                    ]
                  ]
                }
              }
            },
            {
              loader: 'sass-loader',
              options: {
                implementation: require('sass')
              }
            }
          ]
        },
        {
          test: /\.svg$/,
          use: 'raw-loader'
        },
        {
          test: /\.(jpe?g|gif|png)/,
          loader: 'file-loader',
          options: {
            name: '[name].[contenthash].[ext]',
            outputPath: isDevelopmentMode
              ? undefined
              : path.join('assets', 'images')
          }
        }
      ]
    },
    resolve: {
      extensions: ['.tsx', '.ts', '.js'],
      alias: {
        pwa: path.join(__dirname, 'src/pwa')
      },
      fallback: {
        path: require.resolve('path-browserify'),
        crypto: require.resolve('crypto-browserify'),
        stream: false
      }
    },
    devtool: isDevelopmentMode && 'eval',
    devServer: {
      host:
        argv.host ||
        `${process.env.LVH_SUBDOMAIN || process.env.APP_NAME}.lvh.me`,
      port: argv.port || `${process.env.LVH_PORT || 8080}`,
      contentBase: path.join(__dirname, templatePath),
      historyApiFallback: true,
      allowedHosts: env.allowedHosts
        ? env.allowedHosts.split(';')
        : undefined,
      open: true
    },
    plugins: [
      new CleanWebpackPlugin(),
      new DotenvWebpack({ path: pathToEnv }),
      new CopyWebpackPlugin({
        patterns: [
          {
            from: path.join(__dirname, templatePath),
            filter: path =>
              !/index.html$/.test(path) && // let HtmlWebpackPlugin handle index.html
              !/.gitignore$/.test(path) && // don't publish repo files
              !/README\.md$/.test(path) // don't publish repo files
          },
          {
            from: path.join(__dirname, '../server/public'),
            to: path.join(__dirname, distPath, 'api')
          }
        ]
      }),
      new HtmlWebpackPlugin({
        templateParameters: {
          fontawesome: process.env.FONTAWESOME
        },
        template: path.join(
          __dirname,
          templatePath,
          `index.html`
        ),
        chunks: ['octoprint-pool'], // TODO#DEV make sure all chunks are loaded!
        hash: true
      }),
      new MiniCssExtractPlugin({
        filename: 'assets/css/[name].css'
      }),
      new ImageMinimizerPlugin({
        minimizerOptions: {
          plugins: [
            ['gifsicle', { interlaced: true }],
            ['jpegtran', { progressive: true }],
            ['optipng', { optimizationLevel: 5 }],
            ['svgo', { plugins: [{ removeViewBox: false }] }]
          ]
        }
      }),
      new webpack.DefinePlugin({
        __PUBLIC_PATH__: JSON.stringify(publicPath),
        __API_URL__: JSON.stringify(apiUrl),
        __OAUTH_CLIENT_ID__: JSON.stringify(oauthClientId),
        __DEBUGGING__: JSON.stringify(isDevelopmentMode)
      })
    ],
    optimization: {
      minimize: !isDevelopmentMode,
      minimizer: [`...`, new CssMinimizerPlugin()],

      /*
       * TODO deal with code splitting
       *    https://webpack.js.org/guides/code-splitting/
       */
      splitChunks: {
        chunks: 'all'
      }
    }
  };
};