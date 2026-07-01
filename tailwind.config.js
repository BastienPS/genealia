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
      },
      backgroundImage: {
        'brand-gradient': 'linear-gradient(90deg, #fc4c02 0%, #ef2cc1 50%, #bdbbff 100%)',
      },
      boxShadow: {
        'brand-glow': '0 8px 30px rgba(239, 44, 193, 0.35)',
      },
    },
  },
  plugins: [],
}
