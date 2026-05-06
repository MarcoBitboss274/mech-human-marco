<script setup lang="ts">
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import { User, UserForm } from '@/types/User';
import { useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbSelect, BbSwitch, BbTextInput } from 'bitboss-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Defaults = {
    role?: string | null;
    supplier_id?: number | null;
    locked?: boolean;
};

type Props = {
    user: User | null;
    defaults?: Defaults | null;
};

const props = defineProps<Props>();
const emit = defineEmits(['data:updated']);

const isCreating = computed(() => !props.user?.id);
const supplierLocked = computed(() => Boolean(isCreating.value && props.defaults?.locked && props.defaults?.supplier_id));
const roleLocked = computed(() => Boolean(isCreating.value && props.defaults?.locked && props.defaults?.role));

const { select: selectRoles } = useSelect('roles');
const { select: selectBuildings } = useSelect('buildings');
const { select: selectBuildingUserRoles } = useSelect('building-user-roles');
const { select: selectSuppliers } = useSelect('suppliers');
const { select: selectSupplierUserRoles } = useSelect('supplier-user-roles');

const form = useForm<UserForm>({
    id: null,
    name: null,
    surname: null,
    email: null,
    role: null,
    active: true,
    send_invite: false,
    password: null,
    password_confirmation: null,
    odontoiatra: false,
    odontotecnico: false,
    roll_number: null,
    roll_province: null,
    building_relations: [],
    managed_building_ids: [],
    supplier_relation: null,
    verify_email: false,
});

const prefill = () => {
    form.id = props.user?.id ?? null;
    form.name = props.user?.name ?? null;
    form.surname = props.user?.surname ?? null;
    form.email = props.user?.email ?? null;
    form.role = props.user?.role ?? null;
    form.active = props.user?.active ?? true;
    form.odontoiatra = props.user?.odontoiatra ?? false;
    form.odontotecnico = props.user?.odontotecnico ?? false;
    form.roll_number = props.user?.roll_number ?? null;
    form.roll_province = props.user?.roll_province ?? null;
    form.building_relations = (props.user?.buildings ?? []).map((b) => ({
        building_id: b.id,
        building_label: b.name ?? String(b.id),
        role: b.pivot?.role ?? 'member',
    }));
    form.managed_building_ids = (props.user?.managed_buildings ?? []).map((b) => b.id);

    const supplierAssoc = (props.user?.suppliers ?? [])[0] ?? null;
    if (supplierAssoc) {
        form.supplier_relation = {
            supplier_id: supplierAssoc.id,
            role: supplierAssoc.pivot?.role ?? 'member',
        };
    } else if (isCreating.value && props.defaults?.role === 'supplier' && props.defaults?.supplier_id) {
        form.supplier_relation = {
            supplier_id: props.defaults.supplier_id,
            role: 'member',
        };
        form.role = 'supplier';
    } else {
        form.supplier_relation = null;
    }

    form.verify_email = props.user?.email_verified_at ? true : false;
};

const needType = computed(() => form.role === 'customer');
const needSupplier = computed(() => form.role === 'supplier');
const needRoll = computed(() => form.odontoiatra);
const needManagedBuildings = computed(() => form.role === 'agent');

const supplierIdModel = computed<number | null>({
    get: () => form.supplier_relation?.supplier_id ?? null,
    set: (v) => {
        const current = form.supplier_relation ?? { supplier_id: null, role: null };
        form.supplier_relation = { ...current, supplier_id: v };
    },
});

const supplierRoleModel = computed<string | null>({
    get: () => form.supplier_relation?.role ?? null,
    set: (v) => {
        const current = form.supplier_relation ?? { supplier_id: null, role: null };
        form.supplier_relation = { ...current, role: v };
    },
});

watch(needSupplier, (val) => {
    if (!val) {
        form.supplier_relation = null;
    } else if (form.supplier_relation === null) {
        form.supplier_relation = { supplier_id: null, role: null };
    }
});

const loadSuppliersFilter = (query: string, prefill: boolean, modelValue: unknown) => {
    const mv = (modelValue ?? supplierIdModel.value) as number | string | string[] | number[] | null | undefined;
    return selectSuppliers(query || null, prefill, mv ?? null);
};

const excludeBuildingIds = computed(() => form.building_relations.map((r) => r.building_id).filter(Boolean));

const loadBuildings = async (search?: string) =>
    selectBuildings(search ?? null, false, null, {
        exclude_ids: excludeBuildingIds.value,
    });

const newBuildingId = ref<number | null>(null);
const newRole = ref<string | null>(null);
const newManagedBuildingId = ref<number | null>(null);

