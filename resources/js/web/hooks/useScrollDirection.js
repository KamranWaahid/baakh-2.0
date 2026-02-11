import { useState, useEffect } from 'react';

export function useScrollDirection() {
    const [scrollDirection, setScrollDirection] = useState('up');
    const [prevOffset, setPrevOffset] = useState(0);

    useEffect(() => {
        const toggleScrollDirection = () => {
            const scrollY = window.pageYOffset;

            if (Math.abs(scrollY - prevOffset) < 10) {
                return;
            }

            const direction = scrollY > prevOffset ? 'down' : 'up';
            if (
                direction !== scrollDirection &&
                (scrollY > 10 || scrollY < prevOffset)
            ) {
                setScrollDirection(direction);
            }
            setPrevOffset(scrollY > 0 ? scrollY : 0);
        };

        window.addEventListener('scroll', toggleScrollDirection);
        return () => {
            window.removeEventListener('scroll', toggleScrollDirection);
        };
    }, [scrollDirection, prevOffset]);

    return scrollDirection;
}
