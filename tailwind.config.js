/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./HTML/**/*.{html,php}",
    "./PHP/**/*.{php,html}",
    "./JS/**/*.{js,ts}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
