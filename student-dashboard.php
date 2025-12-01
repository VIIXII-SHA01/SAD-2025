<?php include('navigation.php'); // optional, your navigation ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Register Student — Pacific Southbay College</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- QR + html2canvas + jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
  /* Simple toast */
  #toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(16,185,129,0.95);
    color: white;
    padding: 10px 14px;
    border-radius: 8px;
    display: none;
    z-index: 9999;
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    font-weight: 600;
  }
</style>
</head>
<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex flex-col items-center p-4">

<div class="w-full max-w-3xl bg-[#F7E1E6] shadow-2xl rounded-2xl p-8 mt-8 mb-8 lg:ml-64">
  <h1 class="text-3xl font-bold text-red-700 text-center mb-6">User Registration Form</h1>

  <!-- form: we use AJAX so method/action are not requir ed -->
  <form id="studentForm" class="space-y-5" method="POST" action="user">
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">LRN Number</label>
        <input type="text" id="lrn" name="lrn" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
        <input type="text" id="firstName" name="firstName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
      <input type="text" id="lastName" name="lastName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" />
    </div>

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Age</label>
        <input type="number" id="age" name="age" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" />
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Gender</label>
        <select id="gender" name="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Custom">Custom</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Number</label>
        <input type="text" id="contact" name="contact" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Course</label>
      <select id="course" name="course" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
        <option value="">Select Course</option>
        <option value="Accountancy, Business and Management (ABM)">Accountancy, Business and Management (ABM)</option>
        <option value="Humanities and Social Sciences (HUMSS)">Humanities and Social Sciences (HUMSS)</option>
        <option value="Sciences, Technology, Engineering, and Mathematics (STEM)">Sciences, Technology, Engineering, and Mathematics (STEM)</option>
        <option value="BS Accountacy (BSA)">BS Accountacy (BSA)</option>
        <option value="BS Management Accounting (BSMA)">BS Management Accounting (BSMA)</option>
        <option value="BS Office Administration (BSOA)">BS Office Administration (BSOA)</option>
        <option value="BS Tourism Management (BSTM)">BS Tourism Management (BSTM)</option>
        <option value="BS Criminilogy (BSCrim)">BS Criminilogy (BSCrim)</option>
        <option value="BS Social Work (BSSW)">BS Social Work (BSSW)</option>
        <option value="Bachelor Physical Education (BPEd)">Bachelor Physical Education (BPEd)</option>
        <option value="Bachelor Early Childhood Education (BECEd)">Bachelor Early Childhood Education (BECEd)</option>
      </select>
    </div>

    <button id="submitBtn" type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
      Submit
    </button>
  </form>
</div>

<div id="cardContainer" class="w-full flex flex-wrap gap-10 justify-center mb-8"></div>

<div id="toast"></div>

<script>
$(function() {
  function showToast(message, duration = 3000) {
    $('#toast').stop(true, true).text(message).fadeIn(200).delay(duration).fadeOut(400);
  }

  // generate card function: creates card in DOM and wires download button
  function generateCard(data) {
    // data: object with lrn, firstName, lastName, age, gender, contact, course
    const { lrn, firstName, lastName, age, gender, contact, course } = data;

    $('#cardContainer').empty();

    const wrapper = $('<div>').addClass('flex flex-col items-center space-y-2');
    const card = $(`
      <div class="relative w-[340px] h-[215px] bg-red-400 overflow-hidden shadow-lg rounded-md" id="card-${lrn}">
        <div class="absolute top-0 right-0 w-[60%] h-[60%] bg-red-500 -skew-y-6"></div>
        <div class="absolute bottom-0 left-0 w-[70%] h-[50%] bg-red-600 -skew-y-6"></div>

        <div class="relative z-10 flex px-3 py-2 h-full">
          <div class="w-1/2 flex flex-col items-center justify-center">
            <img src="logo.png" class="w-14 mb-1" alt="logo" />
            <div id="qrcode-${lrn}" class="bg-white p-1 shadow-md"></div>
          </div>
          <div class="w-1/2 text-black pl-2 flex flex-col justify-center text-[10px]">
            <h1 class="text-[12px] font-bold mb-1">ATTENDANCE CARD</h1>
            <p class="font-semibold mb-0.5">Name: ${firstName} ${lastName}</p>
            <p class="font-semibold mb-0.5">Course: ${course}</p>
            <p class="font-semibold">LRN: ${lrn}</p>
          </div>
        </div>
      </div>
    `);

    const downloadBtn = $('<button>')
      .addClass('bg-red-600 text-white py-1 px-4 rounded font-semibold hover:bg-red-700 transition shadow-md text-xs')
      .text('Download Attendance Card');

    wrapper.append(card).append(downloadBtn);
    $('#cardContainer').append(wrapper);

    // Generate QR (size purposely big enough then we scale when exporting)
    new QRCode(document.getElementById(`qrcode-${lrn}`), {
      text: `LRN: ${lrn}`,
      width: 140,
      height: 140,
      colorDark: "#000000",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });

    // Download handler: high scale for crisp scanning
    downloadBtn.on('click', function() {
      // temporarily enlarge card for export to ensure print-quality
      html2canvas(card[0], { scale: 1, useCORS: true }).then(canvas => {
        const link = document.createElement('a');
        link.download = `${firstName}_${lastName}_AttendanceCard.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
        showToast('Attendance card downloaded.');
      }).catch(err => {
        console.error(err);
        showToast('Failed to generate image.');
      });
    });
  }

  // AJAX submit
  $('#studentForm').on('submit', function(e) {
    e.preventDefault();

    const payload = {
      lrn: $('#lrn').val().trim(),
      firstName: $('#firstName').val().trim(),
      lastName: $('#lastName').val().trim(),
      age: $('#age').val().trim(),
      gender: $('#gender').val(),
      contact: $('#contact').val().trim(),
      course: $('#course').val()
    };

    // client-side quick validation
    for (const key in payload) {
      if (payload[key] === '' || payload[key] === null) {
        showToast('Please fill in all fields.');
        return;
      }
    }

    // disable submit while request in progress
    $('#submitBtn').prop('disabled', true).addClass('opacity-60 cursor-not-allowed');

    $.ajax({
      url: 'user', // <-- backend endpoint
      method: 'POST',
      data: payload,
      dataType: 'json',
      success: function(res) {
        if (res.type === 'success') {
          showToast(res.message || 'Saved.');
          // build card after successful DB insert
          generateCard(payload);
          // optionally clear form
          $('#studentForm')[0].reset();
        } else {
          showToast(res.message || 'Failed to save.');
        }
      },
      error: function(xhr, status, err) {
        console.error('AJAX error:', status, err, xhr.responseText);
        showToast('User already registered or server error.');
      },
      complete: function() {
        $('#submitBtn').prop('disabled', false).removeClass('opacity-60 cursor-not-allowed');
      }
    });
  });
});
</script>
</body>
</html>
