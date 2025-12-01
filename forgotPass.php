<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | Pacific Southbay College Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
  </head>

  <body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4 relative">

    <!-- Forgot Password Card -->
    <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-md p-8 fade-in">

      <!-- Logo -->
      <div class="flex flex-col items-center mb-6">
        <img src="logo.png" alt="Pacific Southbay College Logo" class="w-20 h-20 object-contain mb-3" />
        <h1 class="text-2xl font-bold text-red-700 text-center">
          Pacific Southbay College, INC. <br>Library System
        </h1>
        <p class="text-sm text-gray-500">“Soar High South Bayers”</p>
      </div>

      <!-- Forgot Password Header -->
      <h2 class="text-xl font-semibold text-gray-700 text-center mb-2">Forgot Password</h2>
      <p class="text-sm text-gray-600 text-center mb-6">
        Enter your email to receive a verification code.
      </p>

      <!-- Step 1: Send Code -->
      <form action="forgot_password" method="POST" class="space-y-5 mb-8">
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
        <div class="flex space-x-2">
          <input type="email" id="email" name="email" required
           pattern="[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}"
            class="flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none"
            placeholder="Enter your email" />

          <button type="submit" id="sendBtn"
            class="px-4 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition shadow-md whitespace-nowrap">
            Send
          </button>
        </div>
      </form>

      <form action="getCode" method="POST" class="space-y-5">
        <div>
          <label for="code" class="block text-sm font-semibold text-gray-700 mb-1">Verification Code</label>
          <input type="number" id="code" name="code" minlength="6" maxlength="6" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none tracking-widest text-center"
            placeholder="••••••" />
        </div>

        <button type="submit" id="verifyBtn"
          class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
          Verify Code
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

    <!-- Realtime Notifications + Loading -->
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
          if(state) $(btn).removeClass('opacity-50'); else $(btn).addClass('opacity-50');
        }

        // Send code AJAX
        $('#sendBtn').closest('form').on('submit', function(e) {
          e.preventDefault();
          const email = $('#email').val().trim();
          if(!email) return showMessage('Please enter your email.', 'error');

          toggleButton('#sendBtn', false);
          showMessage('Sending code...', 'warning');

          $.post('forgot_password', { email }, function(response){
            showMessage(response.message, response.type);
            toggleButton('#sendBtn', true);
          }, 'json').fail(function(){
            showMessage('Server error. Try again.', 'error');
            toggleButton('#sendBtn', true);
          });
        });

        // Verify code AJAX
        $('#verifyBtn').closest('form').on('submit', function(e) {
          e.preventDefault();
          const code = $('#code').val().trim();
          if(!code) return showMessage('Enter verification code.', 'error');

          toggleButton('#verifyBtn', false);
          showMessage('Verifying...', 'warning');

          $.post('getCode', { code }, function(response){
            showMessage(response.message, response.type);
            toggleButton('#verifyBtn', true);
            if(response.type==='success') setTimeout(()=>window.location.href='reset',1200);
          }, 'json').fail(function(){
            showMessage('Server error. Try again.', 'error');
            toggleButton('#verifyBtn', true);
          });
        });
      });
    </script>

  </body>
</html>