import { useState, useEffect } from 'react';
import { useScrollDirection } from './useScrollDirection';

/**
 * Sticky top offset that collapses to 0 when the mobile navbar is hidden.
 * Uses --baakh-header-h from Navbar (notice + nav), with 56px fallback.
 */
export function useStickyBelowNavbar() {
    const scrollDirection = useScrollDirection();
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => setIsMobile(window.innerWidth < 1024);
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    const isNavbarHidden = isMobile && scrollDirection === 'down';

    return {
        isNavbarHidden,
        stickyTopClass: isNavbarHidden ? 'top-0' : 'top-[var(--baakh-header-h,56px)]',
        // Match navbar hide/show timing so the bar eases into place
        stickyMotionClass: 'transition-[top,box-shadow] duration-300 ease-out will-change-[top]',
    };
}
