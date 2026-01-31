import { wayfinder } from "@laravel/vite-plugin-wayfinder";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import laravel from "laravel-vite-plugin";
import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    return {
        server: {
            port: parseInt(env.VITE_PORT || "5173"),
            host: "0.0.0.0",
            hmr: {
                host: "localhost",
            },
            cors: true,
        },
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/app.tsx"],
                ssr: "resources/js/ssr.tsx",
                refresh: true,
            }),
            react({
                babel: {
                    plugins: ["babel-plugin-react-compiler"],
                },
            }),
            tailwindcss(),
            wayfinder({
                formVariants: true,
            }),
        ],
        esbuild: {
            jsx: "automatic",
        },
    };
});
