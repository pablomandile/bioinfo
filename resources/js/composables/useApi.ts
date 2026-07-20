function readCookie(name: string): string | null {
    const escaped = name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1');
    const match = document.cookie.match(new RegExp('(^|; )' + escaped + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Cliente HTTP JSON para los endpoints del editor. Usa la sesión y el token
 * CSRF (cookie XSRF-TOKEN) que Laravel ya expone en las respuestas web.
 */
export async function api<T = unknown>(method: string, url: string, body?: unknown): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const xsrf = readCookie('XSRF-TOKEN');
    if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
    }

    let payload: BodyInit | undefined;
    if (body instanceof FormData) {
        payload = body;
    } else if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const response = await fetch(url, { method, headers, body: payload, credentials: 'same-origin' });

    if (!response.ok) {
        let message = `Error ${response.status}`;
        try {
            const data = await response.json();
            message = (data?.message as string) ?? message;
        } catch {
            // respuesta sin cuerpo JSON
        }
        throw new Error(message);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}

export function debounce<A extends unknown[]>(fn: (...args: A) => void, wait = 700): (...args: A) => void {
    let timer: ReturnType<typeof setTimeout> | undefined;
    return (...args: A) => {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => fn(...args), wait);
    };
}
