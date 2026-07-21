export interface EmbedInfo {
    provider: string;
    id: string;
    embedType?: string;
}

const SPOTIFY_TYPES = ['track', 'album', 'playlist', 'artist', 'episode', 'show'];

/**
 * Extrae el proveedor + id (+ tipo para Spotify) desde una URL pegada.
 *
 * Detecta el proveedor por el host de la URL, usando `providerHint` (el valor
 * del desplegable) solo como respaldo. Soporta los prefijos de idioma de
 * Spotify, p. ej. `https://open.spotify.com/intl-es/track/<id>`.
 */
export function parseEmbed(url: string, providerHint?: string): EmbedInfo {
    const empty: EmbedInfo = { provider: providerHint ?? '', id: '' };

    let parsed: URL;
    try {
        parsed = new URL(url);
    } catch {
        return empty;
    }

    const host = parsed.hostname.replace(/^www\./, '');
    const parts = parsed.pathname.split('/').filter(Boolean);

    if (host.includes('spotify.com') || providerHint === 'spotify') {
        // Busca el segmento de tipo (track/album/…) para saltear prefijos /intl-es/.
        const typeIndex = parts.findIndex((part) => SPOTIFY_TYPES.includes(part));
        if (typeIndex !== -1 && parts[typeIndex + 1]) {
            return { provider: 'spotify', embedType: parts[typeIndex], id: parts[typeIndex + 1] };
        }

        return { provider: 'spotify', id: '' };
    }

    if (host.includes('youtu.be') || host.includes('youtube.com') || providerHint === 'youtube') {
        if (host.includes('youtu.be')) {
            return { provider: 'youtube', id: parts[0] ?? '' };
        }
        if (parts[0] === 'embed' || parts[0] === 'shorts') {
            return { provider: 'youtube', id: parts[1] ?? '' };
        }
        const v = parsed.searchParams.get('v');
        if (v) {
            return { provider: 'youtube', id: v };
        }

        return { provider: 'youtube', id: '' };
    }

    if (host.includes('tiktok.com') || providerHint === 'tiktok') {
        return { provider: 'tiktok', id: parts[parts.length - 1] ?? '' };
    }

    return empty;
}
