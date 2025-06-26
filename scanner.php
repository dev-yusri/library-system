<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$current_page = 'scan';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Scan Book QR Code</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-gray-50 ">
<div class="min-h-screen flex flex-col md:flex-row">
<?php include 'menu/sidebar.php'; ?>

<main class="flex-1 px-2 py-6  flex items-center justify-center">
  <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg p-6 sm:p-8 flex flex-col items-center">
    <h1 class="text-2xl sm:text-3xl font-bold text-black mb-3 text-center">QR Code Scanner</h1>
    <h3 class="text-black mb-4 text-center">Scan a book's QR code to quickly check-in or check-out</h3>

    <div id="qr-reader" class="w-full aspect-video bg-gray-200 rounded-xl flex items-center justify-center text-gray-500 text-lg mb-4">
      Camera preview will appear here
    </div>

    <div class="flex gap-4 flex-col sm:flex-row">
      <button onclick="startScanner()" id="start-btn"
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg shadow mb-4 sm:mb-0">
        Start Scanning
      </button>

      <label for="image-picker" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg shadow cursor-pointer text-center">
        Select QR Image
        <input type="file" id="image-picker" accept="image/*" class="hidden">
      </label>
    </div>

    <a class="mt-4 text-indigo-600 hover:text-indigo-700 font-small" href="book.php"> Or browse books manually →</a>

    <div id="book-details" class="hidden w-full bg-indigo-50 border border-indigo-200 rounded-lg p-5 text-sm sm:text-base">
      <h2 class="text-lg font-semibold text-indigo-700 mb-3">Book Information</h2>
      <p class="mb-1"><strong>Title:</strong> <span id="book-title" class="text-gray-800"></span></p>
      <p class="mb-1"><strong>Author:</strong> <span id="book-author" class="text-gray-800"></span></p>
      <p class="mb-3"><strong>Status:</strong> <span id="book-status" class="text-gray-800"></span></p>

      <button id="confirm-btn"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
        Confirm Borrow/Return
      </button>
    </div>
  </div>
</main>

<script>
  let qrReader;

  function startScanner() {
    document.getElementById('qr-reader').innerHTML = "";

    qrReader = new Html5Qrcode("qr-reader");

    Html5Qrcode.getCameras().then(devices => {
      if (devices && devices.length) {
        qrReader.start(
          { facingMode: "environment" },
          {
            fps: 10,
            qrbox: { width: 250, height: 250 }
          },
          qrCodeMessage => {
            qrReader.stop();
            showDetails(qrCodeMessage);
          },
          errorMessage => {
            // Handle camera errors silently
          }
        );
      } else {
        alert("No camera found.");
      }
    }).catch(err => {
      console.error(err);
      alert("Camera access denied or not available.");
    });
  }

  // Handle image upload
  document.getElementById("image-picker").addEventListener("change", function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const qrScanner = new Html5Qrcode("qr-reader");
    qrScanner.scanFile(file, true)
      .then(decodedText => {
        showDetails(decodedText);
      })
      .catch(err => {
        console.error("QR Code not found", err);
        alert("QR code not recognized from the image.");
      });
  });

  function showDetails(data) {
    // Assuming QR code contains book ID directly
    fetch(`book_fetch.php?book_id=${data}`)
      .then(response => response.json())
      .then(book => {
        if (book.success) {
          // 🔁 Redirect to book detail page
          window.location.href = `book_detail.php?id=${book.id}`;
        } else {
          alert('Book not found.');
        }
      });
  }
</script>

</body>
</html>
