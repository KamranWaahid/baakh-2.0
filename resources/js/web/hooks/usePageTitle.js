import { useEffect } from 'react';

export function usePageTitle(title) {
    useEffect(() => {
        if (!title) {
            return undefined;
        }

        document.title = title;
        return undefined;
    }, [title]);
}
