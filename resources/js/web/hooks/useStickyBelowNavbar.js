import { useState, useEffect } from 'react';
import { useScrollDirection } from './useScrollDirection';

/**
 * Sticky top offset that collapses to 0 when the mobile navbar is hidden.
 * Matches navbar height: 56px mobile / 65px desktop.
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
        stickyTopClass: isNavbarHidden ? 'top-0' : 'top-[56px] lg:top-[65px]',
        // Match navbar hide/show timing so the bar eases into place
        stickyMotionClass: 'transition-[top,box-shadow] duration-300 ease-out will-change-[top]',
    };
}
