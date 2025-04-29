module.exports = {
  plugins: {
    // any PostCSS plugins you need
    'postcss-preset-env': {},

    // ← this one MUST be here
    '@tailwindcss/postcss': {},

    // keep autoprefixer if you like
    'autoprefixer': {},
  },
}