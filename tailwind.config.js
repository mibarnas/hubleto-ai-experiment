/** @type {import('tailwindcss').Config} */

// NOTE: the CSS build does not read this file. src/Main.twcss imports the
// upstream entry point, which pulls in vendor/hubleto/assets/tailwind.config.js
// via its own `@config` directive; the sources missing from that config are
// added as `@source` rules in src/Main.twcss. This file is kept accurate so it
// stays usable if the entry point is ever pointed at it.
module.exports = {
  darkMode: 'selector',
  content: [
    "./vendor/hubleto/erp/**/*.{html,js,twig,tsx,php}",
    "./vendor/hubleto/framework/**/*.{tsx,twig,php}",
    "./src/apps/**/*.{tsx,twig,php}",
    // react-ui is not vendored -- it is a file: dependency outside the project
    "../hubleto/react-ui/{core,ext,fc,css}/**/*.{js,ts,jsx,tsx,css}",
    "../hubleto/react-ui/node_modules/primereact/**/*.{js,ts,jsx,tsx}",
  ],
  safelist: [
    'hubleto-lookup__indicator',
    'hubleto-lookup__control',
    'hubleto-lookup__input-container',
    'hubleto-lookup__value-container',
    'hubleto-lookup__input',
  ],
  plugins: [],
}
