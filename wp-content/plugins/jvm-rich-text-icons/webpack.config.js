const path = require("path");
const DependencyExtractionWebpackPlugin = require("@wordpress/dependency-extraction-webpack-plugin");

module.exports = {
  mode: "production",
  entry: {
    blocks: path.resolve(__dirname, "src", "blocks.js"),
  },
  output: {
    path: path.resolve(__dirname, "dist"),
    filename: "[name].js",
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: {
          loader: require.resolve("babel-loader"),
          options: {
            presets: [require.resolve("@wordpress/babel-preset-default")],
          },
        },
      },
    ],
  },
  plugins: [new DependencyExtractionWebpackPlugin()],
};
