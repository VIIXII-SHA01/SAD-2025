<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register | Pacific Southbay College Library System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4">

  <!-- Exception Message -->
  <?php if (!empty($_SESSION['exception'])): ?>
    <div class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 px-6 py-4 bg-red-500 text-white rounded-xl shadow-2xl text-center text-lg">
      <?= htmlspecialchars($_SESSION['exception']) ?>
    </div>
    <?php unset($_SESSION['exception']); endif; ?>

  <!-- Registration Card -->
  <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-lg p-8 fade-in">

    <!-- Logo Section -->
    <div class="flex flex-col items-center mb-6">
      <img src="logo.png" alt="Pacific Southbay College Logo" class="w-20 h-20 object-contain mb-3" />
      <h1 class="text-2xl font-bold text-red-700 text-center">
        Pacific Southbay College, INC.<br> Library System
      </h1>
      <p class="text-sm text-gray-500">“Soar High South Bayers”</p>
    </div>

    <!-- Registration Form -->
    <form id="registerForm" class="space-y-5">
      <div>
        <label for="fullname" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
      <input type="text" id="fullname" name="fullname" required
        pattern="[A-Za-z\s]+"
        title="Letters only"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
        placeholder="Enter your full name" />
      </div>

      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
       <input type="email" id="email" name="email" required
        pattern="[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}"
        title="Please enter a valid email using only letters, numbers, and basic email characters."
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
        placeholder="Enter your email" />
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
          <input type="password" id="password" name="password" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
            placeholder="Enter password" />
        </div>

        <div>
          <label for="confirm" class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
          <input type="password" id="confirm" name="confirm" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
            placeholder="Re-enter password" />
        </div>
      </div>

      <button type="submit" id="registerBtn"
        class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
        Register
      </button>
    </form>

    <!-- Login Redirect -->
    <p class="text-center text-sm text-gray-600 mt-6">
      Already have an account?
      <a href="login" class="text-red-600 hover:underline font-medium">Login here</a>
    </p>
  </div>

  <!-- jQuery AJAX Registration -->
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
        setTimeout(() => { box.fadeOut(300, () => box.remove()); }, 1800);
      }

      function toggleButton(btn, state) {
        $(btn).prop('disabled', !state);
        if(state) $(btn).removeClass('opacity-50'); else $(btn).addClass('opacity-50');
      }

      // AJAX Register
      $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        const fullname = $('#fullname').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirm = $('#confirm').val();

        if (!fullname || !email || !password || !confirm) {
          return showMessage('Please fill in all fields.', 'error');
        }

        if (password !== confirm) {
          return showMessage('Passwords do not match.', 'error');
        }

        toggleButton('#registerBtn', false);
        showMessage('Registering...', 'warning');

        $.post('/SAD-2025/register', { fullname, email, password, confirm }, function(response) {
          showMessage(response.message, response.type);
          toggleButton('#registerBtn', true);

          if(response.type === 'success') {
            setTimeout(() => window.location.href = 'verification', 1200);
          }
        }, 'json')
        .fail(function () {
          showMessage('Server error. Try again.', 'error');
          toggleButton('#registerBtn', true);
        });

      });

    });
  </script>

</body>
</html>
