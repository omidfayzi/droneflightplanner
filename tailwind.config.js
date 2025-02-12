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

