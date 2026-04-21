<template>
    <div class="blur-tiles">
        <div class="blur-tiles__container">
            <span v-for="i in 200" :key="i" class="blur-tiles__tile"></span>
        </div>
        <div class="blur-tiles__content">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useMounted } from '@vueuse/core';
import { onBeforeUnmount, onMounted } from 'vue';

let container: any = null;

let mouseCoords = { x: -300, y: -300 };

const mounted = useMounted();

const onMouseMove = (e: MouseEvent) => {
    mouseCoords = { x: e.clientX, y: e.clientY };
};

const animateCircle = () => {
    if (!mounted.value) return;
    if (!container) {
        container = document.querySelector('.blur-tiles__container');
    }
    container.style.setProperty('--circle-x', `${mouseCoords.x}px`);
    container.style.setProperty('--circle-y', `${mouseCoords.y}px`);
    requestAnimationFrame(() => animateCircle());
};

onMounted(() => {
    window.addEventListener('mousemove', onMouseMove, { passive: true });
    animateCircle();
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onMouseMove);
});
</script>

<style>
@reference '@/../css/base.css';
.blur-tiles {
    --tile-size: 3cm;
    --circle-size: 120px;
    --circle-x: 0px;
    --circle-y: 0px;
    --offset-x: calc((var(--tile-size) / 2));
    @apply grid;

    .blur-tiles__container {
        @apply pointer-events-none absolute inset-0 top-0 col-span-full col-start-1 row-span-full row-start-1 grid overflow-hidden bg-[var(--bb-panel)] p-4 select-none;
        grid-template-columns: repeat(25, auto);
        grid-template-rows: repeat(25, auto);
        gap: 20px 20px;
        .blur-tiles__tile {
            @apply h-[var(--tile-size)] w-[var(--tile-size)] border;
            margin-left: calc(var(--tile-size) * -1 / 2);
            margin-top: calc(var(--tile-size) * -1 / 2);
            margin-bottom: calc(var(--tile-size) / 2);
            margin-right: calc(var(--tile-size) / 2);
            /* From https://css.glass */
            border-radius: 16px;
            backdrop-filter: blur(10.5px);
            -webkit-backdrop-filter: blur(10.5px);
        }

        &::before {
            @apply absolute hidden h-[var(--circle-size)] w-[var(--circle-size)] transform-gpu rounded-full bg-[var(--bb-primary)] select-none md:block;
            backface-visibility: hidden;
            transform: translate(calc(var(--circle-x) - 50%), calc(var(--circle-y) - 50%));
            content: '';
        }
    }

    .blur-tiles__content {
        @apply isolate col-span-full col-start-1 row-span-full row-start-1 grid px-[var(--min-px)];
    }
}
</style>
