/** @type {import('tailwindcss').Config} */
module.exports = {
  mode: 'jit',
  content: [
    './views/**/*.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  prefix: 'fw-', // This is the prefix for the generated classes
}

