<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Pacific Southbay College</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- HTML5 QR Code Scanner -->
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>

<style>
  #toast {
    position: fixed; top: 20px; right: 20px;
    background: rgba(16,185,129,0.95);
    color: white; padding: 10px 14px;
    border-radius: 8px; display: none;
    z-index: 9999; font-weight: bold;
  }
</style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col md:flex-row">

<!-- SIDEBAR -->
<aside class="w-full md:w-64 bg-red-700 text-white p-6 space-y-4 fixed md:relative h-auto md:h-screen shadow-xl">
  <h2 class="text-2xl font-bold mb-6">Admin Panel</h2>

  <a href="admin"><button class="navBtn w-full py-2 px-3 rounded bg-red-500 hover:bg-red-600 text-left" data-target="usersSection">👥 Manage Users</button></a>
  <button class="navBtn w-full py-2 px-3 rounded bg-red-500 hover:bg-red-600 text-left" data-target="attendanceSection">🕒 Attendance Logs</button>
  <button class="navBtn w-full py-2 px-3 rounded bg-red-500 hover:bg-red-600 text-left" data-target="searchSection">🔍 Search Attendance</button>
 <button class="navBtn w-full py-2 px-3 rounded bg-red-500 hover:bg-red-600 text-left"> <a href="logout">🔒 Logout</a>
</button>

</aside>

<!-- MAIN CONTENT -->
<main class="flex-1 md:ml-64 p-4 md:p-8">

<div id="toast"></div>

<!-- USERS SECTION -->
<section id="usersSection" class="hidden">
  <h1 class="text-2xl md:text-3xl font-bold text-red-700 mb-4">Manage Users</h1>
  <button id="openAddUser" class="mb-4 bg-red-600 text-white py-2 px-4 rounded shadow hover:bg-red-700">+ Add User</button>
  <div class="overflow-x-auto bg-white p-4 rounded shadow">
    <table class="w-full text-left min-w-[600px]">
      <thead class="border-b">
        <tr>
          <th class="py-2 px-2">Name</th>
          <th class="py-2 px-2">Email</th>
          <th class="py-2 px-2">Role</th>
          <th class="py-2 px-2">Status</th>
          <th class="py-2 px-2">Actions</th>
        </tr>
      </thead>
      <tbody id="userTable"></tbody>
    </table>
  </div>
</section>

<!-- ATTENDANCE SECTION -->
<section id="attendanceSection" class="hidden">
  <h1 class="text-2xl md:text-3xl font-bold text-red-700 mb-4">Attendance Logs</h1>
  <div class="flex flex-col md:flex-row gap-2 mb-4">
    <input type="date" id="attendanceDate" class="px-3 py-2 border rounded flex-1">
    <button id="loadAttendance" class="bg-red-600 text-white py-2 px-4 rounded">Load</button>
  </div>
  <div class="overflow-x-auto bg-white p-4 rounded shadow">
    <table class="w-full min-w-[600px]">
      <thead>
        <tr class="border-b">
          <th class="py-2 px-2">LRN</th>
          <th class="py-2 px-2">First Name</th>
          <th class="py-2 px-2">Last Name</th>
          <th class="py-2 px-2">Course</th>
          <th class="py-2 px-2">Time In</th>
        </tr>
      </thead>
      <tbody id="attendanceTable"></tbody>
    </table>
  </div>
</section>

<!-- SEARCH SECTION -->
<section id="searchSection" class="hidden">
  <h1 class="text-2xl md:text-3xl font-bold text-red-700 mb-4">Search Attendance</h1>
  <div class="flex flex-col md:flex-row gap-2">
    <input id="searchLRN" type="text" placeholder="Enter LRN" class="px-4 py-2 border rounded flex-1">
    <button id="searchBtn" class="bg-red-600 text-white py-2 px-4 rounded">Search</button>
  </div>
  <div id="searchResults" class="mt-6 bg-white p-4 rounded shadow overflow-x-auto"></div>
</section>

<!-- SCANNER SECTION -->
<section id="scannerSection" class="hidden">
  <h1 class="text-2xl md:text-3xl font-bold text-red-700 mb-4">Scan Attendance QR</h1>
  <div class="bg-white p-4 rounded shadow flex flex-col items-center">
    <div id="qr-reader" class="w-full max-w-md h-[400px]"></div>
    <div id="qr-result" class="mt-4 text-green-600 font-bold break-words"></div>
    <button id="stopScanner" class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Stop Scanner</button>
  </div>
</section>

