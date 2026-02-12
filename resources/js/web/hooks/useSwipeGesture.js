import { useEffect, useRef } from 'react';

/**
 * Detects edge swipe gestures to open/close the mobile menu.
 * RTL-aware: in LTR swipe-right opens, in RTL swipe-left opens.
 *
 * @param {Object} options
 * @param {boolean} options.isMenuOpen - current menu state
 * @param {Function} options.openMenu - callback to open
 * @param {Function} options.closeMenu - callback to close
 * @param {boolean} options.isRtl - whether the current language is RTL
 * @param {boolean} options.enabled - whether gestures are enabled (mobile only)
 */
export function useSwipeGesture({ isMenuOpen, openMenu, closeMenu, isRtl, enabled = true }) {
    const touchRef = useRef({ startX: 0, startY: 0, startTime: 0, tracking: false });

    useEffect(() => {
        if (!enabled) return;

        const EDGE_ZONE = 30; // px from screen edge to detect swipe start
        const MIN_DISTANCE = 50; // minimum swipe distance
        const MAX_VERTICAL = 80; // ignore if vertical movement exceeds this
        const MENU_WIDTH = 280;

        const handleTouchStart = (e) => {
            const touch = e.touches[0];
            const { clientX, clientY } = touch;
            const screenWidth = window.innerWidth;

            // Determine if touch started in the edge zone
            let inEdgeZone = false;

            if (!isMenuOpen) {
                // To OPEN: detect edge swipe
                if (isRtl) {
                    // RTL: swipe from right edge
                    inEdgeZone = clientX >= screenWidth - EDGE_ZONE;
                } else {
                    // LTR: swipe from left edge
                    inEdgeZone = clientX <= EDGE_ZONE;
                }
            } else {
                // To CLOSE: allow swipe anywhere on the visible content area
                if (isRtl) {
                    // RTL: content is shifted left, swipe left-to-right to close
                    inEdgeZone = clientX <= screenWidth - MENU_WIDTH + 40;
                } else {
                    // LTR: content is shifted right, swipe right-to-left to close
                    inEdgeZone = clientX >= MENU_WIDTH - 40;
                }
            }

            if (inEdgeZone) {
                touchRef.current = {
                    startX: clientX,
                    startY: clientY,
                    startTime: Date.now(),
                    tracking: true,
                };
            }
        };

        const handleTouchEnd = (e) => {
            if (!touchRef.current.tracking) return;

            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - touchRef.current.startX;
            const deltaY = Math.abs(touch.clientY - touchRef.current.startY);
            const elapsed = Date.now() - touchRef.current.startTime;

            touchRef.current.tracking = false;

            // Ignore if too much vertical movement (user is scrolling)
            if (deltaY > MAX_VERTICAL) return;

            const distance = Math.abs(deltaX);
            const velocity = distance / elapsed; // px/ms

            // Need minimum distance OR fast velocity
            const isValidSwipe = distance >= MIN_DISTANCE || (velocity > 0.3 && distance > 20);
            if (!isValidSwipe) return;

            if (!isMenuOpen) {
                // Opening swipe
                if (isRtl && deltaX < 0) openMenu();     // RTL: swipe left to open
                if (!isRtl && deltaX > 0) openMenu();     // LTR: swipe right to open
            } else {
                // Closing swipe
                if (isRtl && deltaX > 0) closeMenu();     // RTL: swipe right to close
                if (!isRtl && deltaX < 0) closeMenu();    // LTR: swipe left to close
            }
        };

        document.addEventListener('touchstart', handleTouchStart, { passive: true });
        document.addEventListener('touchend', handleTouchEnd, { passive: true });

        return () => {
            document.removeEventListener('touchstart', handleTouchStart);
            document.removeEventListener('touchend', handleTouchEnd);
        };
    }, [isMenuOpen, openMenu, closeMenu, isRtl, enabled]);
}
