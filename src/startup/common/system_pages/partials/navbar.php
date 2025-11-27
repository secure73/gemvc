<?php
/**
 * Navigation Bar Partial
 * 
 * @var string $pageToShow Current page identifier
 * @var string $gemvcLogoUrl Base64 encoded GEMVC logo URL
 */
?>
<!-- Fixed Top Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 w-full bg-white shadow-md z-50">
    <div class="max-w-6xl mx-auto px-10 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <?php if ($gemvcLogoUrl): ?>
                    <form method="POST" class="inline m-0 p-0">
                        <input type="hidden" name="set_page" value="developer-welcome">
                        <button type="submit" class="inline-flex items-center p-0 m-0 bg-transparent border-0 cursor-pointer">
                            <img class="h-8 w-auto" src="<?php echo htmlspecialchars($gemvcLogoUrl); ?>" alt="GEMVC Framework" />
                        </button>
                    </form>
                <?php endif; ?>
                <form method="POST" class="inline m-0 p-0">
                    <input type="hidden" name="set_page" value="developer-welcome">
                    <button type="submit" class="text-base font-medium transition-colors <?php echo $pageToShow === 'developer-welcome' ? 'text-gemvc-green border-b-2 border-gemvc-green pb-1' : 'text-gray-600 hover:text-gemvc-green'; ?>">
                        Home
                    </button>
                </form>
                <form method="POST" class="inline m-0 p-0">
                    <input type="hidden" name="set_page" value="database">
                    <button type="submit" class="text-base font-medium transition-colors <?php echo $pageToShow === 'database' ? 'text-gemvc-green border-b-2 border-gemvc-green pb-1' : 'text-gray-600 hover:text-gemvc-green'; ?>">
                        Database
                    </button>
                </form>
            </div>
            <div class="flex items-center gap-4">
                <form method="POST" class="inline m-0 p-0">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="text-base font-medium transition-colors text-gray-600 hover:text-red-600">
                        Logout
                    </button>
                </form>
                <a href="https://buymeacoffee.com/gemvc" target="_blank" 
                    class="inline-flex items-center gap-2 bg-yellow-200 hover:bg-yellow-300 text-gray-800 no-underline font-medium transition-colors text-sm px-4 py-2 rounded-lg shadow-md hover:shadow-lg">
                    <span>Buy us a coffee</span>
                    <svg class="w-5 h-5 text-gemvc-green" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</nav>

