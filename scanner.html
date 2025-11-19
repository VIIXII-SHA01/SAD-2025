<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Code Scanner | Pacific Southbay College</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.9/minified/html5-qrcode.min.js"></script>
</head>
<body class="bg-gradient-to-br from-red-100 via-white to-gray-100 min-h-screen flex flex-col items-center p-4">

<div class="w-full max-w-xl bg-white shadow-2xl rounded-2xl p-8 mt-8 mb-8 text-center">
  <h1 class="text-3xl font-bold text-red-700 mb-6">QR Code Scanner</h1>
  <p class="text-gray-700 mb-4">Click the button below to allow access to your camera and scan a QR code.</p>

  <!-- Button to start camera -->
  <button id="startScanner" class="bg-red-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-red-700 transition mb-4">
    Start Camera
  </button>

  <!-- Scanner container -->
  <div id="reader" class="w-full rounded-xl overflow-hidden shadow-lg mb-4" style="display:none;"></div>

  <!-- Scan result -->
  <div id="result" class="bg-gray-100 text-gray-900 p-4 rounded-lg font-semibold">
    Scan a QR code to see the result here
  </div>
</div>

<script>
const html5QrCode = new Html5Qrcode("reader");
const qrConfig = { fps: 10, qrbox: 250 };

function onScanSuccess(decodedText) {
  document.getElementById('result').innerText = `QR Code Data: ${decodedText}`;
  html5QrCode.stop().then(() => console.log("Camera stopped.")).catch(err => console.error(err));
}

function onScanFailure(error) {
  // ignored silently
}

document.getElementById('startScanner').addEventListener('click', () => {
  Html5Qrcode.getCameras().then(cameras => {
    if(cameras && cameras.length) {
      // Show scanner container
      document.getElementById('reader').style.display = 'block';

      // Start first available camera
      html5QrCode.start(
        cameras[0].id,
        qrConfig,
        onScanSuccess,
        onScanFailure
      ).catch(err => {
        alert("Camera could not be started: " + err);
      });
    } else {
      alert("No cameras found on this device.");
    }
  }).catch(err => alert("Error accessing cameras: " + err));
});
</script>

</body>
</html>
