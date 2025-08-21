module.exports = {
  entry: './public/js/dashboard.js',
  output: {
    path: __dirname + '/public/js',
    filename: 'bundle.js'
  },
  mode: 'development',
  module: {
    rules: [
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }
    ]
  },
  watch: true,
  watchOptions: {
    ignored: /node_modules/
  }
};