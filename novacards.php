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
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
        }

        /* Card Flip Styles - FIXED */
        .card-container {
            perspective: 1000px;
            min-height: 280px;
            width: 100%;
        }

        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 280px;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .card-inner.flipped {
            transform: rotateY(180deg);
        }

        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            min-height: 280px;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 1.25rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .card-back {
            transform: rotateY(180deg);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .cursor-not-allowed {
            cursor: not-allowed;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Roulette Styles */
        .roulette-wheel {
            width: clamp(250px, 90vw, 320px);
            height: clamp(250px, 90vw, 320px);
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
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
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
            z-index: 10;
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
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.08);
            }
        }

        .roulette-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 1rem;
        }

        .roulette-modal-card {
            background: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            width: min(420px, 90vw);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.7);
        }

        /* Responsive utilities */
        @media (max-width: 640px) {
            .card-front, .card-back {
                min-height: 240px;
                padding: 1rem;
            }

            .card-container {
                min-height: 240px;
            }

            .card-inner {
                min-height: 240px;
            }

            .card-inner > * {
                min-height: 240px;
            }

            h1 {
                font-size: 2.25rem !important;
            }

            h2 {
                font-size: 1.875rem !important;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 0.75rem !important;
            }
        }

        /* Prevent layout shift on button state change */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-buttons a {
            flex: 1;
            min-width: 70px;
            text-align: center;
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 md:p-6 lg:p-12">

    <div class="max-w-6xl mx-auto w-full">
        <div id="novacardsView" class="w-full">
            <header class="text-center mb-8 md:mb-10">
                <h1 class="text-4xl md:text-5xl font-black text-indigo-500 mb-2">NovaCards</h1>
                <p class="text-slate-500 text-sm md:text-base">Add multiple questions below. Cards marked as "Taken" will be locked.</p>
                <p class="text-slate-600 text-xs md:text-sm mt-2 uppercase tracking-widest font-bold">Developed by Oalden Morales</p>
            </header>

            <div class="bg-slate-900 p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-800 shadow-2xl mb-8 md:mb-12 w-full">
                <form method="POST" class="w-full">
                    <textarea name="questions_input" rows="3"
                        class="w-full p-3 md:p-4 rounded-xl md:rounded-2xl bg-slate-800 border border-slate-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none mb-4 text-sm md:text-base"
                        placeholder="Type questions here (one per line)..."></textarea>

                    <div class="flex flex-col gap-3 md:gap-4 md:flex-row md:justify-between md:items-center">
                        <div class="flex flex-wrap gap-2 w-full md:w-auto">
                            <button type="submit" name="add_card" class="bg-indigo-600 hover:bg-indigo-500 px-4 md:px-8 py-2 md:py-3 rounded-lg md:rounded-xl font-bold text-sm md:text-base transition-all shadow-lg shadow-indigo-500/20 flex-1 md:flex-none">
                                Create Cards
                            </button>
                            <button type="button" onclick="randomSelect()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-black text-sm md:text-base transition-all shadow-lg flex-1 md:flex-none">
                                🎲 Random
                            </button>
                            <button type="button" onclick="showRoulette()" class="bg-slate-800 hover:bg-slate-700 text-indigo-200 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-black text-sm md:text-base transition-all shadow-lg border border-slate-700 flex-1 md:flex-none">
                                🎡 Roulette
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 w-full md:w-auto">
                            <button type="submit" name="unlock_all" class="text-slate-400 hover:text-white flex items-center justify-center gap-1 md:gap-2 px-3 md:px-4 py-2 md:py-2 rounded-lg md:rounded-xl border border-slate-800 transition hover:bg-slate-800 text-xs md:text-sm font-bold flex-1 md:flex-none">
                                🔄 <span class="hidden sm:inline">Unlock All</span><span class="sm:hidden">Unlock</span>
                            </button>
                            <button type="submit" name="clear_all" onclick="return confirm('Are you sure you want to delete ALL questions?')" class="text-rose-400 hover:text-white flex items-center justify-center gap-1 md:gap-2 px-3 md:px-4 py-2 md:py-2 rounded-lg md:rounded-xl border border-rose-900/30 transition hover:bg-rose-900/20 text-xs md:text-sm font-bold flex-1 md:flex-none">
                                🗑️ <span class="hidden sm:inline">Clear All</span><span class="sm:hidden">Clear</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8 w-full" id="cardGrid">
                <?php foreach (array_values($_SESSION['cards']) as $index => $card): ?>
                    <div class="card-container w-full" data-taken="<?php echo $card['taken'] ? 'true' : 'false'; ?>">
                        <div class="card-inner <?php echo $card['taken'] ? 'cursor-not-allowed' : 'cursor-pointer'; ?>"
                            onclick="<?php echo !$card['taken'] ? "this.classList.toggle('flipped')" : ''; ?>">

                            <div class="card-front bg-slate-900 border-2 <?php echo $card['taken'] ? 'border-emerald-500/30 opacity-40 grayscale' : 'border-slate-800 shadow-xl'; ?>">
                                <span class="absolute top-3 left-4 md:top-4 md:left-5 text-base md:text-xl font-bold text-slate-600 tracking-tighter">Q#<?php echo $index + 1; ?></span>

                                <?php if ($card['taken']): ?>
                                    <div class="text-emerald-500 text-center mb-2">
                                        <div class="text-2xl md:text-3xl font-black">✓</div>
                                        <div class="text-xs md:text-sm font-bold">TAKEN</div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-slate-600 text-center text-xs md:text-sm font-semibold">Click to view question</p>
                                <?php endif; ?>

                                <div class="action-buttons absolute bottom-4 left-4 right-4" onclick="event.stopPropagation();">
                                    <a href="?mark_taken=<?php echo $card['id']; ?>" class="<?php echo $card['taken'] ? 'bg-emerald-600' : 'bg-slate-700 hover:bg-indigo-600'; ?> text-white text-[10px] md:text-xs font-black uppercase px-2 md:px-4 py-1 md:py-2 rounded-lg transition-colors">
                                        <?php echo $card['taken'] ? 'Unlock' : 'Take'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $card['id']; ?>" class="bg-rose-950/50 text-rose-500 hover:bg-rose-600 hover:text-white text-[10px] md:text-xs font-black uppercase px-2 md:px-4 py-1 md:py-2 rounded-lg transition-colors">
                                        Delete
                                    </a>
                                </div>
                            </div>

                            <div class="card-back bg-gradient-to-br from-indigo-700 to-indigo-900 shadow-2xl overflow-hidden flex flex-col items-center justify-center">
                                <div class="w-full h-full overflow-y-auto custom-scroll p-4 flex items-center justify-center">
                                    <p class="text-center text-lg md:text-xl font-medium leading-snug">
                                        <?php echo $card['question']; ?>
                                    </p>
                                </div>
                                <div class="absolute bottom-4 text-[8px] md:text-[9px] uppercase tracking-widest text-indigo-300 font-bold opacity-50 px-4 text-center">Click to hide</div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($_SESSION['cards'])): ?>
                <div class="text-center py-12 md:py-16">
                    <p class="text-slate-500 text-base md:text-lg">No cards yet. Add your first question above!</p>
                </div>
            <?php endif; ?>
        </div>

        <section id="rouletteView" class="hidden w-full">
            <header class="text-center mb-8 md:mb-10">
                <h2 class="text-3xl md:text-4xl font-black text-amber-400 mb-2">Name Roulette</h2>
                <p class="text-slate-500 text-sm md:text-base">Add names, spin the wheel, and decide whether the selected name stays.</p>
            </header>

            <div id="rouletteModal" class="roulette-modal hidden">
                <div class="roulette-modal-card">
                    <p class="text-xs uppercase tracking-widest text-slate-400 mb-3">Selected Name</p>
                    <p id="modalSelectedName" class="text-3xl md:text-4xl font-black text-amber-300 mb-2">—</p>
                    <p id="modalStatus" class="text-xs md:text-sm text-slate-400 mb-6"></p>
                    <div class="flex flex-col gap-2 md:gap-3 md:flex-row md:justify-center">
                        <button id="modalRemoveButton" type="button" onclick="removeSelectedName()" class="bg-rose-500 hover:bg-rose-400 text-slate-950 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-black text-xs md:text-base transition-all shadow-lg flex-1 md:flex-none">
                            Remove from Wheel
                        </button>
                        <button type="button" onclick="closeResultModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-black text-xs md:text-base transition-all shadow-lg border border-slate-700 flex-1 md:flex-none">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-800 shadow-2xl mb-10 w-full">
                <div class="flex flex-col gap-4 md:gap-6 md:flex-row md:items-start">
                    <div class="flex-1 w-full">
                        <label class="block text-xs uppercase tracking-widest text-slate-400 mb-2">Add names (comma or new line separated)</label>
                        <div class="flex flex-col gap-2 md:flex-row md:gap-2">
                            <textarea id="nameInput" rows="3" class="flex-1 p-3 md:p-3 rounded-lg md:rounded-xl bg-slate-800 border border-slate-700 focus:ring-2 focus:ring-amber-400 focus:outline-none text-sm md:text-base" placeholder="Type names here..."></textarea>
                            <button type="button" onclick="addName()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-black text-sm md:text-base transition-all shadow-lg h-fit md:h-auto">
                                Add Names
                            </button>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" onclick="spinWheel()" class="bg-indigo-600 hover:bg-indigo-500 px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-bold text-xs md:text-base transition-all shadow-lg">
                                Spin
                            </button>
                            <label class="flex items-center gap-2 text-xs md:text-sm text-slate-300 bg-slate-800 border border-slate-700 px-3 md:px-4 py-2 md:py-2 rounded-lg md:rounded-xl">
                                <input id="removeAfterSpin" type="checkbox" class="accent-rose-500" />
                                <span class="hidden sm:inline">Remove selected after spin</span>
                                <span class="sm:hidden">Auto-remove</span>
                            </label>
                            <button type="button" onclick="resetNames()" class="text-rose-300 hover:text-white flex items-center justify-center gap-1 md:gap-2 px-3 md:px-4 py-2 rounded-lg md:rounded-xl border border-rose-900/30 transition hover:bg-rose-900/20 text-xs md:text-sm font-bold">
                                🧹 <span class="hidden sm:inline">Reset</span>
                            </button>
                            <button type="button" onclick="showNovaCards()" class="text-slate-200 hover:text-white flex items-center justify-center gap-1 md:gap-2 px-3 md:px-4 py-2 rounded-lg md:rounded-xl border border-slate-700 transition hover:bg-slate-800 text-xs md:text-sm font-bold">
                                ↩️ <span class="hidden sm:inline">Back</span>
                            </button>
                        </div>
                    </div>

                    <div class="relative flex flex-col items-center gap-4 w-full md:w-auto md:flex-shrink-0">
                        <div class="roulette-wheel bg-slate-200" id="rouletteWheel">
                            <span class="roulette-pointer"></span>
                        </div>
                        <div class="text-center">
                            <p class="text-xs uppercase tracking-widest text-slate-500">Selected</p>
                            <p id="selectedName" class="text-lg md:text-xl font-black text-amber-300">—</p>
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
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showNovaCards() {
            rouletteView.classList.add('hidden');
            novacardsView.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
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
            const allCards = document.querySelectorAll('.card-container[data-taken="false"]');

            if (allCards.length === 0) {
                alert("No available cards to select! Unlock cards or add new ones.");
                return;
            }

            document.querySelectorAll('.card-inner.flipped').forEach(card => {
                card.classList.remove('flipped');
            });

            const randomIndex = Math.floor(Math.random() * allCards.length);
            const selectedContainer = allCards[randomIndex];
            const innerCard = selectedContainer.querySelector('.card-inner');

            selectedContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

            setTimeout(() => {
                innerCard.classList.add('flipped');
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