<!-- ADD USER MODAL -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center p-4">
  <div class="bg-white p-6 rounded shadow-xl w-full max-w-md">
    <h2 class="text-xl font-bold text-red-700 mb-4">Add New User</h2>
    <form action="addUser" method="POST">
    <input id="newLRN" class="w-full mb-3 border px-3 py-2 rounded" placeholder="Full Name" name="fullname">
    <input id="newFN" class="w-full mb-3 border px-3 py-2 rounded" placeholder="Email" name="email">
    <select id="newCourse" class="w-full mb-4 border px-3 py-2 rounded" name="role">
      <option value="">Role</option>
      <option>Admin</option>
      <option>Staff</option>
      <option>Student</option>
    </select>
    <div class="flex justify-end space-x-2">
      <button type="submit" id="saveUser" class="px-4 py-2 bg-red-600 text-white rounded">Save</button>
      <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded">Cancel</button>
    </div>
    </form>
  </div>
</div>

<script>
function showToast(msg){ $('#toast').text(msg).fadeIn().delay(2000).fadeOut(); }

$('.navBtn').click(function(){ 
  $('section').hide(); 
  $('#' + $(this).data('target')).show(); 
});

// Load Users
function loadUsers(){
  $.get('admin-users', { action: 'list' }, function(res){
    $('#userTable').html('');
    res.forEach(u=>{
      $('#userTable').append(`
        <tr class="border-b">
          <td class="py-1 px-2">${u.full_name}</td>
          <td class="py-1 px-2">${u.email}</td>
          <td class="py-1 px-2">${u.role}</td>
          <td class="py-1 px-2 font-bold ${u.status=='Active'?'text-green-600':'text-red-600'}">${u.status}</td>
          <td class="py-1 px-2">
            <button class="toggleStatus bg-red-500 text-white px-2 py-1 rounded text-xs" data-lrn="${u.user_id}">
              ${u.status === 'verified' ? 'Restrict' : 'Unrestrict'}
            </button>
          </td>
        </tr>
      `);
    });
  }, 'json');
}

// Toggle user status
$(document).on('click','.toggleStatus',function(){
  const lrn = $(this).data('lrn');
  $.post('admin-users.php',{action:'toggle',lrn},function(res){ showToast(res.message); loadUsers(); },'json');
});

// Add User Modal
$('#openAddUser').click(()=> $('#addUserModal').removeClass('hidden'));
$('.closeModal').click(()=> $('#addUserModal').addClass('hidden'));
$('#saveUser').click(function(){
  $.post('admin-users.php',{ action:'add', lrn:$('#newLRN').val(), first:$('#newFN').val(), last:$('#newLN').val(), course:$('#newCourse').val() },
    function(res){ showToast(res.message); if(res.type==='success'){ $('#addUserModal').addClass('hidden'); loadUsers(); } },'json');
});

// Attendance
$('#loadAttendance').click(function(){
  $.get('admin-attendance.php',{date:$('#attendanceDate').val()},function(res){
    $('#attendanceTable').html('');
    res.forEach(a=>$('#attendanceTable').append(`
      <tr class="border-b">
        <td class="py-1 px-2">${a.lrn}</td>
        <td class="py-1 px-2">${a.name}</td>
        <td class="py-1 px-2">${a.course}</td>
        <td class="py-1 px-2">${a.time_in}</td>
      </tr>`));
  },'json');
});

// Search
$('#searchBtn').click(function(){
  $.get('admin-attendance.php',{search:$('#searchLRN').val()},function(res){
    let out='<h2 class="font-bold text-lg mb-2">Attendance Records</h2>';
    res.forEach(r=>out+=`<p>${r.date} — <strong>${r.time_in}</strong></p>`);
    $('#searchResults').html(out);
  },'json');
});

// QR Scanner
let html5QrCode;
let qrScannerActive = false;

function startScanner() {
    if (qrScannerActive) return;

    const qrContainer = document.getElementById("qr-reader");

    // Wait until container is visible
    if (!qrContainer.offsetWidth || !qrContainer.offsetHeight) {
        requestAnimationFrame(startScanner);
        return;
    }

    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("qr-reader");
    }

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras.length) { showToast("No camera found"); return; }

        html5QrCode.start(
            cameras[0].id,
            { fps: 10, qrbox: 250 },
            message => $('#qr-result').text("Scanned: " + message),
            err => console.log("Scan error:", err)
        ).then(() => qrScannerActive = true)
          .catch(err => showToast("Camera failed to open"));
    }).catch(err => showToast("Camera error"));
}

$('.navBtn[data-target="scannerSection"]').click(function () {
    $('section').hide();
    $('#scannerSection').show();
    requestAnimationFrame(startScanner); // ensures container is visible
});

$('#stopScanner').click(function () {
    if (!html5QrCode) return;
    html5QrCode.stop().then(() => {
        qrScannerActive = false;
        $('#qr-result').text('');
    }).catch(err => console.error(err));
});

// Load default section
$('#usersSection').show();
loadUsers();
</script>

</body>
</html>
