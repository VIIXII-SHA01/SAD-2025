<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pacific Southbay College Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex flex-col items-center justify-center text-center p-6">

    <!-- Main Container -->
    <div class="max-w-4xl w-full fade-in">

      <!-- Logo Section -->
      <div class="flex flex-col items-center mb-8">
        <img src="logo.png" alt="Pacific Southbay College Logo" class="w-32 h-32 md:w-48 md:h-48 object-contain mb-4" />
        <h1 class="text-3xl md:text-5xl font-extrabold text-red-700 tracking-wide">
          Pacific Southbay College, INC. <br>Library System
        </h1>
      </div>

      <!-- Description -->
      <p class="text-lg md:text-xl text-gray-700 font-medium mb-10">
        “Soar High South Bayers”
      </p>

      <!-- Buttons / Navigation -->
      <div class="flex flex-wrap justify-center gap-4">
        <a href="login"> <button class="bg-red-600 text-white px-6 py-3 rounded-xl shadow-lg hover:bg-red-700 transition">
          Login
        </button></a>
       <a href="register">
         <button class="bg-gray-200 text-red-700 px-6 py-3 rounded-xl shadow-lg hover:bg-gray-300 transition">
          Register
        </button>
       </a>
        <button class="bg-white border border-red-600 text-red-700 px-6 py-3 rounded-xl shadow-lg hover:bg-red-50 transition">
          About
        </button>
      </div>

    </div>

    <!-- Footer -->
    <footer class="mt-16 text-gray-600 text-sm">
      &copy; 2025 Pacific Southbay College, Inc. | Library System
    </footer>

  </body>
</html>
