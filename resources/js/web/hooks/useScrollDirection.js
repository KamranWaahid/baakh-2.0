import { useSyncExternalStore } from 'react';

let direction = 'up';
let prevY = 0;
let ticking = false;
const listeners = new Set();

function notify() {
    listeners.forEach((listener) => listener());
}

function onScroll() {
    if (ticking) return;
    ticking = true;

    requestAnimationFrame(() => {
        const y = window.scrollY || window.pageYOffset || 0;

        if (Math.abs(y - prevY) >= 10) {
            const next = y > prevY ? 'down' : 'up';
            if (next !== direction && (y > 10 || next === 'up')) {
                direction = next;
                notify();
            }
            prevY = y > 0 ? y : 0;
        }

        ticking = false;
    });
}

function subscribe(listener) {
    if (listeners.size === 0) {
        window.addEventListener('scroll', onScroll, { passive: true });
    }
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
        if (listeners.size === 0) {
            window.removeEventListener('scroll', onScroll);
        }
    };
}

function getSnapshot() {
    return direction;
}

function getServerSnapshot() {
    return 'up';
}

/**
 * Shared, passive scroll-direction signal for mobile chrome.
 * One window listener for all consumers — avoids rebind churn and jank.
 */
export function useScrollDirection() {
    return useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);
}
