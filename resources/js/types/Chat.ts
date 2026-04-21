export type ChatMessageUser = {
    id: number;
    name: string | null;
    surname: string | null;
};

export type OperationChatMessage = {
    id: number;
    operation_id: number;
    user_id?: number;
    body: string;
    created_at: string;
    user: ChatMessageUser | null;
};

export type OperationUnreadChatItem = {
    operation_id: number;
    batch_number: string | null;
    typology: string | null;
    unread_count: number;
};

