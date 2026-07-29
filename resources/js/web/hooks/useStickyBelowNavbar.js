/**
 * Sticky top offset below the always-visible fixed navbar (notice + nav).
 * Uses --baakh-header-h from Navbar, with 97px fallback (notice + nav).
 */
export function useStickyBelowNavbar() {
    return {
        isNavbarHidden: false,
        stickyTopClass: 'top-[var(--baakh-header-h,97px)]',
        stickyMotionClass: '',
    };
}
