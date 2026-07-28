/**
 * Convert stored HTML/bio strings into readable plain text.
 * Decodes entities (&nbsp;, &amp;, …) and strips tags without showing markup.
 */
export function htmlToPlainText(input) {
    if (input == null) return '';
    const raw = String(input);
    if (!raw.trim()) return '';

    if (typeof document !== 'undefined') {
        const withBreaks = raw
            .replace(/<\s*br\s*\/?>/gi, '\n')
            .replace(/<\/\s*p\s*>/gi, '\n')
            .replace(/<\/\s*div\s*>/gi, '\n')
            .replace(/<\/\s*li\s*>/gi, '\n');

        const template = document.createElement('template');
        template.innerHTML = withBreaks;
        const text = template.content.textContent || template.content.innerText || '';
        return text
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    return raw
        .replace(/<\s*br\s*\/?>/gi, '\n')
        .replace(/<\/\s*p\s*>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'")
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}
