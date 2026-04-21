import type { OperationChatMessage, OperationUnreadChatItem } from '@/types/Chat';
import axios from 'axios';
import { defineStore } from 'pinia';
import { route } from 'ziggy-js';

type OperationMessageListener = (message: OperationChatMessage) => void;

const operationListeners = new Map<number, Set<OperationMessageListener>>();
const subscribedOperationChannels = new Set<number>();
let initializedUserId: number | null = null;

export const useChatStore = defineStore('chat', {
    state: () => ({
        unreadItems: [] as OperationUnreadChatItem[],
        loadingUnread: false,
        realtimeInitialized: false,
    }),
    getters: {
        unreadTotal: (state) => state.unreadItems.reduce((carry, item) => carry + item.unread_count, 0),
    },
    actions: {
        async fetchUnread() {
            this.loadingUnread = true;

            try {
                const response = await axios.get(route('chat.unread-by-operation'));
                this.unreadItems = response.data.items ?? [];
            } finally {
                this.loadingUnread = false;
            }
        },
        removeUnreadByOperation(operationId: number) {
            this.unreadItems = this.unreadItems.filter((item) => item.operation_id !== operationId);
        },
        initializeRealtime(userId: number) {
            if (!window.Echo || this.realtimeInitialized || initializedUserId === userId) {
                return;
            }

            initializedUserId = userId;
            this.realtimeInitialized = true;

            window.Echo.private(`App.Models.User.${userId}`).listen('.operation.chat.unread.updated', async () => {
                await this.fetchUnread();
            });
        },
        teardownRealtime() {
            if (!window.Echo || initializedUserId === null) {
                return;
            }

            window.Echo.leave(`App.Models.User.${initializedUserId}`);
            initializedUserId = null;
            this.realtimeInitialized = false;
        },
        watchOperationChannel(operationId: number, listener: OperationMessageListener): () => void {
            if (!window.Echo) {
                return () => {};
            }

            if (!operationListeners.has(operationId)) {
                operationListeners.set(operationId, new Set());
            }

            const listeners = operationListeners.get(operationId);
            listeners?.add(listener);

            if (!subscribedOperationChannels.has(operationId)) {
                subscribedOperationChannels.add(operationId);

                window.Echo.private(`operations.chat.${operationId}`).listen(
                    '.operation.chat.message.sent',
                    async (event: { message?: OperationChatMessage }) => {
                        if (!event?.message) {
                            return;
                        }

                        const channelListeners = operationListeners.get(operationId);
                        channelListeners?.forEach((callback) => callback(event.message!));
                    },
                );
            }

            return () => {
                const callbacks = operationListeners.get(operationId);
                callbacks?.delete(listener);

                if (callbacks && callbacks.size === 0) {
                    operationListeners.delete(operationId);
                    subscribedOperationChannels.delete(operationId);

                    if (window.Echo) {
                        window.Echo.leave(`operations.chat.${operationId}`);
                    }
                }
            };
        },
    },
});

