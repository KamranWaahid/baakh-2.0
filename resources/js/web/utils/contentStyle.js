/**
 * Map poetry content_style (admin: center|start|end|justified, legacy: left|right)
 * to a physical CSS text align for the public poem body.
 *
 * Admin RTL editor uses start→right / end→left; LTR uses start→left / end→right.
 */
export function resolveVerseAlign(contentStyle, { isRtl = true, isGhazal = false } = {}) {
    const style = String(contentStyle || '').toLowerCase().trim();

    if (style === 'center') return 'center';
    if (style === 'left') return 'left';
    if (style === 'right') return 'right';
    if (style === 'start') return isRtl ? 'right' : 'left';
    if (style === 'end') return isRtl ? 'left' : 'right';
    if (style === 'justified' || style === 'justify') return 'justify';

    // No explicit style: ghazals default centered; else script-aware start edge.
    if (isGhazal) return 'center';
    return isRtl ? 'right' : 'left';
}

export function verseAlignClass(align) {
    if (align === 'center') return 'text-center';
    if (align === 'left') return 'text-left';
    if (align === 'justify') return 'text-justify';
    return 'text-right';
}
