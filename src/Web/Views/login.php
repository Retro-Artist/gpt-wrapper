<?php 
$pageTitle = 'Login - OpenAI Webchat';
ob_start(); 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h1 class="text-center text-3xl font-bold text-gray-900 mb-2">OpenAI Webchat</h1>
            <h2 class="text-center text-xl text-gray-600">Sign in to your account</h2>
        </div>
        
        <?php if (isset($error) && $error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" action="/login" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input 
                        id="username" 
                        name="username" 
                        type="text" 
                        required 
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Enter your username"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        required 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Enter your password"
                    >
                </div>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    Sign in
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="/register" class="font-medium text-primary-600 hover:text-primary-500">
                        Sign up
                    </a>
                </p>
            </div>
        </form>
        
        <!-- Demo Account Info -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <h3 class="text-sm font-medium text-blue-800">Demo Account</h3>
            <p class="text-sm text-blue-700 mt-1">
                Username: <strong>demo</strong><br>
                Password: <strong>password</strong>
            </p>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>