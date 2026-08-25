module.exports = function (api) {
  api.cache(true);

  const presets = [
    "@babel/preset-react",
    ["@babel/preset-typescript", { allowDeclareFields: true }],
  ];
  const plugins = [];

  return {
    presets,
    plugins
  };
}
