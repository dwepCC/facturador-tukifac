/**
 * Caché en memoria + TTL para snapshots de API del tenant (Vue 2).
 * Uso: import { getCachedJson, setCachedJson, invalidateByPrefix } from '@/helpers/tenantResourceCache'
 * No sustituye Vuex; complementa layouts que no tienen store global.
 */

const memory = new Map();

function key(prefix, logicalKey) {
    return `${prefix}:${logicalKey}`;
}

/**
 * @param {string} prefix  ej. 'tukifac.api' + tenantFqdn o userId
 * @param {string} logicalKey  ej. 'company', 'configurations.record'
 * @param {number} ttlMs
 * @returns {object|null}
 */
export function getCachedJson(prefix, logicalKey, ttlMs) {
    const k = key(prefix, logicalKey);
    const row = memory.get(k);
    if (!row) {
        return null;
    }
    if (Date.now() - row.t > ttlMs) {
        memory.delete(k);
        return null;
    }
    return row.v;
}

export function setCachedJson(prefix, logicalKey, value) {
    memory.set(key(prefix, logicalKey), { t: Date.now(), v: value });
}

export function invalidateByPrefix(prefix) {
    const p = `${prefix}:`;
    for (const k of memory.keys()) {
        if (k.startsWith(p)) {
            memory.delete(k);
        }
    }
}
