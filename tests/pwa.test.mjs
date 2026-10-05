import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../public/sw.js', import.meta.url), 'utf8');

function worker(fetch) {
    const listeners = new Map();
    const cached = [];
    const deleted = [];
    const offline = new Response('Offline fallback');
    const cache = {
        addAll: async (urls) => cached.push(...urls),
        match: async (request) => request === '/offline.html' ? offline.clone() : undefined,
    };
    vm.runInNewContext(source, {
        self: {
            location: { origin: 'https://noq.example' },
            addEventListener: (type, listener) => listeners.set(type, listener),
            skipWaiting: async () => {},
            clients: { claim: async () => {} },
        },
        caches: {
            open: async () => cache,
            keys: async () => ['noq-pwa-v0', 'noq-pwa-v1', 'another-app'],
            delete: async (name) => deleted.push(name),
        },
        fetch,
        URL,
        Response,
    });
    return { listeners, cached, deleted };
}

function responseFor(instance, path, options = {}) {
    let response;
    instance.listeners.get('fetch')({
        request: { url: new URL(path, 'https://noq.example').href, method: 'GET', mode: 'navigate', ...options },
        respondWith: (promise) => { response = promise; },
    });
    return response;
}

test('offline navigation shows fallback without retaining private pages', async () => {
    const instance = worker(async () => { throw new TypeError('Offline'); });
    const response = await responseFor(instance, '/dashboard?queue=private');
    assert.equal(await response.text(), 'Offline fallback');
    assert.deepEqual(instance.cached, []);
});

test('online pages and server errors pass through unchanged', async () => {
    for (const status of [200, 401, 500]) {
        const instance = worker(async () => new Response('Live server response', { status }));
        const response = await responseFor(instance, '/dashboard');
        assert.equal(response.status, status);
        assert.equal(await response.text(), 'Live server response');
        assert.deepEqual(instance.cached, []);
    }
});

test('API, Livewire, writes and external requests are never intercepted', () => {
    const instance = worker(() => { throw new Error('Must not fetch'); });
    assert.equal(responseFor(instance, '/api/queues', { mode: 'cors' }), undefined);
    assert.equal(responseFor(instance, '/livewire/update', { method: 'POST', mode: 'cors' }), undefined);
    assert.equal(responseFor(instance, '/login', { method: 'POST' }), undefined);
    assert.equal(responseFor(instance, 'https://external.example/'), undefined);
});

test('installation caches only existing public files and migration preserves other apps', async () => {
    const instance = worker(() => {});
    let pending;
    instance.listeners.get('install')({ waitUntil: (promise) => { pending = promise; } });
    await pending;
    assert.ok(instance.cached.includes('/offline.html'));
    for (const path of instance.cached) {
        assert.match(path, /^\/(offline\.html|assets\/branding\/icon[\w-]*\.png)$/);
        assert.ok((await readFile(new URL(`../public${path}`, import.meta.url))).length > 0);
    }
    instance.listeners.get('activate')({ waitUntil: (promise) => { pending = promise; } });
    await pending;
    assert.deepEqual(instance.deleted, ['noq-pwa-v0']);
});
