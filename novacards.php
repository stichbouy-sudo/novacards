<?php
// novacards.php

// Function to implement the roulette wheel spinning animation
function spinRouletteWheel() {
    // Define the animation using CSS
    echo '<style>
';
    echo '  .roulette { animation: spin 6s cubic-bezier(0.25, 1, 0.5, 1); }
';
    echo '  @keyframes spin {
';
    echo '    0% { transform: rotate(0deg); }
';
    echo '    50% { transform: rotate(720deg); }
';  
    echo '    100% { transform: rotate(0deg); }
';
    echo '  }
';
    echo '</style>';

    // Start the animation
    echo '<div class="roulette">Roulette Wheel</div>';
}

// Call the function
spinRouletteWheel();
?>
