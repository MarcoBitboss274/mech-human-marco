<script setup lang="ts">
type ActorPerson = {
    name: string | null;
    email: string | null;
};

type Props = {
    role: string;
    person?: ActorPerson | null;
    placeholder?: string;
};

withDefaults(defineProps<Props>(), {
    person: null,
    placeholder: '--',
});
</script>

<template>
    <article class="operations-overview__actor">
        <p class="operations-overview__actor-role">{{ role }}</p>
        <template v-if="person">
            <p class="operations-overview__actor-name">{{ person.name ?? placeholder }}</p>
            <p v-if="person.email" class="operations-overview__actor-email">{{ person.email }}</p>
        </template>
        <p v-else class="operations-overview__actor-empty">{{ placeholder }}</p>
    </article>
</template>

<style>
@reference '@/../css/base.css';

.operations-overview__actor {
    @apply rounded-lg border border-gray-200 bg-white p-4;
}

.operations-overview__actor-role {
    @apply text-xs font-medium uppercase tracking-wide text-gray-500;
}

.operations-overview__actor-name {
    @apply mt-2 text-base font-semibold text-gray-900;
}

.operations-overview__actor-email {
    @apply mt-1 text-sm text-gray-600 break-all;
}

.operations-overview__actor-empty {
    @apply mt-2 text-sm italic text-gray-400;
}
</style>
