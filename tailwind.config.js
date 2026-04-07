/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {colors: {
        'orange-impordiesel': '#FF6B00',
        'gray-industrial': '#4C474F',
        'gray-soft': '#F8F9FA',
      },
      boxShadow: {
        'soft': '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)',
      }
    },
  },
  plugins: [],
}
