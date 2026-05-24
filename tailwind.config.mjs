/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        brand: {
          cyan:   '#00b4d8',
          'cyan-dk': '#0077a8',
          purple: '#7c3aed',
          navy:   '#0d2137',
          'navy-mid': '#112a42',
          dark:   '#0a1628',
          accent: '#00e5ff',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