const addRelation = async () => {
    if (!newBuildingId.value || !newRole.value) return;
    const data = await selectBuildings(null, true, newBuildingId.value);
    const label = Array.isArray(data) && data[0] ? data[0].label : String(newBuildingId.value);
    form.building_relations = [
        ...form.building_relations,
        {
            building_id: newBuildingId.value,
            building_label: label,
            role: newRole.value,
        },
    ];
    newBuildingId.value = null;
    newRole.value = null;
};

const excludeManagedBuildingIds = computed(() => (form.managed_building_ids ?? []).filter(Boolean));

const loadManagedBuildings = async (search?: string) =>
    selectBuildings(search ?? null, false, null, {
        exclude_ids: excludeManagedBuildingIds.value,
    });

const addManagedBuilding = () => {
    if (!newManagedBuildingId.value) return;
    const current = form.managed_building_ids ?? [];
    form.managed_building_ids = Array.from(new Set([...current, newManagedBuildingId.value]));
    newManagedBuildingId.value = null;
};

const removeManagedBuilding = (id: number) => {
    form.managed_building_ids = (form.managed_building_ids ?? []).filter((x) => x !== id);
};

const managedBuildingLabels = ref<Record<number, string>>({});

const hydrateManagedBuildingLabels = async () => {
    const ids = (form.managed_building_ids ?? []).filter(Boolean);
    if (!ids.length) {
        managedBuildingLabels.value = {};
        return;
    }
    const data = await selectBuildings(null, true, ids);
    const map: Record<number, string> = {};
    if (Array.isArray(data)) {
        for (const item of data) {
            const value = typeof item.value === 'number' ? item.value : Number(item.value);
            if (!Number.isNaN(value)) map[value] = item.label ?? String(value);
        }
    }
    managedBuildingLabels.value = map;
};

const removeRelation = (index: number) => {
    form.building_relations = form.building_relations.filter((_, i) => i !== index);
};

const updateRelationRole = (index: number, role: string) => {
    const updated = [...form.building_relations];
    updated[index] = { ...updated[index], role };
    form.building_relations = updated;
};

watch(
    () => props.user,
    () => {
        form.reset();
        form.clearErrors();
        prefill();
        hydrateManagedBuildingLabels();
    },
    { immediate: true },
);

watch(
    () => form.managed_building_ids,
    () => {
        hydrateManagedBuildingLabels();
    },
    { deep: true },
);

const { execute } = useAsyncFn(
    async () => {
        const url = isCreating.value ? route('users.store') : route('users.update', { user: props.user?.id });
        const method = isCreating.value ? 'post' : 'put';
        await form.submit(method, url, {
            onSuccess: () => {
                form.reset();
                setTimeout(() => {
                    emit('data:updated');
                }, 10);
            },
        });
    },
    { immediate: false },
);
</script>

