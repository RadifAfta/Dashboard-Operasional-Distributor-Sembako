/**
 * Dynamic Brand Theming System
 * Supports both predefined corporate palettes AND arbitrary custom HEX colors (#HEX).
 */

export const BRAND_PALETTES = {
    indigo: {
        id: 'indigo',
        name: 'Modern Indigo (Bawaan)',
        description: 'SaaS, platform digital & startup',
        hex: '#4F46E5',
    },
    blue: {
        id: 'blue',
        name: 'Corporate Blue',
        description: 'Perbankan, B2B & Keuangan (BCA, Mandiri)',
        hex: '#2563EB',
    },
    emerald: {
        id: 'emerald',
        name: 'Emerald Forest',
        description: 'Retail, Logistik & E-commerce (Tokopedia)',
        hex: '#059669',
    },
    rose: {
        id: 'rose',
        name: 'Crimson Red',
        description: 'BUMN, Telco & Media (Telkom)',
        hex: '#E11D48',
    },
    amber: {
        id: 'amber',
        name: 'Amber Industrial',
        description: 'Manufaktur, Pabrik & Energi',
        hex: '#D97706',
    },
    violet: {
        id: 'violet',
        name: 'Tech Violet',
        description: 'AI, Desain & Agensi Kreatif',
        hex: '#7C3AED',
    },
    teal: {
        id: 'teal',
        name: 'Teal Oceanic',
        description: 'Healthcare, Medis & Maritim',
        hex: '#0D9488',
    },
    cyan: {
        id: 'cyan',
        name: 'Sky Cyan',
        description: 'Cloud, API & Developer Tools',
        hex: '#0284C7',
    },
    orange: {
        id: 'orange',
        name: 'Vibrant Orange',
        description: 'F&B, Hospitality & Pengiriman',
        hex: '#EA580C',
    },
    slate: {
        id: 'slate',
        name: 'Obsidian Minimalist',
        description: 'Monokrom elegan & Studio',
        hex: '#0F172A',
    },
};

/**
 * Parses any 3 or 6-digit hex code into RGB
 */
export function hexToRgb(hex) {
    if (!hex) return { r: 79, g: 70, b: 229 };
    let cleanHex = hex.replace('#', '').trim();
    if (cleanHex.length === 3) {
        cleanHex = cleanHex.split('').map((c) => c + c).join('');
    }
    if (cleanHex.length !== 6) {
        return { r: 79, g: 70, b: 229 };
    }
    const num = parseInt(cleanHex, 16);
    if (isNaN(num)) return { r: 79, g: 70, b: 229 };

    return {
        r: (num >> 16) & 255,
        g: (num >> 8) & 255,
        b: num & 255,
    };
}

/**
 * Darkens or lightens an RGB color by a percentage
 */
function adjustBrightness(rgb, percent) {
    return {
        r: Math.max(0, Math.min(255, Math.round(rgb.r * (1 + percent / 100)))),
        g: Math.max(0, Math.min(255, Math.round(rgb.g * (1 + percent / 100)))),
        b: Math.max(0, Math.min(255, Math.round(rgb.b * (1 + percent / 100)))),
    };
}

function rgbToHex({ r, g, b }) {
    return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase()}`;
}

/**
 * Injects CSS custom properties into :root to immediately change the brand color across the ENTIRE application.
 * Accepts either a preset key (e.g. 'blue') OR any custom hex code (e.g. '#FF6600').
 */
export function applyBrandTheme(colorOrHex = 'indigo') {
    let hexValue = '#4F46E5';

    if (BRAND_PALETTES[colorOrHex]) {
        hexValue = BRAND_PALETTES[colorOrHex].hex;
    } else if (colorOrHex && colorOrHex.startsWith('#')) {
        hexValue = colorOrHex;
    } else if (colorOrHex && /^[0-9A-Fa-f]{6}$/.test(colorOrHex)) {
        hexValue = `#${colorOrHex}`;
    }

    const rgb = hexToRgb(hexValue);
    const hoverRgb = adjustBrightness(rgb, -15);
    const hoverHex = rgbToHex(hoverRgb);

    const root = document.documentElement;
    root.style.setProperty('--brand-primary', `${rgb.r} ${rgb.g} ${rgb.b}`);
    root.style.setProperty('--brand-primary-hover', `${hoverRgb.r} ${hoverRgb.g} ${hoverRgb.b}`);
    root.style.setProperty('--brand-hex', hexValue);
    root.style.setProperty('--brand-hex-hover', hoverHex);
    root.style.setProperty('--brand-light', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1)`);
    root.style.setProperty('--brand-border', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.25)`);
}
