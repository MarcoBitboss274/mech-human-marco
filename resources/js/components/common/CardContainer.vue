<template>
    <div class="card">
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
type Props = {
    title?: string;
};

defineProps<Props>();
</script>

<style>
@reference '@/../css/base.css';
.card {
    @apply overflow-auto rounded-[var(--bb-radius)] border border-[var(--bb-border)] bg-[var(--bb-panel)] shadow-md transition-colors duration-1000;
}
</style>
