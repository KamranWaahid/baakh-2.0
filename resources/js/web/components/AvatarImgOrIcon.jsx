import React, { useState } from 'react';
import { User } from 'lucide-react';
import { cn } from '@/lib/utils';
import { getImageUrl } from '../utils/url';

/**
 * Small round avatar: loads image resolved via getImageUrl; User icon when missing or 404.
 */
export default function AvatarImgOrIcon({
    src,
    imageType = 'poet',
    alt = '',
    className,
    iconClassName,
    imgClassName = 'w-full h-full object-cover',
}) {
    const [broken, setBroken] = useState(() => !src);

    const url = src ? getImageUrl(src, imageType) : '';

    if (broken || !url) {
        return (
            <span
                className={cn('flex h-full w-full items-center justify-center text-muted-foreground bg-muted', className)}
                aria-hidden
            >
                <User
                    className={cn(
                        'h-[55%] w-[55%] min-h-[14px] min-w-[14px]',
                        iconClassName
                    )}
                    strokeWidth={1.75}
                />
            </span>
        );
    }

    return (
        <img
            src={url}
            alt={alt}
            className={cn(imgClassName, className)}
            onError={() => setBroken(true)}
            loading="lazy"
            decoding="async"
        />
    );
}
