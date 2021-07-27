require('dotenv').config({ path: '../env/.env' });
const PwaAssetGenerator = require('pwa-asset-generator');
const path = require('path');
const minify = require('html-minifier-terser').minify;
const fs = require('fs');

const srcPath = path.join(process.cwd(), 'src/pwa');
const distPath = path.join(process.cwd(), process.env.DIST_PATH);
const index = path.join(distPath, 'index.html');
const options = {
    path: process.env.PUBLIC_PATH,
    index,
    manifest: path.join(distPath, 'assets/manifest.json'),
    scrape: false
};

const cache = `.${path.basename(__filename, '.js')}.cache`;
fs.readFile(cache, (error, data) => {
    if (
        data === undefined ||
        new Date(Date.now() - process.env.PWA_SCRAPE_CACHE) >
            new Date(data.toString())
    ) {
        options.scrape = true;
        fs.writeFile(cache, new Date().toISOString(), () => true);
    }

    PwaAssetGenerator.generateImages(
        path.join(srcPath, 'icon/icon-assets/icon.png'),
        path.join(distPath, 'assets/icons'),
        {
            ...options,
            iconOnly: true,
            favicon: true,
            mstile: true,
            padding: '0px',
            background: process.env.PWA_ICON_BACKGROUND
        }
    ).then(() => {
        PwaAssetGenerator.generateImages(
            path.join(srcPath, 'launch/launch-assets/launch.png'),
            path.join(distPath, 'assets/launch'),
            {
                ...options,
                splashOnly: true,
                background: process.env.PWA_LAUNCH_BACKGROUND
            }
        ).then(() => {
            // re-minify HTML after inserting meta and link tags
            fs.readFile(index, (err, data) => {
                fs.writeFile(
                    index,
                    minify(data.toString(), {
                        // html-webpack-plugin defaults -- 2021-01-02
                        collapseWhitespace: true,
                        keepClosingSlash: true,
                        removeComments: true,
                        removeRedundantAttributes: true,
                        removeScriptTypeAttributes: true,
                        removeStyleLinkTypeAttributes: true,
                        useShortDoctype: true
                    }),
                    error => {
                        if (error) {
                            console.error(error);
                        }
                    }
                );
            });
        });
    });
});
