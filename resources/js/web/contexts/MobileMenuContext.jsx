import React, { createContext, useContext, useState, useCallback, useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

const MobileMenuContext = createContext();

export const MobileMenuProvider = ({ children }) => {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const location = useLocation();
    const scrollYRef = useRef(0);

    const openMenu = useCallback(() => setIsMenuOpen(true), []);
    const closeMenu = useCallback(() => setIsMenuOpen(false), []);
    const toggleMenu = useCallback(() => setIsMenuOpen(prev => !prev), []);

    // Auto-close on navigation
    useEffect(() => {
        closeMenu();
    }, [location.pathname, closeMenu]);

    // Lock body scroll without layout jump (iOS-safe)
    useEffect(() => {
        const { body, documentElement } = document;

        if (isMenuOpen) {
            scrollYRef.current = window.scrollY || window.pageYOffset || 0;
            body.style.position = 'fixed';
            body.style.top = `-${scrollYRef.current}px`;
            body.style.left = '0';
            body.style.right = '0';
            body.style.width = '100%';
            body.style.overflow = 'hidden';
            documentElement.style.overflow = 'hidden';
        } else {
            const y = scrollYRef.current;
            body.style.position = '';
            body.style.top = '';
            body.style.left = '';
            body.style.right = '';
            body.style.width = '';
            body.style.overflow = '';
            documentElement.style.overflow = '';
            if (y) {
                window.scrollTo(0, y);
            }
        }

        return () => {
            body.style.position = '';
            body.style.top = '';
            body.style.left = '';
            body.style.right = '';
            body.style.width = '';
            body.style.overflow = '';
            documentElement.style.overflow = '';
        };
    }, [isMenuOpen]);

    return (
        <MobileMenuContext.Provider value={{ isMenuOpen, openMenu, closeMenu, toggleMenu }}>
            {children}
        </MobileMenuContext.Provider>
    );
};

export const useMobileMenu = () => {
    const context = useContext(MobileMenuContext);
    if (!context) {
        throw new Error('useMobileMenu must be used within a MobileMenuProvider');
    }
    return context;
};
