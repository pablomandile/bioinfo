function readCookie(name: string): string | null {
    const escaped = name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1');
    const match = document.cookie.match(new RegExp('(^|; )' + escaped + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : null;
}

/** Reintentos ante fallos de red (NO ante respuestas HTTP de error). */
const NETWORK_RETRIES = 2;
const RETRY_BACKOFF_MS = 300;

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

async function sendRequest<T>(method: string, url: string, body?: unknown): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Muchos servidores (Apache + mod_security, LiteSpeed, hostings compartidos)
    // rechazan los métodos PATCH/PUT/DELETE con "400 Bad Request" antes de que la
    // petición llegue a la app. Los enviamos como POST + X-HTTP-Method-Override,
    // que Laravel interpreta de forma nativa como el método real.
    const verb = method.toUpperCase();
    let httpMethod = verb;
    if (verb === 'PATCH' || verb === 'PUT' || verb === 'DELETE') {
        headers['X-HTTP-Method-Override'] = verb;
        httpMethod = 'POST';
    }

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

    for (let attempt = 0; ; attempt++) {
        let response: Response;

        try {
            response = await fetch(url, { method: httpMethod, headers, body: payload, credentials: 'same-origin' });
        } catch {
            // fetch() lanza TypeError ("Failed to fetch") cuando la petición ni siquiera
            // llegó al servidor (conexión rechazada/reseteada). En ese caso la request no
            // se procesó, así que es seguro reintentarla con un pequeño backoff.
            if (attempt < NETWORK_RETRIES) {
                await sleep(RETRY_BACKOFF_MS * (attempt + 1));
                continue;
            }
            throw new Error('No se pudo guardar: sin conexión con el servidor. Reintentá en unos segundos.');
        }

        if (!response.ok) {
            if (response.status === 419) {
                throw new Error('Tu sesión expiró. Recargá la página para seguir editando.');
            }

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
}

/**
 * Cola de escrituras. El servidor de desarrollo (`php artisan serve`) usa un único
 * worker de PHP: dos peticiones de escritura solapadas pueden pisarse y caerse con
 * "Failed to fetch". Serializándolas garantizamos que solo haya una en vuelo a la vez.
 */
let writeChain: Promise<unknown> = Promise.resolve();

/**
 * Cliente HTTP JSON para los endpoints del editor. Usa la sesión y el token
 * CSRF (cookie XSRF-TOKEN) que Laravel ya expone en las respuestas web.
 *
 * Las escrituras (todo lo que no sea GET/HEAD) se encolan para no solaparse, y
 * los fallos de red se reintentan automáticamente.
 */
export function api<T = unknown>(method: string, url: string, body?: unknown): Promise<T> {
    const verb = method.toLowerCase();

    if (verb === 'get' || verb === 'head') {
        return sendRequest<T>(method, url, body);
    }

    const result = writeChain.then(() => sendRequest<T>(method, url, body));
    // La cadena debe seguir viva aunque una petición falle.
    writeChain = result.catch(() => undefined);
    return result;
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

/**
 * Como {@link debounce}, pero mantiene un temporizador independiente por clave. Así,
 * editar dos entidades distintas (p. ej. dos bloques o dos redes) dentro de la ventana
 * de espera no cancela el guardado de la primera.
 */
export function keyedDebounce<A extends unknown[]>(
    fn: (...args: A) => void,
    keyOf: (...args: A) => string | number,
    wait = 700,
): (...args: A) => void {
    const timers = new Map<string | number, ReturnType<typeof setTimeout>>();
    return (...args: A) => {
        const key = keyOf(...args);
        const existing = timers.get(key);
        if (existing) {
            clearTimeout(existing);
        }
        timers.set(
            key,
            setTimeout(() => {
                timers.delete(key);
                fn(...args);
            }, wait),
        );
    };
}
