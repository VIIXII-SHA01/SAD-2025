<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password | Pacific Southbay College Library System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
</head>

<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4 relative">

  <!-- Reset Password Card -->
  <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-md p-8 fade-in">

    <!-- Logo -->
    <div class="flex flex-col items-center mb-6">
      <img src="logo.png" alt="Pacific Southbay College Logo" class="w-20 h-20 object-contain mb-3" />
      <h1 class="text-2xl font-bold text-red-700 text-center">
        Pacific Southbay College, INC. <br>Library System
      </h1>
      <p class="text-sm text-gray-500">“Soar High South Bayers”</p>
    </div>

    <!-- Header -->
    <h2 class="text-xl font-semibold text-gray-700 text-center mb-2">Reset Password</h2>
    <p class="text-sm text-gray-600 text-center mb-6">
      Enter your new password below.
    </p>

    <!-- Reset Password Form -->
    <form id="resetForm" action="reset" method="POST" class="space-y-5">

      <!-- New Password -->
      <div>
        <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-1">
          New Password
        </label>
        <input type="password" id="new_password" name="new_password" required 
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
          placeholder="Enter new password" />
      </div>

      <!-- Confirm Password -->
      <div>
        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">
          Confirm Password
        </label>
        <input type="password" id="confirm_password" name="confirm_password" required 
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
          placeholder="Confirm new password" />
      </div>

      <button id="resetBtn" type="submit"
        class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
        Reset Password
      </button>

    </form>

    <!-- Back to Login -->
    <p class="text-center text-sm text-gray-600 mt-6">
      Remembered your password?
      <a href="login" class="text-red-600 hover:underline font-medium">Back to Login</a>
    </p>

  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Password Reset Handler -->
  <script>
    $(document).ready(function () {

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
        if (state) $(btn).removeClass('opacity-50');
        else $(btn).addClass('opacity-50');
      }

      // Submit Reset Password
      $('#resetForm').on('submit', function (e) {
        e.preventDefault();

        const newPass = $('#new_password').val().trim();
        const confirmPass = $('#confirm_password').val().trim();

        if (!newPass || !confirmPass)
          return showMessage('Please complete all fields.', 'error');

        if (newPass !== confirmPass)
          return showMessage('Passwords do not match.', 'error');

        toggleButton('#resetBtn', false);
        showMessage('Updating password...', 'warning');

        $.post('reset', 
        {
          new_password: newPass,
          confirm_password: confirmPass
        }, 
        function (response) {
          showMessage(response.message, response.type);
          toggleButton('#resetBtn', true);

          if (response.type === 'success') {
            setTimeout(() => window.location.href = 'login', 1500);
          }

        }, 'json')
        .fail(function () {
          showMessage('Server error. Try again.', 'error');
          toggleButton('#resetBtn', true);
        });
      });

    });
  </script>

</body>
</html>
