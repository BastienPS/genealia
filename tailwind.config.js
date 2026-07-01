/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        'brand-primary': '#000000',
        'brand-on-primary': '#ffffff',
        'brand-ink': '#000000',
        'brand-body': '#959494',
        'brand-hairline': '#ebebeb',
        'brand-canvas': '#ffffff',
        'brand-canvas-dark': '#010120',
        'brand-surface-dark-soft': '#313641',
        'brand-on-dark': '#ffffff',
        'brand-orange': '#fc4c02',
        'brand-magenta': '#ef2cc1',
        'brand-periwinkle': '#bdbbff',
        'brand-mint': '#c8f6f9',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      letterSpacing: {
        'tightest': '-0.03em',
        'tight': '-0.01em',
        'mono-wide': '0.04em',
      }
    },
  },
  plugins: [],
}
