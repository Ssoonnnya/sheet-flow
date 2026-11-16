import {
    ApiError,
    type Paginated,
    type ValidationErrors,
} from '@/spa/api/types';

type JsonBody = Record<string, unknown> | undefined;

function csrfToken(): string | null {
    const meta = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (meta) {
        return meta;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match?.[1] ? decodeURIComponent(match[1]) : null;
}

async function parseBody(response: Response): Promise<unknown> {
    if (response.status === 204) {
        return null;
    }

    const text = await response.text();

    if (text === '') {
        return null;
    }

    try {
        return JSON.parse(text) as unknown;
    } catch {
        return text;
    }
}

function unwrapData<T>(payload: unknown): T {
    if (
        payload !== null &&
        typeof payload === 'object' &&
        'data' in payload &&
        (payload as { data: unknown }).data !== undefined
    ) {
        return (payload as { data: T }).data;
    }

    return payload as T;
}

async function apiRequestRaw(
    method: string,
    path: string,
    body?: JsonBody,
): Promise<unknown> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const token = csrfToken();

    if (token) {
        headers['X-CSRF-TOKEN'] = token;
        headers['X-XSRF-TOKEN'] = token;
    }

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(`/api${path}`, {
        method,
        credentials: 'same-origin',
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const payload = await parseBody(response);

    if (response.status === 401) {
        if (!window.location.pathname.startsWith('/app/login')) {
            window.location.assign('/app/login');
        }

        throw new ApiError('Unauthenticated.', 401);
    }

    if (response.status === 422) {
        const errors =
            payload !== null &&
            typeof payload === 'object' &&
            'errors' in payload
                ? ((payload as { errors: ValidationErrors }).errors ?? {})
                : {};

        const message =
            payload !== null &&
            typeof payload === 'object' &&
            'message' in payload &&
            typeof (payload as { message: unknown }).message === 'string'
                ? (payload as { message: string }).message
                : 'Validation failed.';

        throw new ApiError(message, 422, errors);
    }

    if (!response.ok) {
        const message =
            payload !== null &&
            typeof payload === 'object' &&
            'message' in payload &&
            typeof (payload as { message: unknown }).message === 'string'
                ? (payload as { message: string }).message
                : `Request failed with status ${response.status}.`;

        throw new ApiError(message, response.status);
    }

    return payload;
}

export async function apiRequest<T>(
    method: string,
    path: string,
    body?: JsonBody,
): Promise<T> {
    return unwrapData<T>(await apiRequestRaw(method, path, body));
}

export const api = {
    get: <T>(path: string) => apiRequest<T>('GET', path),
    getPage: async <T>(path: string): Promise<Paginated<T>> => {
        const payload = await apiRequestRaw('GET', path);

        if (
            payload === null ||
            typeof payload !== 'object' ||
            !('data' in payload) ||
            !Array.isArray((payload as { data: unknown }).data)
        ) {
            throw new ApiError('Unexpected paginated response.', 500);
        }

        return payload as Paginated<T>;
    },
    post: <T>(path: string, body?: JsonBody) =>
        apiRequest<T>('POST', path, body),
    put: <T>(path: string, body?: JsonBody) => apiRequest<T>('PUT', path, body),
    patch: <T>(path: string, body?: JsonBody) =>
        apiRequest<T>('PATCH', path, body),
    delete: <T>(path: string) => apiRequest<T>('DELETE', path),
};
