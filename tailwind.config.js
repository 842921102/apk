/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
        "./src/**/*.css",
    ],
    theme: {
        extend: {
            colors: {
                app: {
                    page: '#F5F6F8',
                    card: '#FFFFFF',
                    fg: '#222222',
                    muted: '#8A8F99',
                    accent: '#7A57D1',
                    'accent-deep': '#6743BF',
                    'accent-soft': '#F3ECFF',
                    line: '#ECEEF2',
                    'line-soft': '#E8DDF5',
                },
                // 主黄色系 - 参考图片的背景色
                yellow: {
                    50: '#fefce8',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
                // 粉色系 - 参考图片的头部色
                pink: {
                    50: '#fdf2f8',
                    100: '#fce7f3',
                    200: '#fbcfe8',
                    300: '#f9a8d4',
                    400: '#f472b6',
                    500: '#ec4899',
                    600: '#db2777',
                    700: '#be185d',
                    800: '#9d174d',
                    900: '#831843',
                },
                // 黑色系 - 参考图片的标签色
                dark: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                // 保留中性色
                neutral: {
                    50: '#fafaf9',
                    100: '#f5f5f4',
                    200: '#e7e5e4',
                    300: '#d6d3d1',
                    400: '#a8a29e',
                    500: '#78716c',
                    600: '#57534e',
                    700: '#44403c',
                    800: '#292524',
                    900: '#1c1917',
                }
            },
            boxShadow: {
                'app-card': '0 4px 24px rgba(0, 0, 0, 0.06)',
                'app-card-hover': '0 8px 28px rgba(122, 87, 209, 0.1)',
                'app-btn': '0 8px 24px rgba(122, 87, 209, 0.32)',
            },
        },
    },
    safelist: [
        'app-toast',
        'app-toast--success',
        'app-toast--error',
        'app-toast--warning',
        'app-toast--info',
        'app-toast__row',
        'app-toast__icon',
        'app-toast__text',
        'is-visible',
    ],
    plugins: [],
}