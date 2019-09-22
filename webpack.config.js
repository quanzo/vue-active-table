const devMode = process.env.NODE_ENV !== 'production';
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const TerserJSPlugin = require('terser-webpack-plugin');

module.exports = {
  entry: './src/bundle.js',
  optimization: {
    minimizer: [
      new TerserJSPlugin({})
    ],
  },
  module: {
    rules: [
      {
		test: /\.vue$/,
        loader: 'vue-loader',
	  },
	  {
        test: /\.js$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            query: {
              presets: [ '@babel/preset-env' ],
            },
          },
        ]
      },
      {
        test: /\.css$/,
        use: [
          'vue-style-loader',
          'css-loader'
        ]
      },
	  {
        test: /\.svg$/,
        loader: 'svg-inline-loader?removeSVGTagAttrs=false'
      }
    ]
  },
  output: {
    path: __dirname + '/dist',
    publicPath: '/',
    filename: 'bundle.js',
    library: 'VueActiveTable',
    libraryTarget: 'umd'
  },
  plugins: [
    new VueLoaderPlugin()
  ]
};
