<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Scanner | Pacific Southbay College</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex items-center justify-center p-4">

  <!-- Attendance Card -->
  <div class="bg-[#F7E1E6] shadow-2xl rounded-2xl w-full max-w-md p-8 fade-in">

    <!-- Header -->
    <div class="flex flex-col items-center mb-6">
      <img src="logo.png" alt="Logo" class="w-20 h-20 object-contain mb-3">
      <h1 class="text-2xl font-bold text-red-700 text-center">
        Pacific Southbay College, INC.<br>Attendance Scanner
      </h1>
      <p class="text-sm text-gray-500">Scan your ID to mark attendance automatically</p>
    </div>

    <!-- Scanner Form -->
    <form id="scannerForm">
      <input name="lrn" id="idInput" type="text" placeholder="Scan your ID here" autofocus
             class="w-full px-4 py-3 mb-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none text-lg">
      <button type="submit" class="hidden">Submit</button>
    </form>

    <!-- Result Box -->
    <div id="result" class="bg-gray-100 text-gray-900 p-4 rounded-lg font-semibold mb-4 text-center">
      Scan an ID to see the result here
    </div>

    <!-- Attendance List -->
    <div class="bg-gray-50 p-4 rounded-lg shadow-inner w-full max-h-64 overflow-auto mb-4">
      <h2 class="font-bold text-gray-800 mb-2 text-center">Attendance List</h2>
      <ul id="attendanceList" class="list-disc list-inside text-gray-700"></ul>
    </div>

    <!-- Logout Button -->
    <div class="mt-2 text-center">
      <a href="logout">
        <button class="w-full py-2 px-3 rounded bg-red-600 hover:bg-red-700 text-white font-semibold shadow-md">
          🔒 Logout
        </button>
      </a>
    </div>

  </div>

  <!-- jQuery AJAX -->
  <script>
    $(document).ready(function () {
      const scannedIds = new Set();
      const idInput = $('#idInput');
      const result = $('#result');
      const attendanceList = $('#attendanceList');

      function showMessage(text, type='success') {
        const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500' };
        const box = $(`
          <div class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50">
            <div class="px-6 py-4 text-white rounded-xl shadow-2xl text-center text-lg ${colors[type]} animate-fade">
              ${text}
            </div>
          </div>
        `);
        $('body').append(box);
        setTimeout(() => { box.fadeOut(300, () => box.remove()); }, 1500);
      }

      // Handle form submission
      $('#scannerForm').on('submit', function(e){
        e.preventDefault();
        const idNumber = idInput.val().trim();
        idInput.val('');
        if(!idNumber) return;

        if(scannedIds.has(idNumber)){
          result.text(`ID ${idNumber} already recorded`);
          showMessage(`ID ${idNumber} already exists`, 'warning');
          return;
        }

        // Submit via AJAX to backend scan.php
        $.post('scan.php', { lrn: idNumber }, function(response){
          if(response.type === 'success'){
            scannedIds.add(idNumber);
            result.text(response.message);
            showMessage(response.message, 'success');

            const timestamp = new Date().toLocaleTimeString();
            const name = response.data.fullname || idNumber;
            attendanceList.append(`<li>${name} - ${timestamp}</li>`);
          } else {
            result.text(response.message);
            showMessage(response.message, 'error');
          }
        }, 'json').fail(function(){
          result.text('Server error. Try again.');
          showMessage('Server error. Try again.', 'error');
        });
      });

      idInput.focus();
    });
  </script>

</body>
</html>
