<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Pacific Southbay College Library System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4">

  <!-- Login Card -->
  <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-md p-8 fade-in">

    <!-- Logo -->
    <div class="flex flex-col items-center mb-6">
      <img src="logo.png" alt="Pacific Southbay College Logo" class="w-20 h-20 object-contain mb-3" />
      <h1 class="text-2xl font-bold text-red-700 text-center">
        Pacific Southbay College, INC. <br>Library System
      </h1>
      <p class="text-sm text-gray-500">“Soar High South Bayers”</p>
    </div>

    <!-- Login Form -->
    <form id="loginForm" class="space-y-5" method='POST' action="login">
      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
        <input type="email" id="email" name="email" required
           pattern="[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
          placeholder="Enter your email" />
      </div>

      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
        <input type="password" id="password" name="password" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
          placeholder="Enter your password" />
      </div>

      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center space-x-2 text-gray-600">
          <input type="checkbox" id="remember" class="accent-red-600" />
          <span>Remember Me</span>
        </label>
        <a href="forgot_password" class="text-red-600 hover:underline">Forgot Password?</a>
      </div>

      <button id="loginBtn" type="submit"
        class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
        Login
      </button>
    </form>

    <!-- Register Link -->
    <p class="text-center text-sm text-gray-600 mt-6">
      Don’t have an account?
      <a href="register" class="text-red-600 hover:underline font-medium">Register here</a>
    </p>
  </div>

  <!-- jQuery Login Handler -->
  <script>
    $(document).ready(function() {

      function showMessage(text, type) {
        const colors = { 
          success: 'bg-green-500', 
          error: 'bg-red-500', 
          warning: 'bg-yellow-500' 
        };
        const box = $(`
          <div class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50">
            <div class="px-6 py-4 text-white rounded-xl shadow-2xl text-center text-lg ${colors[type]} animate-fade">
              ${text}
            </div>
          </div>
        `);
        $('body').append(box);
        setTimeout(() => {
          box.fadeOut(300, () => box.remove());
        }, 1800);
      }

      function toggleButton(btn, state) {
        $(btn).prop('disabled', !state);
        if(state) $(btn).removeClass('opacity-50'); else $(btn).addClass('opacity-50');
      }

      // AJAX Login
      $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        const email = $('#email').val().trim();
        const password = $('#password').val().trim();
        const remember = $('#remember').is(':checked') ? 1 : 0;

        if (!email || !password) {
          return showMessage('Please enter your email and password.', 'error');
        }

        toggleButton('#loginBtn', false);
        showMessage('Logging in...', 'warning');

        $.post('login', { email, password, remember }, function(response) {
          showMessage(response.message, response.type);
          toggleButton('#loginBtn', true);

          if(response.type === 'success') {
            if(response.role === 'admin') {
              setTimeout(() => window.location.href = 'admin', 1200);
            } else {
            setTimeout(() => window.location.href = 'user', 1200);
            }
          }
        }, 'json').fail(function() {
          showMessage('Server error. Try again.', 'error');
          toggleButton('#loginBtn', true);
        });
      });

    });
  </script>
</body>
</html>
