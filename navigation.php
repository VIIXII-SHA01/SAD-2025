<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .sidebar {
        height: 100vh; /* Full height */
    }
</style>
</head>

<body>

<!-- Mobile Toggle Button -->
<div class="lg:hidden fixed top-3 left-3">
    <button id="toggleMenu" class="text-red-700 text-3xl">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sidebar -->
<div id="sidebarMenu"
     class="sidebar fixed top-0 left-0 w-50 bg-red-600 text-white p-6 space-y-6 transform -translate-x-full lg:translate-x-0 transition-all duration-300 z-50">

    <h2 class="text-2xl font-bold mb-4">User Dashboard</h2>

    <nav class="space-y-4">

        <a href="user" class="flex items-center space-x-3 text-lg hover:text-gray-200 transition">
            <i class="fa-solid fa-user-plus text-xl"></i>
            <span>Register</span>
        </a>

        <a href="settings.php" class="flex items-center space-x-3 text-lg hover:text-gray-200 transition">
            <i class="fa-solid fa-gear text-xl"></i>
            <span>Settings</span>
        </a>

        <a href="logout" class="flex items-center space-x-3 text-lg hover:text-gray-200 transition">
            <i class="fa-solid fa-right-from-bracket text-xl"></i>
            <span>Logout</span>
        </a>

    </nav>
</div>

<script>
    const toggleButton = document.getElementById('toggleMenu');
    const sidebar = document.getElementById('sidebarMenu');

    toggleButton?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
</script>

</body>
</html>
