<?php
session_start();

if (!isset($_SESSION['cards'])) {
    $_SESSION['cards'] = [];
}

// 1. PHP 8.1+ Safe Add Logic
if (isset($_POST['add_card'])) {
    $raw_input = $_POST['questions_input'] ?? ''; 
    $input = trim($raw_input);

    if (!empty($input)) {
        $lines = explode("\n", str_replace("\r", "", $input));
        foreach ($lines as $line) {
            $clean_question = trim($line);
            if (!empty($clean_question)) {
                $_SESSION['cards'][] = [
                    'id' => uniqid(),
                    'question' => htmlspecialchars($clean_question),
                    'taken' => false
                ];
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 2. Action Handlers
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $_SESSION['cards'] = array_filter($_SESSION['cards'], function($card) use ($id) {
        return $card['id'] !== $id;
    });
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['mark_taken'])) {
    $id = $_GET['mark_taken'];
    foreach ($_SESSION['cards'] as &$card) {
        if ($card['id'] === $id) {
            $card['taken'] = !($card['taken']);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Reset "Taken" status only
if (isset($_POST['unlock_all'])) {
    foreach ($_SESSION['cards'] as &$card) {
        $card['taken'] = false;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// NEW: Clear all questions completely
if (isset($_POST['clear_all'])) {
    $_SESSION['cards'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaCards - Flip System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-container { perspective: 1000px; height: 280px; }
        .card-inner {
            position: relative; width: 100%; height: 100%;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }
        .flipped { transform: rotateY(180deg); }
        
        .card-front, .card-back {
            position: absolute; width: 100%; height: 100%;
            -webkit-backface-visibility: hidden; backface-visibility: hidden;
            border-radius: 1.25rem; padding: 1.5rem;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .card-back { transform: rotateY(180deg); }
        
        .cursor-pointer { cursor: pointer; }
        .cursor-not-allowed { cursor: not-allowed; }
        
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

        /* Highlight effect for random selection */
        .picking { ring: 4px; ring-color: #6366f1; transform: scale(1.05); }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 md:p-12">

    <div class="max-w-5xl mx-auto">
        <header class="text-center mb-10">
            <h1 class="text-5xl font-black text-indigo-500 mb-2">NovaCards</h1>
            <p class="text-slate-500">Add multiple questions below. Cards marked as "Taken" will be locked.</p>
            <p class="text-slate-600 text-sm mt-2 uppercase tracking-widest font-bold">Developed by Oalden Morales</p>
        </header>

        <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-2xl mb-12">
            <form method="POST">
                <textarea name="questions_input" rows="3" 
                    class="w-full p-4 rounded-2xl bg-slate-800 border border-slate-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none mb-4"
                    placeholder="Type questions here (one per line)..."></textarea>
                
                <div class="flex flex-wrap gap-4 justify-between items-center">
                    <div class="flex gap-2">
                        <button type="submit" name="add_card" class="bg-indigo-600 hover:bg-indigo-500 px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20">
                            Create Cards
                        </button>
                        <button type="button" onclick="randomSelect()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-6 py-3 rounded-xl font-black transition-all shadow-lg">
                            🎲 Random Select
                        </button>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" name="unlock_all" class="text-slate-400 hover:text-white flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-800 transition hover:bg-slate-800 text-sm">
                            🔄 Unlock All
                        </button>
                        <button type="submit" name="clear_all" onclick="return confirm('Are you sure you want to delete ALL questions?')" class="text-rose-400 hover:text-white flex items-center gap-2 px-4 py-2 rounded-xl border border-rose-900/30 transition hover:bg-rose-900/20 text-sm font-bold">
                            🗑️ Clear All
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8" id="cardGrid">
            <?php foreach (array_values($_SESSION['cards']) as $index => $card): ?>
                <div class="card-container" data-taken="<?php echo $card['taken'] ? 'true' : 'false'; ?>">
                    <div class="card-inner <?php echo $card['taken'] ? 'cursor-not-allowed' : 'cursor-pointer'; ?>" 
                         onclick="<?php echo !$card['taken'] ? "this.classList.toggle('flipped')" : ""; ?>">
                        
                        <div class="card-front bg-slate-900 border-2 <?php echo $card['taken'] ? 'border-emerald-500/30 opacity-40 grayscale' : 'border-slate-800 shadow-xl'; ?>">
                            <span class="absolute top-4 left-5 text-[20px] font-bold text-slate-600 tracking-tighter">Question #<?php echo $index + 1; ?></span>
                            
                            <?php if ($card['taken']): ?>
                                <div class="text-emerald-500 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-emerald-500 uppercase tracking-widest">Taken</span>
                            <?php else: ?>
                                <div class="text-7xl font-black text-slate-800"><?php echo $index + 1; ?></div>
                            <?php endif; ?>

                            <div class="flex gap-2 mt-6" onclick="event.stopPropagation();">
                                <a href="?mark_taken=<?php echo $card['id']; ?>" class="<?php echo $card['taken'] ? 'bg-emerald-600' : 'bg-slate-700 hover:bg-indigo-600'; ?> text-[10px] font-black uppercase px-4 py-2 rounded-lg transition-colors">
                                    <?php echo $card['taken'] ? 'Unlock' : 'Take'; ?>
                                </a>
                                <a href="?delete=<?php echo $card['id']; ?>" class="bg-rose-950/50 text-rose-500 hover:bg-rose-600 hover:text-white text-[10px] font-black uppercase px-4 py-2 rounded-lg transition-colors">
                                    Delete
                                </a>
                            </div>
                        </div>

                        <div class="card-back bg-gradient-to-br from-indigo-700 to-indigo-900 shadow-2xl overflow-hidden">
                            <div class="w-full h-full overflow-y-auto custom-scroll p-4 flex items-center justify-center">
                                <p class="text-center text-xl font-medium leading-snug">
                                    <?php echo $card['question']; ?>
                                </p>
                            </div>
                            <div class="absolute bottom-4 text-[9px] uppercase tracking-widest text-indigo-300 font-bold opacity-50">Click to hide</div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function randomSelect() {
            // Get all card containers that are NOT marked as taken
            const allCards = document.querySelectorAll('.card-container[data-taken="false"]');
            
            if (allCards.length === 0) {
                alert("No available cards to select! Unlock cards or add new ones.");
                return;
            }

            // Close any cards that are already flipped (optional, for better UX)
            document.querySelectorAll('.card-inner.flipped').forEach(card => {
                card.classList.remove('flipped');
            });

            // Pick a random index
            const randomIndex = Math.floor(Math.random() * allCards.length);
            const selectedContainer = allCards[randomIndex];
            const innerCard = selectedContainer.querySelector('.card-inner');

            // Scroll the selected card into view smoothly
            selectedContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Add a small delay then flip the card
            setTimeout(() => {
                innerCard.classList.add('flipped');
                // Optional: add a temporary highlight effect
                innerCard.classList.add('ring-4', 'ring-indigo-500', 'rounded-3xl');
                setTimeout(() => {
                    innerCard.classList.remove('ring-4', 'ring-indigo-500');
                }, 2000);
            }, 500);
        }
    </script>
</body>
</html>
