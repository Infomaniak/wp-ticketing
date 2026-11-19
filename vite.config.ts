import react from "@vitejs/plugin-react";
import { fileURLToPath } from "node:url";
import { defineConfig } from "vitest/config";

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
    },
    build: {
        outDir: "build",
        manifest: true,
        rollupOptions: {
            // Entry keys land in build/.vite/manifest.json and are looked up by
            // src/Assets.php — keep both sides in sync (see AGENTS.md).
            input: ["resources/js/app.tsx", "resources/css/app.css"],
        },
    },
    test: {
        environment: "jsdom",
        globals: true,
        include: ["resources/js/**/*.{test,spec}.{ts,tsx}"],
        exclude: ["vendor/**", "node_modules/**", "build/**"],
        setupFiles: ["./resources/js/vitest.setup.ts"],
        coverage: {
            provider: "v8",
            reporter: ["text", "lcov"],
            reportsDirectory: "./coverage",
            include: ["resources/js/**/*.{ts,tsx}"],
            exclude: [
                "resources/js/**/*.test.{ts,tsx}",
                "resources/js/**/*.spec.{ts,tsx}",
                "resources/js/**/*.d.ts",
            ],
        },
    },
});
