<script setup lang="ts">
import { useChatStore } from '@/stores/chat';
import type { OperationChatMessage } from '@/types/Chat';
import axios from 'axios';
import { BbButton, BbOffCanvas } from 'bitboss-ui';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type Props = {
    title?: string;
    operationId: number;
    currentUserId: number;
    canRead?: boolean;
    canSend?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Chat lavorazione',
    canRead: false,
    canSend: false,
});

const modelValue = defineModel<boolean>('modelValue', {
    required: true,
    default: false,
});

const messagesContainer = ref<HTMLElement | null>(null);
const draftMessage = ref('');
const messages = ref<OperationChatMessage[]>([]);
const loadingMessages = ref(false);
const sendingMessage = ref(false);
const chatStore = useChatStore();
const hasInitialized = ref(false);
const stopOperationWatch = ref<(() => void) | null>(null);

const scrollToBottom = async () => {
    await nextTick();
    if (!messagesContainer.value) {
        return;
    }

    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
};

const formatTime = (value: string) => {
    if (!value) {
        return '--:--';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '--:--';
    }

    return date.toLocaleTimeString('it-IT', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const appendMessage = (message: OperationChatMessage) => {
    if (messages.value.some((item) => item.id === message.id)) {
        return;
    }

    messages.value.push(message);
    messages.value = messages.value.sort((left, right) => left.id - right.id);
};

const loadMessages = async () => {
    if (!props.canRead) {
        messages.value = [];
        return;
    }

    loadingMessages.value = true;
    try {
        const response = await axios.get(route('operations.chat.messages', props.operationId), {
            params: {
                limit: 100,
            },
        });
        messages.value = response.data.messages ?? [];
    } finally {
        loadingMessages.value = false;
    }
};

const markAsRead = async () => {
    if (!props.canRead) {
        return;
    }

    await axios.post(route('operations.chat.read', props.operationId));
    chatStore.removeUnreadByOperation(props.operationId);
};

const sendMessage = async () => {
    if (!props.canSend) {
        return;
    }

    const trimmedMessage = draftMessage.value.trim();
    if (!trimmedMessage) {
        return;
    }

    sendingMessage.value = true;
    try {
        const response = await axios.post(route('operations.chat.store', props.operationId), {
            body: trimmedMessage,
        });
        appendMessage(response.data.message);
        draftMessage.value = '';
        await markAsRead();
        await chatStore.fetchUnread();
        await scrollToBottom();
    } finally {
        sendingMessage.value = false;
    }
};

const registerOperationRealtime = () => {
    if (!props.canRead) {
        return;
    }

    stopOperationWatch.value = chatStore.watchOperationChannel(props.operationId, async (message) => {
        appendMessage(message);

        if (modelValue.value) {
            await markAsRead();
        }

        await chatStore.fetchUnread();
        if (modelValue.value) {
            await scrollToBottom();
        }
    });
};

const onComposerKeydown = async (event: KeyboardEvent) => {
    if (event.key !== 'Enter' || event.shiftKey) {
        return;
    }

    event.preventDefault();
    await sendMessage();
};

watch(
    () => modelValue.value,
    async (isOpen) => {
        if (isOpen) {
            if (!hasInitialized.value) {
                await loadMessages();
                hasInitialized.value = true;
            }
            await markAsRead();
            await chatStore.fetchUnread();
            await scrollToBottom();
        }
    },
);

onMounted(async () => {
    registerOperationRealtime();

    if (!props.canRead) {
        return;
    }

    if (modelValue.value || !hasInitialized.value) {
        await loadMessages();
        hasInitialized.value = true;
    }

    if (modelValue.value) {
        await markAsRead();
        await chatStore.fetchUnread();
        await scrollToBottom();
    }
});

onBeforeUnmount(() => {
    stopOperationWatch.value?.();
});
</script>

<template>
    <BbOffCanvas v-model="modelValue" direction="right" :title="props.title" overlay-classes="chat-slider-offcanvas">
        <div class="chat-slider">
            <div v-if="!props.canRead" class="chat-slider__empty">Non hai i permessi per visualizzare questa chat.</div>

            <div v-else ref="messagesContainer" class="chat-slider__messages">
                <div v-if="loadingMessages" class="chat-slider__empty">Caricamento messaggi...</div>
                <div v-else-if="messages.length === 0" class="chat-slider__empty">Nessun messaggio per questa lavorazione.</div>
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="chat-slider__row"
                    :class="message.user?.id === props.currentUserId ? 'is-sent' : 'is-received'"
                >
                    <div class="chat-slider__bubble">
                        <p class="chat-slider__text">
                            {{ message.body }}
                        </p>
                        <span class="chat-slider__time">{{ formatTime(message.created_at) }}</span>
                    </div>
                </div>
            </div>

            <div class="chat-slider__composer" v-if="props.canRead">
                <form class="chat-slider__composer-form" @submit.prevent="sendMessage">
                    <textarea
                        v-model="draftMessage"
                        rows="1"
                        class="chat-slider__input"
                        placeholder="Scrivi un messaggio..."
                        :disabled="!props.canSend || sendingMessage"
                        @keydown="onComposerKeydown"
                    />
                    <BbButton type="submit" size="sm" :disabled="!props.canSend" :loading="sendingMessage">Invia</BbButton>
                </form>
                <p v-if="!props.canSend" class="chat-slider__send-disabled">Puoi leggere i messaggi ma non inviarne di nuovi.</p>
            </div>
        </div>
    </BbOffCanvas>
</template>

<style>
@reference '@/../css/base.css';

.chat-slider-offcanvas {
    .bb-offcanvas__body {
        @apply flex h-full p-0;
    }
}

.chat-slider {
    @apply flex min-h-0 flex-1 flex-col bg-slate-50;
}

.chat-slider__messages {
    @apply flex-1 space-y-3 overflow-y-auto p-4;
}

.chat-slider__empty {
    @apply p-4 text-sm text-slate-500;
}

.chat-slider__row {
    @apply flex;
}

.chat-slider__row.is-sent {
    @apply justify-end;
}

.chat-slider__row.is-received {
    @apply justify-start;
}

.chat-slider__bubble {
    @apply max-w-[80%] rounded-2xl px-3 py-2 shadow-sm;
}

.chat-slider__row.is-sent .chat-slider__bubble {
    @apply bg-indigo-600 text-white;
}

.chat-slider__row.is-received .chat-slider__bubble {
    @apply bg-white text-slate-900 ring-1 ring-slate-200;
}

.chat-slider__text {
    @apply text-sm leading-5 break-words whitespace-pre-wrap;
}

.chat-slider__time {
    @apply mt-1 block text-right text-[11px] opacity-80;
}

.chat-slider__composer {
    @apply mt-auto border-t border-slate-200 bg-white p-3;
}

.chat-slider__composer-form {
    @apply flex items-end gap-2;
}

.chat-slider__input {
    @apply max-h-32 min-h-[42px] flex-1 resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 transition outline-none focus:border-indigo-400;
}

.chat-slider__send-disabled {
    @apply mt-2 text-xs text-slate-500;
}
</style>
