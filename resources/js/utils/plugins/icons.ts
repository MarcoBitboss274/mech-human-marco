// iconPlugin.ts
import { mapKeys } from '@/utils/functions/mapKeys';
import type { App } from 'vue';

export default {
    install: (app: App) => {
        /**
         * Cast imported svg to string
         */
        const modules = import.meta.glob('../../assets/icons/**', {
            query: '?raw',
            import: 'default',
        }) as unknown as Record<string, () => Promise<string>>;
        // Get the name of the icon without extension

        const getModuleName = (_: any, key: string) => {
            const path = key.split(/[/.]+/g);
            return path[path.length - 2];
        };
        // Makes a map of all the icons indexed by name without extension
        const icons = mapKeys(modules, getModuleName);
        app.provide('icons', icons);
    },
};
