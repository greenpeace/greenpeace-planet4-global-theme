const {dirname, extname, join, sep} = require('path');
const {readFileSync} = require('fs');
const {sync: glob} = require('fast-glob');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const scriptFields = new Set(['viewScript', 'script', 'editorScript']);
const fromProjectRoot = fileName => join(dirname(dirname(__dirname)), fileName);
const srcDir = 'assets/src/';
const blocksDir = 'assets/src/blocks';

/**
 * @param {Object} blockJson
 * @return {null|Record<string, unknown>} Fields
 */
function getBlockJsonScriptFields(blockJson) {
  let result = null;
  for (const field of scriptFields) {
    if (Object.hasOwn(blockJson, field)) {
      if (!result) {
        result = {};
      }
      result[field] = blockJson[field];
    }
  }
  return result;
}

const getBlocksEntries = () => {
  const blockMetadataFiles = glob('**/block.json', {
    absolute: true,
    cwd: fromProjectRoot(blocksDir),
  });

  if (blockMetadataFiles.length <= 0) {
    return {};
  }

  const srcDirectory = fromProjectRoot(srcDir);
  const entryPoints = {};

  for (const blockMetadataFile of blockMetadataFiles) {
    const fileContents = readFileSync(blockMetadataFile);
    let parsedBlockJson;
    // wrapping in try/catch in case the file is malformed
    // this happens especially when new block.json files are added
    // at which point they are completely empty and therefore not valid JSON
    try {
      parsedBlockJson = JSON.parse(fileContents);
    } catch (error) {
      console.log( //eslint-disable-line no-console
        `Skipping "${blockMetadataFile.replace(
          fromProjectRoot(sep), ''
        )}" due to malformed JSON.`
      ); //eslint-disable-line no-console
    }

    const fields = getBlockJsonScriptFields(parsedBlockJson);

    if (!fields) {
      continue;
    }

    for (const value of Object.values(fields).flat()) {
      if (!value.startsWith('file:')) {
        continue;
      }

      // Removes the `file:` prefix.
      const filepath = join(
        dirname(blockMetadataFile),
        value.replace('file:', '')
      );

      // Takes the path without the file extension, and relative to the defined source directory.
      if (!filepath.startsWith(srcDirectory)) {
        console.log( //eslint-disable-line no-console
          `Skipping "${filepath}" listed in "${blockMetadataFile.replace(
            fromProjectRoot(sep), ''
          )}". File is located outside of the "${srcDirectory}" directory.`
        );
        return;
      }
      const entryName = filepath
        .replace(extname(filepath), '')
        .replace(srcDirectory, '')
        .replace(/\\/g, '/');

      // Detects the proper file extension used in the defined source directory.
      const [entryFilepath] = glob(
        `${entryName}.js`,
        {
          absolute: true,
          cwd: srcDirectory,
        }
      );

      if (!entryFilepath) {
        console.log( //eslint-disable-line no-console
          `Skipping "${entryFilepath}" listed in "${blockMetadataFile.replace(
            fromProjectRoot(sep), ''
          )}". File does not exist in the "${srcDirectory}" directory.`
        );
        return;
      }
      entryPoints[entryName] = entryFilepath;
    }
  }

  if (Object.keys(entryPoints).length > 0) {
    console.log(entryPoints); //eslint-disable-line no-console
    return entryPoints;
  }
};

module.exports = {
  ...defaultConfig,
  entry: getBlocksEntries(),
  plugins: [
    ...defaultConfig.plugins,
    new CopyWebpackPlugin({
      patterns: [
        {
          context: srcDir,
          from: 'blocks/**/block.json',
          noErrorOnMissing: true,
        },
      ],
    }),
  ],
};
