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

if (isset($_POST['unlock_all'])) {
    foreach ($_SESSION['cards'] as &$card) {
        $card['taken'] = false;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

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

        .roulette-wheel {
            width: 320px;
            height: 320px;
            border-radius: 9999px;
            border: 8px solid rgba(99, 102, 241, 0.4);
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.6);
            transition: transform 1.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .roulette-label {
            position: absolute;
            left: 50%;
            top: 50%;
            transform-origin: 0 0;
            color: #0f172a;
            font-weight: 700;
            font-size: 0.8rem;
            text-shadow: 0 1px 2px rgba(255,255,255,0.6);
            white-space: nowrap;
        }

        .roulette-pointer {
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-bottom: 24px solid #f59e0b;
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
        }

        .name-chip-selected {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #0f172a;
            border-color: transparent;
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.4);
        }

        .roulette-winner {
            animation: winnerPulse 1s ease-in-out infinite;
        }

        @keyframes winnerPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .roulette-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .roulette-modal-card {
            background: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            width: min(420px, 90vw);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.7);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 md:p-12">

    <div class="max-w-5xl mx-auto">
        <div id="novacardsView">
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
                        <button type="button" onclick="showRoulette()" class="bg-slate-800 hover:bg-slate-700 text-indigo-200 px-6 py-3 rounded-xl font-black transition-all shadow-lg border border-slate-700">
                            🎡 Name Roulette
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
@@ -157,63 +233,292 @@ if (isset($_POST['clear_all'])) {
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

        <section id="rouletteView" class="hidden">
            <header class="text-center mb-10">
                <h2 class="text-4xl font-black text-amber-400 mb-2">Name Roulette</h2>
                <p class="text-slate-500">Add names, spin the wheel, and decide whether the selected name stays.</p>
            </header>

            <div id="rouletteModal" class="roulette-modal hidden">
                <div class="roulette-modal-card">
                    <p class="text-xs uppercase tracking-widest text-slate-400 mb-3">Selected Name</p>
                    <p id="modalSelectedName" class="text-4xl font-black text-amber-300 mb-2">—</p>
                    <p id="modalStatus" class="text-sm text-slate-400 mb-6"></p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button id="modalRemoveButton" type="button" onclick="removeSelectedName()" class="bg-rose-500 hover:bg-rose-400 text-slate-950 px-6 py-3 rounded-xl font-black transition-all shadow-lg">
                            Remove from Wheel
                        </button>
                        <button type="button" onclick="closeResultModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-6 py-3 rounded-xl font-black transition-all shadow-lg border border-slate-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-2xl mb-10">
                <div class="flex flex-col md:flex-row gap-6 items-center">
                    <div class="flex-1 w-full">
                        <label class="block text-xs uppercase tracking-widest text-slate-400 mb-2">Add names (comma or new line separated)</label>
                        <div class="flex flex-col md:flex-row gap-2">
                            <textarea id="nameInput" rows="3" class="flex-1 p-3 rounded-xl bg-slate-800 border border-slate-700 focus:ring-2 focus:ring-amber-400 focus:outline-none" placeholder="Type names here..."></textarea>
                            <button type="button" onclick="addName()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-6 py-3 rounded-xl font-black transition-all shadow-lg">
                                Add Names
                            </button>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" onclick="spinWheel()" class="bg-indigo-600 hover:bg-indigo-500 px-6 py-3 rounded-xl font-bold transition-all shadow-lg">
                                Spin
                            </button>
                            <label class="flex items-center gap-2 text-sm text-slate-300 bg-slate-800 border border-slate-700 px-4 py-2 rounded-xl">
                                <input id="removeAfterSpin" type="checkbox" class="accent-rose-500" />
                                Remove selected after spin
                            </label>
                            <button type="button" onclick="resetNames()" class="text-rose-300 hover:text-white flex items-center gap-2 px-4 py-2 rounded-xl border border-rose-900/30 transition hover:bg-rose-900/20 text-sm font-bold">
                                🧹 Reset Names
                            </button>
                            <button type="button" onclick="showNovaCards()" class="text-slate-200 hover:text-white flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-700 transition hover:bg-slate-800 text-sm font-bold">
                                ↩️ Back to NovaCards
                            </button>
                        </div>
                    </div>

                    <div class="relative flex flex-col items-center gap-4">
                        <div class="roulette-wheel bg-slate-200" id="rouletteWheel">
                            <span class="roulette-pointer"></span>
                        </div>
                        <div class="text-center">
                            <p class="text-xs uppercase tracking-widest text-slate-500">Selected</p>
                            <p id="selectedName" class="text-xl font-black text-amber-300">—</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="text-xs uppercase tracking-widest text-slate-400 mb-2">Names on the wheel</p>
                    <div id="nameList" class="flex flex-wrap gap-2"></div>
                    <p id="emptyNames" class="text-sm text-slate-500 mt-3">No names yet. Add a few to start spinning!</p>
                </div>
            </div>
        </section>
    </div>

    <script>
        const novacardsView = document.getElementById('novacardsView');
        const rouletteView = document.getElementById('rouletteView');
        const rouletteWheel = document.getElementById('rouletteWheel');
        const nameInput = document.getElementById('nameInput');
        const nameList = document.getElementById('nameList');
        const emptyNames = document.getElementById('emptyNames');
        const selectedName = document.getElementById('selectedName');
        const removeAfterSpin = document.getElementById('removeAfterSpin');
        const rouletteModal = document.getElementById('rouletteModal');
        const modalSelectedName = document.getElementById('modalSelectedName');
        const modalStatus = document.getElementById('modalStatus');
        const modalRemoveButton = document.getElementById('modalRemoveButton');

        const wheelColors = ['#fef08a', '#fed7aa', '#fbcfe8', '#e9d5ff', '#bae6fd', '#bbf7d0', '#fde68a', '#fecaca'];
        let rouletteNames = [];
        let lastSelectedIndex = null;
        let currentRotation = 0;
        let isSpinning = false;
        let hasRemovedSelected = false;

        function showRoulette() {
            novacardsView.classList.add('hidden');
            rouletteView.classList.remove('hidden');
        }

        function showNovaCards() {
            rouletteView.classList.add('hidden');
            novacardsView.classList.remove('hidden');
        }

        function addName() {
            const value = nameInput.value.trim();
            if (!value) {
                nameInput.focus();
                return;
            }
            const entries = value
                .split(/[\n,]+/)
                .map(name => name.trim())
                .filter(name => name.length > 0);
            if (entries.length === 0) {
                nameInput.focus();
                return;
            }
            rouletteNames = rouletteNames.concat(entries);
            nameInput.value = '';
            renderRoulette();
        }

        function resetNames() {
            rouletteNames = [];
            lastSelectedIndex = null;
            selectedName.textContent = '—';
            selectedName.classList.remove('roulette-winner');
            currentRotation = 0;
            rouletteWheel.style.transform = 'rotate(0deg)';
            closeResultModal();
            renderRoulette();
        }

        function spinWheel() {
            if (rouletteNames.length === 0) {
                alert('Add at least one name to spin the wheel.');
                return;
            }
            if (isSpinning) {
                return;
            }
            closeResultModal();
            isSpinning = true;
            selectedName.classList.remove('roulette-winner');
            const count = rouletteNames.length;
            lastSelectedIndex = Math.floor(Math.random() * count);
            const degreesPerSlice = 360 / count;
            const targetRotation = 360 - (degreesPerSlice * lastSelectedIndex) - (degreesPerSlice / 2);
            const extraSpins = 360 * (Math.floor(Math.random() * 3) + 4);
            currentRotation += extraSpins + targetRotation;
            rouletteWheel.style.transform = `rotate(${currentRotation}deg)`;

            const name = rouletteNames[lastSelectedIndex];
            setTimeout(() => {
                selectedName.textContent = name;
                selectedName.classList.add('roulette-winner');
                openResultModal(name);

                if (removeAfterSpin.checked) {
                    removeSelectedName(true);
                }
                renderRoulette();
                isSpinning = false;
            }, 1800);
        }

        function openResultModal(name) {
            modalSelectedName.textContent = name;
            modalStatus.textContent = '';
            modalRemoveButton.disabled = false;
            modalRemoveButton.classList.remove('opacity-50', 'cursor-not-allowed');
            hasRemovedSelected = false;
            rouletteModal.classList.remove('hidden');
        }

        function closeResultModal() {
            rouletteModal.classList.add('hidden');
        }

        function removeSelectedName(isAuto = false) {
            if (lastSelectedIndex === null || hasRemovedSelected) {
                return;
            }
            rouletteNames.splice(lastSelectedIndex, 1);
            lastSelectedIndex = null;
            hasRemovedSelected = true;
            modalStatus.textContent = isAuto ? 'Removed from the wheel.' : 'Name removed. Spin again!';
            modalRemoveButton.disabled = true;
            modalRemoveButton.classList.add('opacity-50', 'cursor-not-allowed');
            renderRoulette();
        }

        function renderRoulette() {
            nameList.innerHTML = '';
            rouletteWheel.querySelectorAll('.roulette-label').forEach(label => label.remove());

            if (rouletteNames.length === 0) {
                rouletteWheel.style.background = '#1e293b';
                emptyNames.classList.remove('hidden');
                return;
            }

            emptyNames.classList.add('hidden');

            rouletteNames.forEach((name, index) => {
                const chip = document.createElement('span');
                chip.className = 'px-3 py-1 rounded-full bg-slate-800 text-slate-200 text-xs font-semibold border border-slate-700 transition';
                chip.textContent = name;
                if (index === lastSelectedIndex) {
                    chip.classList.add('name-chip-selected');
                }
                nameList.appendChild(chip);

                const label = document.createElement('span');
                label.className = 'roulette-label';
                const angle = (360 / rouletteNames.length) * index;
                label.style.transform = `rotate(${angle}deg) translate(110px, -10px)`;
                label.textContent = name;
                rouletteWheel.appendChild(label);
            });

            const gradients = rouletteNames.map((_, index) => {
                const start = (360 / rouletteNames.length) * index;
                const end = (360 / rouletteNames.length) * (index + 1);
                const color = wheelColors[index % wheelColors.length];
                return `${color} ${start}deg ${end}deg`;
            });
            rouletteWheel.style.background = `conic-gradient(${gradients.join(',')})`;
        }

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

        renderRoulette();
    </script>
</body>
</html>