import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import fs from "node:fs";
import path from "node:path";

const rootPath = fs.realpathSync(process.cwd());

export default defineConfig({
    root: rootPath,
    server: {
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: ["resources/css/app.css", "resources/css/admin.css", "resources/js/app.js", "resources/js/admin.js"],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources/js"),
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});


