const defaultConfig = require("./node_modules/@wordpress/scripts/config/webpack.config");    // Require default Webpack config
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const TerserJSPlugin = require('terser-webpack-plugin');
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const RemovePlugin = require('remove-files-webpack-plugin');
const SpriteLoaderPlugin = require('svg-sprite-loader/plugin');
const dashDash = require('@greenpeace/dashdash');

const mediaQueryAliases = {
  '(max-width: 576px)': 'mobile-only',
  '(min-width: 576px)': 'small-and-up',
  '(min-width: 768px)': 'medium-and-up',
  '(min-width: 992px)': 'large-and-up',
  '(min-width: 1200px)': 'x-large-and-up',
  '(min-width: 1600px)': 'xx-large-and-up',
};

<<<<<<< Updated upstream
module.exports = {
  ...defaultConfig,
  entry: {
    index: './assets/src/js/app.js',
    style: './assets/src/scss/style.scss',
    post: './assets/src/scss/post.scss',
    editorStyle: './assets/src/scss/editorStyle.scss',
    bootstrap: './assets/src/scss/bootstrap-build.scss',
    "country-selector": './assets/src/scss/partials/country-selector.scss',
    "gravity-forms": './assets/src/scss/layout/_gravity-forms.scss',
    "gravityforms-client-side": './assets/src/js/gravityforms-client-side.js',
    media_archive: './assets/src/js/media_archive.js',
    media_archive_editor_view: './assets/src/js/media_archive_editor_view.js',
    "lite-yt-embed": './node_modules/lite-youtube-embed/src/lite-yt-embed.js',
    menu_editor: './assets/src/js/menu_editor.js',
    frontendIndex: './assets/src/frontendIndex.js',
    editorIndex: './assets/src/editorIndex.js',
    ArticlesScript: './assets/src/blocks/Articles/ArticlesScript.js',
    ArticlesEditorScript: './assets/src/blocks/Articles/ArticlesEditorScript.js',
    GalleryScript: './assets/src/blocks/Gallery/GalleryScript.js',
    GalleryEditorScript: './assets/src/blocks/Gallery/GalleryEditorScript.js',
    GalleryStyle: './assets/src/scss/blocks/Gallery/GalleryStyle.scss',
    GalleryEditorStyle: './assets/src/scss/blocks/Gallery/GalleryEditorStyle.scss',
    GuestBookScript: './assets/src/blocks/GuestBook/GuestBookScript.js',
    GuestBookEditorScript: './assets/src/blocks/GuestBook/GuestBookEditorScript.js',
    CarouselHeaderScript: './assets/src/blocks/CarouselHeader/CarouselHeaderScript.js',
    CarouselHeaderEditorScript: './assets/src/blocks/CarouselHeader/CarouselHeaderEditorScript.js',
    AccordionScript: './assets/src/blocks/Accordion/AccordionScript.js',
    AccordionEditorScript: './assets/src/blocks/Accordion/AccordionEditorScript.js',
    CookiesScript: './assets/src/blocks/Cookies/CookiesScript.js',
    CookiesEditorScript: './assets/src/blocks/Cookies/CookiesEditorScript.js',
    CounterScript: './assets/src/blocks/Counter/CounterScript.js',
    CoversEditorScript: './assets/src/blocks/Covers/CoversEditorScript.js',
    CoversScript: './assets/src/blocks/Covers/CoversScript.js',
    CounterEditorScript: './assets/src/blocks/Counter/CounterEditorScript.js',
    SpreadsheetScript: './assets/src/blocks/Spreadsheet/SpreadsheetScript.js',
    SpreadsheetEditorScript: './assets/src/blocks/Spreadsheet/SpreadsheetEditorScript.js',
    SocialMediaEditorScript: './assets/src/blocks/SocialMedia/SocialMediaEditorScript.js',
    SocialMediaStyle: './assets/src/scss/blocks/SocialMedia/SocialMediaStyle.scss',
    SocialMediaEditorStyle: './assets/src/scss/blocks/SocialMedia/SocialMediaEditorStyle.scss',
    ENFormScript: './assets/src/blocks/ENForm/ENFormScript.js',
    ENFormEditorScript: './assets/src/blocks/ENForm/ENFormEditorScript.js',
    TimelineScript: './assets/src/blocks/Timeline/TimelineScript.js',
    TimelineEditorScript: './assets/src/blocks/Timeline/TimelineEditorScript.js',
    TimelineStyle: './assets/src/scss/blocks/Timeline/TimelineStyle.scss',
    TimelineEditorStyle: './assets/src/scss/blocks/Timeline/TimelineEditorStyle.scss',
  },
  output: {
    filename: '[name].js',
    path: __dirname + '/assets/build'
  },
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules,
      {
        test: /\.(sass|scss)$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
=======
module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    //stats: 'verbose',
    ...defaultConfig,
    mode: argv.mode || 'development',
    devtool: isProduction ? false : 'source-map',
    entry: {
      index: './assets/src/js/app.js',
      style: './assets/src/scss/style.scss',
      // post: './assets/src/scss/post.scss',
      // editorStyle: './assets/src/scss/editorStyle.scss',
      // bootstrap: './assets/src/scss/bootstrap-build.scss',
      // 'country-selector': './assets/src/scss/partials/country-selector.scss',
      // 'gravity-forms': './assets/src/scss/layout/_gravity-forms.scss',
      'gravityforms-client-side': './assets/src/js/gravityforms-client-side.js',
      media_archive: './assets/src/js/media_archive.js',
      media_archive_editor_view: './assets/src/js/media_archive_editor_view.js',
      'lite-yt-embed': './node_modules/lite-youtube-embed/src/lite-yt-embed.js',
      menu_editor: './assets/src/js/menu_editor.js',
      frontendIndex: './assets/src/frontendIndex.js',
      editorIndex: './assets/src/editorIndex.js',
      ArticlesScript: './assets/src/blocks/Articles/ArticlesScript.js',
      ArticlesEditorScript: './assets/src/blocks/Articles/ArticlesEditorScript.js',
      GalleryScript: './assets/src/blocks/Gallery/GalleryScript.js',
      GalleryEditorScript: './assets/src/blocks/Gallery/GalleryEditorScript.js',
      // GalleryStyle: './assets/src/scss/blocks/Gallery/GalleryStyle.scss',
      // GalleryEditorStyle: './assets/src/scss/blocks/Gallery/GalleryEditorStyle.scss',
      GuestBookScript: './assets/src/blocks/GuestBook/GuestBookScript.js',
      GuestBookEditorScript: './assets/src/blocks/GuestBook/GuestBookEditorScript.js',
      CarouselHeaderScript: './assets/src/blocks/CarouselHeader/CarouselHeaderScript.js',
      CarouselHeaderEditorScript: './assets/src/blocks/CarouselHeader/CarouselHeaderEditorScript.js',
      AccordionScript: './assets/src/blocks/Accordion/AccordionScript.js',
      AccordionEditorScript: './assets/src/blocks/Accordion/AccordionEditorScript.js',
      CookiesScript: './assets/src/blocks/Cookies/CookiesScript.js',
      CookiesEditorScript: './assets/src/blocks/Cookies/CookiesEditorScript.js',
      CounterScript: './assets/src/blocks/Counter/CounterScript.js',
      CounterEditorScript: './assets/src/blocks/Counter/CounterEditorScript.js',
      CoversEditorScript: './assets/src/blocks/Covers/CoversEditorScript.js',
      CoversScript: './assets/src/blocks/Covers/CoversScript.js',
      SpreadsheetScript: './assets/src/blocks/Spreadsheet/SpreadsheetScript.js',
      SpreadsheetEditorScript: './assets/src/blocks/Spreadsheet/SpreadsheetEditorScript.js',
      SocialMediaEditorScript: './assets/src/blocks/SocialMedia/SocialMediaEditorScript.js',
      // SocialMediaStyle: './assets/src/scss/blocks/SocialMedia/SocialMediaStyle.scss',
      // SocialMediaEditorStyle: './assets/src/scss/blocks/SocialMedia/SocialMediaEditorStyle.scss',
      ENFormScript: './assets/src/blocks/ENForm/ENFormScript.js',
      ENFormEditorScript: './assets/src/blocks/ENForm/ENFormEditorScript.js',
      TimelineScript: './assets/src/blocks/Timeline/TimelineScript.js',
      TimelineEditorScript: './assets/src/blocks/Timeline/TimelineEditorScript.js',
      // TimelineStyle: './assets/src/scss/blocks/Timeline/TimelineStyle.scss',
      // TimelineEditorStyle: './assets/src/scss/blocks/Timeline/TimelineEditorStyle.scss',
    },
    output: {
      filename: '[name].js',
      path: __dirname + '/assets/build',
    },
    module: {
      rules: [
        {
          test: /\.(j|t)sx?$/,
          exclude: /node_modules/,
          use: [
            {
              loader: require.resolve('babel-loader'),
              options: {
                babelrc: false,
                configFile: false,
                presets: [
                  require.resolve('@wordpress/babel-preset-default'),
                ],
              },
            },
          ],
        },
        {
          test: /\.(sass|scss)$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                url: false,
                sourceMap: !isProduction,
              },
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: !isProduction,
                postcssOptions: {
                  ident: 'postcss',
                  plugins: [
                    dashDash({mediaQueryAliases, mediaQueryAtStart: false}),
                    require.resolve('autoprefixer'),
                  ],
                },
              },
            },
            {
              loader: 'sass-loader',
              options: {
                implementation: require.resolve('sass'),
                sourceMap: !isProduction,
              },
            },
          ],
        },
        {
          test: /icons\/.*\.svg$/,
          use: [{
            loader: 'svg-sprite-loader',
>>>>>>> Stashed changes
            options: {
              url: false,
              sourceMap: true,
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              ident: 'postcss',
              plugins: () => [
                dashDash({ mediaQueryAliases, mediaQueryAtStart: false }),
                require('autoprefixer'),
              ],
              sourceMap: true,
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
            }
          }
        ]
      },
      {
        test: /icons\/.*\.svg$/,
        loader: 'svg-sprite-loader',
        options: {
          extract: true,
          spriteFilename: '../../assets/build/sprite.symbol.svg',
          runtimeCompat: true
        }
      },
    ]
  },
  plugins: [
    ...defaultConfig.plugins,
    // extract css into dedicated file
    new FixStyleOnlyEntriesPlugin(),
    new MiniCssExtractPlugin({
      chunkFilename: '[id].css',
      ignoreOrder: false, // Enable to remove warnings about conflicting order
      filename: './[name].min.css'
    }),
    new RemovePlugin({
      /**
       * After compilation removes all files in `dist/styles` folder,
       * that have `.map` type.
       */
      after: {
        test: [
          {
            folder: 'assets/build/',
            method: (filePath) => {
              return [
                'style.deps.json',
                'index.asset.php',
                'bootstrap.asset.php',
                'style.asset.php',
              ].filter(item => {
                return new RegExp(item, 'm').test(filePath);
              }).length > 0;
            }
          }
        ]
      }
    }),
    new SpriteLoaderPlugin({
      plainSprite: true
    }),
  ],
  optimization: {
    ...defaultConfig.optimization,
    minimizer: [
      // enable the css minification plugin
      new TerserJSPlugin({}),
      new OptimizeCSSAssetsPlugin({
        cssProcessorOptions: {
          sourceMap: true,
          map: {
            inline: false,
            annotation: true,
          }
        }
      })
    ]
  }
};
