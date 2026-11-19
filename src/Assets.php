<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Resolves Vite-built assets from the Vite manifest and registers them with WordPress.
 *
 * Vite writes the manifest to build/.vite/manifest.json; it maps each source entry
 * to its hashed build output. The entry keys looked up here must match the inputs
 * declared in vite.config.ts (build.rollupOptions.input).
 */
final class Assets
{
    public const HANDLE = 'itkt-app';

    private const ENTRY_CSS = 'resources/css/app.css';

    private const ENTRY_JS = 'resources/js/app.tsx';

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $manifest = null;

    public function register(): void
    {
        $styleUrl = self::manifestUrl(self::ENTRY_CSS);
        if (is_string($styleUrl)) {
            wp_register_style(self::HANDLE, $styleUrl, [], WP_INFOMANIAK_TICKETING_VERSION);
        }

        $scriptUrl = self::manifestUrl(self::ENTRY_JS);
        if (is_string($scriptUrl)) {
            wp_register_script(
                self::HANDLE,
                $scriptUrl,
                [],
                WP_INFOMANIAK_TICKETING_VERSION,
                [
                    'in_footer' => true,
                    'strategy' => 'defer',
                ],
            );
        }
    }

    /**
     * Resolves a Vite entry to its built asset URL, or null when the manifest is
     * missing or the entry was not built.
     */
    private static function manifestUrl(string $entry): ?string
    {
        $entryData = self::manifest()[$entry] ?? null;

        if (! is_array($entryData) || ! is_string($entryData['file'] ?? null)) {
            return null;
        }

        return plugins_url('/build/'.$entryData['file'], WP_INFOMANIAK_TICKETING_FILE);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $manifestPath = WP_INFOMANIAK_TICKETING_DIR.'/build/.vite/manifest.json';
        $rawManifest = is_readable($manifestPath) ? (string) file_get_contents($manifestPath) : '';
        $decodedManifest = json_decode($rawManifest, true);

        self::$manifest = is_array($decodedManifest) ? $decodedManifest : [];

        return self::$manifest;
    }
}
