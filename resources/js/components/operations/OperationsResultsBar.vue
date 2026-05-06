<template>
    <div class="operations-results-bar">
        <p class="operations-results-bar__label">{{ resultsLabel }}</p>
        <div v-if="$slots.actions" class="operations-results-bar__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
type Props = {
    resultsLabel: string;
};

defineProps<Props>();
</script>

<style scoped>
.operations-results-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: flex-start;
    gap: 12px;
    padding: 8px 0;
    margin-bottom: 8px;
}

.operations-results-bar__label {
    font-size: 14px;
    color: var(--bb-text-muted, #555);
    margin: 0;
}

.operations-results-bar__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
