<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Email Verification | Pacific Southbay College Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
  </head>

  <body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4">

    <!-- Verification Card -->
    <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-lg p-8 fade-in">

      <!-- Logo Section -->
      <div class="flex flex-col items-center mb-6">
        <img src="logo.png" alt="Pacific Southbay College Logo" class="w-20 h-20 object-contain mb-3" />
        <h1 class="text-2xl font-bold text-red-700 text-center">
          Pacific Southbay College, INC.<br> Library System
        </h1>
        <p class="text-sm text-gray-500">“Soar High South Bayers”</p>
      </div>

      <!-- Verification Form -->
      <form id="verifyForm" class="space-y-5" method="post" action="/SAD-2025/verify-email">
        <p class="text-gray-700 text-center mb-2">
          Enter the verification code sent to your email to activate your account.
        </p>

        <div>
          <label for="verification_code" class="block text-sm font-semibold text-gray-700 mb-1">Verification Code</label>
          <input type="text" id="verification_code" name="verification_code" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
            placeholder="Enter code here" />
        </div>

        <button type="submit"
          class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
          Verify Email
        </button>
      </form>

      <!-- Resend / Login Links -->
      <p class="text-center text-sm text-gray-600 mt-6">
        Didn't receive the code?
        <a href="/SAD-2025/resend-code" class="text-red-600 hover:underline font-medium">Resend Code</a>
      </p>

      <p class="text-center text-sm text-gray-600 mt-2">
        Already verified?
        <a href="/SAD-2025/login" class="text-red-600 hover:underline font-medium">Login here</a>
      </p>

    </div>

    <!-- Optional JS Validation -->
    <script>
      document.getElementById('verifyForm').addEventListener('submit', function(e) {
        const code = document.getElementById('verification_code').value.trim();

        if (!code) {
          e.preventDefault();
          alert('Please enter the verification code.');
        }
      });
    </script>

  </body>
</html>
