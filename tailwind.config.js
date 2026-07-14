/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                'dilg-yellow': '#F4C542',
                'dilg-gold': '#D4A017',
                'dilg-dark': '#333333',
                'dilg-light': '#F8F9FA',
            },
        },
    },
    plugins: [
        require('daisyui'),
    ],
    daisyui: {
        themes: [
            {
                dilg: {
                    "primary": "#F4C542",      // DILG Yellow
                    "primary-content": "#333333", // Dark text on yellow
                    "secondary": "#D4A017",     // DILG Gold
                    "secondary-content": "#FFFFFF", // White text on gold
                    "accent": "#F8F9FA",        // Light background
                    "accent-content": "#333333", // Dark text on light
                    "neutral": "#333333",       // Dark Gray
                    "neutral-content": "#FFFFFF", // White text on dark
                    "base-100": "#FFFFFF",      // White background
                    "base-200": "#F8F9FA",      // Light gray
                    "base-300": "#E5E7EB",      // Medium gray
                    "base-content": "#333333",  // Dark text
                    "info": "#3B82F6",          // Blue
                    "info-content": "#FFFFFF",
                    "success": "#10B981",       // Green
                    "success-content": "#FFFFFF",
                    "warning": "#F59E0B",       // Orange
                    "warning-content": "#FFFFFF",
                    "error": "#EF4444",         // Red
                    "error-content": "#FFFFFF",
                },
            },
        ],
        base: true,
        styled: true,
        utils: true,
        logs: false,
    },
};