<template>
    <form class="admin-form" autocomplete="off">
        <div class="admin-form__grid">
            <BbTextInput v-model="form.name" autocomplete="off" :label="t('Nome')" required :errors="form.errors?.name" />
            <BbTextInput v-model="form.surname" autocomplete="off" :label="t('Cognome')" required :errors="form.errors?.surname" />
            <BbTextInput v-model="form.email" autocomplete="off" :label="t('Email')" required type="email" :errors="form.errors?.email" />
            <BbSelect v-model="form.role" item-text="label" item-value="value" :items="selectRoles" :label="t('Ruolo')" :disabled="roleLocked" :errors="form.errors?.role" />
            <BbSwitch v-model="form.active" :label="t('Attivo')" />
            <BbSwitch v-model="form.verify_email" :label="t('Email verificata')" />
            <BbCheckbox v-if="isCreating" :label="t('Invia email di benvenuto')" v-model="form.send_invite" />

            <fieldset v-if="needType">
                <legend>{{ t('Tipo utente') }}</legend>
                <BbCheckbox v-model="form.odontoiatra" :label="t('Odontoiatra')" />
                <BbCheckbox v-model="form.odontotecnico" :label="t('Odontotecnico')" />
                <div v-if="needRoll" class="grid gap-2 lg:col-span-2 lg:grid-cols-2">
                    <BbTextInput
                        v-model="form.roll_number"
                        autocomplete="off"
                        :label="t('Numero albo')"
                        required
                        :errors="form.errors?.roll_number"
                    />
                    <BbTextInput
                        v-model="form.roll_province"
                        autocomplete="off"
                        :label="t('Provincia albo')"
                        required
                        :errors="form.errors?.roll_province"
                    />
                </div>
            </fieldset>

            <fieldset v-if="needType" class="lg:col-span-2">
                <legend>{{ t('Relazioni strutture') }}</legend>
                <div class="space-y-3">
                    <div v-for="(rel, index) in form.building_relations" :key="`${rel.building_id}-${index}`" class="flex flex-wrap items-end gap-2">
                        <BbTextInput :model-value="rel.building_label" :label="t('Struttura')" class="min-w-0 flex-1" disabled />
                        <BbSelect
                            :model-value="rel.role"
                            item-text="label"
                            item-value="value"
                            :items="selectBuildingUserRoles"
                            :label="t('Ruolo')"
                            class="w-36"
                            @update:model-value="(v: string) => updateRelationRole(index, v)"
                        />
                        <BbButton icon="trash" size="xs" variant="ghost" @click="removeRelation(index)">
                            {{ t('Rimuovi') }}
                        </BbButton>
                    </div>
                    <div class="flex flex-wrap items-end gap-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                        <BbSelect
                            v-model="newBuildingId"
                            item-text="label"
                            item-value="value"
                            :items="loadBuildings"
                            :label="t('Aggiungi struttura')"
                            class="min-w-0 flex-1"
                        />
                        <BbSelect
                            v-model="newRole"
                            item-text="label"
                            item-value="value"
                            :items="selectBuildingUserRoles"
                            :label="t('Ruolo')"
                            class="w-36"
                        />
                        <BbButton size="xs" @click="addRelation">{{ t('Aggiungi') }}</BbButton>
                    </div>
                </div>
                <p v-if="form.errors?.building_relations" class="mt-1 text-sm text-red-600">
                    {{ form.errors.building_relations }}
                </p>
            </fieldset>

            <fieldset v-if="needSupplier" class="lg:col-span-2">
                <legend>{{ t('Fornitore associato') }}</legend>
                <div class="flex flex-wrap items-end gap-2">
                    <BbSelect
                        v-model="supplierIdModel"
                        item-text="label"
                        item-value="value"
                        :items="loadSuppliersFilter"
                        :label="t('Fornitore')"
                        class="min-w-0 flex-1"
                        :disabled="supplierLocked"
                        :errors="form.errors?.['supplier_relation.supplier_id']"
                    />
                    <BbSelect
                        v-model="supplierRoleModel"
                        item-text="label"
                        item-value="value"
                        :items="selectSupplierUserRoles"
                        :label="t('Ruolo nel team')"
                        class="w-48"
                        :errors="form.errors?.['supplier_relation.role']"
                    />
                </div>
                <p v-if="form.errors?.supplier_relation" class="mt-1 text-sm text-red-600">
                    {{ form.errors.supplier_relation }}
                </p>
            </fieldset>

            <fieldset v-if="needManagedBuildings" class="lg:col-span-2">
                <legend>{{ t('Strutture gestite') }}</legend>
                <div class="space-y-3">
                    <div v-if="(form.managed_building_ids ?? []).length" class="space-y-2">
                        <div v-for="id in form.managed_building_ids" :key="id" class="flex flex-wrap items-end gap-2">
                            <BbTextInput
                                :model-value="managedBuildingLabels[id] ?? String(id)"
                                :label="t('Struttura')"
                                class="min-w-0 flex-1"
                                disabled
                            />
                            <BbButton icon="trash" size="xs" variant="ghost" @click="removeManagedBuilding(id)">
                                {{ t('Rimuovi') }}
                            </BbButton>
                        </div>
                    </div>

                    <div v-else class="text-sm text-gray-500">
                        {{ t('Nessuna struttura assegnata') }}
                    </div>

                    <div class="flex flex-wrap items-end gap-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                        <BbSelect
                            v-model="newManagedBuildingId"
                            item-text="label"
                            item-value="value"
                            :items="loadManagedBuildings"
                            :label="t('Aggiungi struttura')"
                            class="min-w-0 flex-1"
                        />
                        <BbButton size="xs" @click="addManagedBuilding">{{ t('Aggiungi') }}</BbButton>
                    </div>
                </div>
                <p v-if="form.errors?.managed_building_ids" class="mt-1 text-sm text-red-600">
                    {{ form.errors.managed_building_ids }}
                </p>
            </fieldset>

            <fieldset v-if="!isCreating">
                <legend>{{ t('Cambio password') }}</legend>
                <form autocomplete="off">
                    <BbTextInput
                        v-model="form.password"
                        autocomplete="false"
                        :label="t('Password')"
                        required
                        type="password"
                        :errors="form.errors?.password"
                    />
                    <BbTextInput
                        v-model="form.password_confirmation"
                        autocomplete="false"
                        :label="t('Conferma password')"
                        required
                        type="password"
                        :errors="form.errors?.password_confirmation"
                    />
                </form>
            </fieldset>
        </div>
        <div class="admin-form__actions">
            <BbButton :loading="form.processing" @click="execute">{{ t('Salva') }}</BbButton>
        </div>
    </form>
</template>
