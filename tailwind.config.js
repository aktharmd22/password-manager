import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                vault: {
                    bg: {
                        DEFAULT: '#0A0A0B',
                        light: '#FAFAFA',
                    },
                    surface: {
                        DEFAULT: '#18181B',
                        light: '#FFFFFF',
                        elevated: '#1F1F23',
                    },
                    border: {
                        DEFAULT: '#27272A',
                        light: '#E4E4E7',
                        strong: '#3F3F46',
                    },
                    text: {
                        DEFAULT: '#FAFAFA',
                        light: '#18181B',
                        muted: '#A1A1AA',
                        subtle: '#71717A',
                    },
                    accent: {
                        DEFAULT: '#6366F1',
                        hover: '#4F46E5',
                        soft: 'rgba(99, 102, 241, 0.12)',
                    },
                    success: '#10B981',
                    warning: '#F59E0B',
                    danger: '#EF4444',
                },
            },
            boxShadow: {
                'vault-sm': '0 1px 2px 0 rgba(0, 0, 0, 0.18)',
                'vault': '0 4px 12px -2px rgba(0, 0, 0, 0.25)',
                'vault-lg': '0 12px 32px -4px rgba(0, 0, 0, 0.35)',
                'vault-glow': '0 0 0 1px rgba(99, 102, 241, 0.4), 0 0 24px -4px rgba(99, 102, 241, 0.3)',
            },
            borderRadius: {
                'xl': '0.875rem',
            },
            keyframes: {
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'slide-in-right': {
                    '0%': { opacity: '0', transform: 'translateX(16px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                'shimmer': {
                    '0%': { backgroundPosition: '-1000px 0' },
                    '100%': { backgroundPosition: '1000px 0' },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.2s ease-out',
                'fade-in-up': 'fade-in-up 0.25s ease-out',
                'slide-in-right': 'slide-in-right 0.25s ease-out',
                'shimmer': 'shimmer 2s linear infinite',
            },
            backdropBlur: {
                xs: '2px',
            },
        },
    },

    plugins: [forms],
};
