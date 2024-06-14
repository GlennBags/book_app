module.exports = {
  content: [
    './src/**/*.{html,js,jsx,ts,tsx}', // Adjust the paths according to your project's structure
    './public/index.html',
    './resources/views/**/*.blade.php', // Add your Blade templates
  ],
  theme: {
    extend: {
      colors: {
        laravel: "#ef3b2d",
      },
    },
  },
  plugins: [],
};
