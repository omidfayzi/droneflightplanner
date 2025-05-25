/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["*/*.{html,js,php}"],
   theme: {
     extend: {
      backgroundImage: {
        'custom-gradient': 'linear-gradient(to right, transparent 70%, rgba(189, 189, 189))',
      },
     },
},
   plugins: [],
}

module.exports = {
  content: [
    "./app/views/**/*.php",
    "./components/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#313234',
        secondary: '#2563EB',
        background: '#F3F4F6',
        surface: '#FFFFFF',
        altSurface: '#F9FAFB'
      }
    },
  },
  plugins: [],
}