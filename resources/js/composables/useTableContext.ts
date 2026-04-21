import { injectLocal, provideLocal } from '@vueuse/core';
import { type InjectionKey, reactive } from 'vue';

const injectionKey = Symbol('tableContext');

/**
 * Provides a context for a page where paginated data is present.
 * It allows tracking selected, unselected, and select all items in a typesafe way.
 * So you can update the current page data in nested component without resorting to prop drilling.
 * If no key is provided, it will use a default symbol. If you have  multiple contexts on a page you can define a specific key to use.
 */
type TableContext<U = any> = {
    all: boolean;
    selected: U[];
    unselected: U[];
    resetSelection: () => void;
};

export const useTableContext = <U>(symbolOrKey: InjectionKey<TableContext<U>> | string = injectionKey) => {
    const existingContext = injectLocal(symbolOrKey, null);

    if (existingContext) {
        return existingContext;
    }

    const resetSelection = () => {
        context.selected = [];
        context.unselected = [];
        context.all = false;
    };

    const context: TableContext<U> = reactive({
        all: false,
        selected: [],
        unselected: [],
        resetSelection,
    });
    provideLocal(symbolOrKey, context);

    return injectLocal<TableContext<U>>(symbolOrKey)!;
};
