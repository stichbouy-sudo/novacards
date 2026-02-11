<style>
  .roulette {
    transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
  }
</style>

<div class="roulette">
  <p>Name Roulette Wheel</p>
</div>
<script>
  function spinRoulette() {
    const wheel = document.querySelector('.roulette');
    wheel.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
  }
</script>