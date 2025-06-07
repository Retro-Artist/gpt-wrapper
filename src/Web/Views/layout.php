<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'OpenAI Webchat' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': {
                            50: '#f0f9ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/chat" class="text-xl font-bold text-gray-900">OpenAI Webchat</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/chat" class="text-gray-700 hover:text-gray-900">Chat</a>
                    <a href="/threads" class="text-gray-700 hover:text-gray-900">Threads</a>
                    <a href="/agents" class="text-gray-700 hover:text-gray-900">Agents</a>
                    <span class="text-gray-500">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                    <a href="/logout" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?= isset($_SESSION['user_id']) ? 'pt-4' : '' ?>">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-auto">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                © <?= date('Y') ?> OpenAI Webchat - Built with clarity and maintainability in mind
            </p>
        </div>
    </footer>
</body>
</html>