<?php

// Enhanced Roulette Spinner Animations

// Keyframe animations for spinning, bouncing, and particle effects
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-30px); }
    60% { transform: translateY(-15px); }
}

@keyframes particle {
    0% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(2); }
}

function startRoulette() {
    const spinner = document.getElementById('roulette-spinner');
    spinner.style.animation = 'spin 4s ease-in-out';

    // Particle effect for confetti
    for (let i = 0; i < 100; i++) {
        createParticle();
    }
}

function createParticle() {
    const particle = document.createElement('div');
    particle.className = 'particle';
    document.body.appendChild(particle);
    // Add animation and effect logic here
    particle.style.animation = 'particle 1s forwards';
}

// Improved animations when selecting names
function highlightSelectedName(nameElement) {
    nameElement.style.animation = 'bounce 0.5s ease';
    nameElement.classList.add('highlight');
}

// Additional styling for confetti and glow effects
const styles = `
.particle {
    position: absolute;
    border-radius: 50%;
    background-color: rgba(255, 223, 0, 0.7);
    width: 10px;
    height: 10px;
}

.highlight {
    background-color: rgba(255, 255, 0, 1);
    box-shadow: 0 0 10px rgba(255, 255, 0, 1);
}
`;

const styleSheet = document.createElement('style');
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);
