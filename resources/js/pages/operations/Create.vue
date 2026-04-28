<template>
    <div class="admin-view">
        <div class="admin-view__header">
            <h1 class="page__title">{{ pageTitle }}</h1>
            <BbButton icon="arrow-left" @click="router.get(route('operations.index'))">{{ t('Torna alla lista') }}</BbButton>
        </div>

        <div class="mx-auto max-w-7xl">
            <XStepper v-model="currentStep" direction="right">
                <template #default="ctx">
                    <div>
                        <XStep>
                            <div class="create-wizard__step">
                                <h2 class="create-wizard__step-title">{{ t('Dati generali prescrizione') }}</h2>
                                <form class="admin-form" autocomplete="off" @submit.prevent>
                                    <div class="admin-form__grid">
                                        <BbSelect
                                            v-model="form.typology"
                                            item-text="label"
                                            item-value="value"
                                            :items="loadTypologies"
                                            :label="t('Tipologia prescrizione')"
                                            :errors="form.errors?.typology"
                                        />
                                        <BbTextInput v-model="form.ref" autocomplete="off" :label="t('Riferimento')" :errors="form.errors?.ref" />
                                        <BbTextInput v-model="form.name" autocomplete="off" :label="t('Nome')" :errors="form.errors?.name" />
                                        <BbTextInput v-model="form.surname" autocomplete="off" :label="t('Cognome')" :errors="form.errors?.surname" />
                                        <BbTextInput
                                            v-model="ageModel"
                                            type="number"
                                            autocomplete="off"
                                            :label="t('Età')"
                                            :errors="form.errors?.age"
                                        />
                                        <BbSelect
                                            v-model="form.gender"
                                            item-text="label"
                                            item-value="value"
                                            :items="loadGenders"
                                            :label="t('Genere')"
                                            :errors="form.errors?.gender"
                                        />
                                        <BbSelect
                                            v-model="form.building_id"
                                            item-text="label"
                                            item-value="value"
                                            :items="loadBuildings"
                                            :label="t('Struttura')"
                                            :errors="form.errors?.building_id"
                                        />
                                        <BbSelect
                                            v-model="form.user_id"
                                            item-text="label"
                                            item-value="value"
                                            :items="loadUsers"
                                            :label="t('Richiedente')"
                                            :errors="form.errors?.user_id"
                                            :disabled="!form.building_id"
                                        />
                                    </div>
                                </form>
                            </div>
                        </XStep>
                        <XStep>
                            <div class="create-wizard__step">
                                <h2 class="create-wizard__step-title">{{ t('Dati di dettaglio') }}</h2>
                                <ProtrusorStep v-if="form.typology === 'protrusor'" :form="form" />
                                <LybraAlignerStep v-else-if="form.typology === 'lybra_aligner'" :form="form" />
                                <GuidedSurgeryStep v-else-if="form.typology === 'guided_surgery'" :form="form" />
                                <ThreeDMeshStep v-else-if="form.typology === '3d_mesh'" :form="form" />
                                <ProsthesisStep v-else-if="form.typology === 'prosthesis'" :form="form" />
                                <SemiFinishedProsthesesStep v-else-if="form.typology === 'semi_finished_prostheses'" :form="form" />
                                <div v-else class="create-wizard__empty-step">
                                    {{ t('Seleziona prima una tipologia per compilare i dettagli') }}
                                </div>
                            </div>
                        </XStep>
                        <XStep>
                            <div class="create-wizard__step">
                                <h2 class="create-wizard__step-title">{{ t('Upload file') }}</h2>
                                <AttachmentsStep :form="form" />
                            </div>
                        </XStep>
                        <XStep>
                            <div class="create-wizard__step">
                                <h2 class="create-wizard__step-title">{{ t('Spedizione e fatturazione') }}</h2>
                                <div class="create-wizard__shipping-billing">
                                    <div class="create-wizard__review-section">
                                        <h3 class="create-wizard__review-section-title">{{ t('Fatturazione') }}</h3>
                                        <dl class="create-wizard__review-grid">
                                            <dt>{{ t('Ragione sociale') }}</dt>
                                            <dd>{{ selectedBuilding?.name ?? '--' }}</dd>
                                            <dt>{{ t('P.IVA') }}</dt>
                                            <dd>{{ selectedBuilding?.vat ?? '--' }}</dd>
                                            <dt>{{ t('Indirizzo sede legale') }}</dt>
                                            <dd>{{ selectedBuilding?.legal_address ?? '--' }}</dd>
                                        </dl>
                                    </div>
                                    <div class="create-wizard__review-section">
                                        <h3 class="create-wizard__review-section-title">{{ t('Indirizzo di spedizione') }}</h3>
                                        <div class="create-wizard__address-mode">
                                            <div
                                                class="create-wizard__address-mode-option"
                                                :class="{ selected: shippingAddressMode === 'existing' }"
                                                role="button"
                                                tabindex="0"
                                                @click="setShippingAddressMode('existing')"
                                                @keydown.enter.prevent="setShippingAddressMode('existing')"
                                            >
                                                {{ t('Usa indirizzo esistente') }}
                                            </div>
                                            <div
                                                class="create-wizard__address-mode-option"
                                                :class="{ selected: shippingAddressMode === 'new' }"
                                                role="button"
                                                tabindex="0"
                                                @click="setShippingAddressMode('new')"
                                                @keydown.enter.prevent="setShippingAddressMode('new')"
                                            >
                                                {{ t('Inserisci un nuovo indirizzo') }}
                                            </div>
                                        </div>

                                        <template v-if="shippingAddressMode === 'existing'">
                                            <div class="admin-form__grid">
                                                <BbSelect
                                                    v-model="selectedShippingAddressId"
                                                    item-text="label"
                                                    item-value="value"
                                                    :items="shippingAddressOptions"
                                                    :label="t('Indirizzo di spedizione')"
                                                    :placeholder="t('Seleziona indirizzo di spedizione')"
                                                    :disabled="shippingAddressOptions.length === 0"
                                                    :hint="
                                                        shippingAddressOptions.length === 0
                                                            ? t('Nessun indirizzo di spedizione disponibile')
                                                            : undefined
                                                    "
                                                    persistent-hint
                                                    :errors="shippingAddressError ?? undefined"
                                                />
                                            </div>
                                            <dl class="create-wizard__review-grid create-wizard__shipping-address-preview">
                                                <dt>{{ t('Indirizzo') }}</dt>
                                                <dd>{{ selectedShippingAddress?.street ?? '--' }}</dd>
                                                <dt>{{ t('Cap') }}</dt>
                                                <dd>{{ selectedShippingAddress?.cap ?? '--' }}</dd>
                                                <dt>{{ t('Città') }}</dt>
                                                <dd>{{ selectedShippingAddress?.city ?? '--' }}</dd>
                                                <dt>{{ t('Provincia') }}</dt>
                                                <dd>{{ selectedShippingAddress?.province ?? '--' }}</dd>
                                            </dl>
                                        </template>

                                        <template v-else>
                                            <div class="admin-form__grid">
                                                <BbTextInput
                                                    v-model="form.address"
                                                    autocomplete="off"
                                                    :label="t('Indirizzo')"
                                                    :errors="form.errors?.address"
                                                />
                                                <BbTextInput v-model="form.city" autocomplete="off" :label="t('Città')" :errors="form.errors?.city" />
                                                <BbTextInput
                                                    v-model="form.province"
                                                    autocomplete="off"
                                                    :label="t('Provincia')"
                                                    :errors="form.errors?.province"
                                                />
                                                <BbTextInput v-model="form.cap" autocomplete="off" :label="t('Cap')" :errors="form.errors?.cap" />
                                            </div>
                                        </template>
                                    </div>
                                    <div class="create-wizard__review-section">
                                        <h3 class="create-wizard__review-section-title">{{ t('Informazioni aggiungtive') }}</h3>
                                        <BbTextarea
                                            v-model="form.notes"
                                            autocomplete="off"
                                            :label="t('Note aggiungtive')"
                                            :errors="form.errors?.notes"
                                        />
                                    </div>
                                </div>
                            </div>
                        </XStep>
                        <XStep>
                            <div class="create-wizard__step">
                                <h2 class="create-wizard__step-title">{{ t('Riepilogo e salvataggio') }}</h2>
                                <div class="create-wizard__review">
                                    <div class="create-wizard__review-section">
                                        <h3 class="create-wizard__review-section-title">{{ t('Prescrizione') }}</h3>
                                        <dl class="create-wizard__review-grid">
                                            <dt>{{ t('Tipologia') }}</dt>
                                            <dd>{{ typologyLabel(form.typology) }}</dd>
                                            <template v-if="form.ref">
                                                <dt>{{ t('Riferimento') }}</dt>
                                                <dd>{{ form.ref }}</dd>
                                            </template>
                                            <template v-if="form.name || form.surname">
                                                <dt>{{ t('Nome e cognome') }}</dt>
                                                <dd>{{ [form.name, form.surname].filter(Boolean).join(' ') }}</dd>
                                            </template>
                                            <template v-if="form.age != null">
                                                <dt>{{ t('Età') }}</dt>
                                                <dd>{{ form.age }}</dd>
                                            </template>
                                            <template v-if="form.gender">
                                                <dt>{{ t('Genere') }}</dt>
                                                <dd>
                                                    <PrescriptionGenderBadge :gender="form.gender" />
                                                </dd>
                                            </template>
                                            <template v-if="form.building_id">
                                                <dt>{{ t('Struttura') }}</dt>
                                                <dd>{{ buildingLabel(form.building_id) }}</dd>
                                            </template>
                                            <template v-if="form.user_id">
                                                <dt>{{ t('Richiedente') }}</dt>
                                                <dd>{{ userLabel(form.user_id) }}</dd>
                                            </template>
                                        </dl>
                                    </div>
                                    <div class="create-wizard__review-section">
                                        <h3 class="create-wizard__review-section-title">{{ t('Lavorazione') }}</h3>
                                        <dl class="create-wizard__review-grid">
                                            <dt>{{ t('Struttura') }}</dt>
                                            <dd>{{ buildingLabel(form.building_id) }}</dd>
                                            <dt>{{ t('Tipologia') }}</dt>
                                            <dd>{{ typologyLabel(form.typology) }}</dd>
                                            <dt>{{ t('Stato') }}</dt>
                                            <dd>{{ t('Bozza') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <div class="admin-form__actions">
                                    <BbButton :loading="form.processing" @click="openSubmitConfirmation">
                                        {{ submitButtonLabel }}
                                    </BbButton>
                                </div>
                            </div>
                        </XStep>
                        <div class="create-wizard__nav">
                            <BbButton v-if="!ctx.isStart" icon="arrow-left" variant="secondary" @click="ctx.previous">
                                {{ t('Indietro') }}
                            </BbButton>
                            <span class="flex-1" />
                            <BbButton variant="secondary" :loading="form.processing" @click="saveDraft">
                                {{ t('Salva bozza') }}
                            </BbButton>
                            <BbButton v-if="!ctx.isEnd" append:icon="arrow-right" @click="onNext(ctx.next)">
                                {{ t('Avanti') }}
                            </BbButton>
                        </div>
                    </div>
                </template>
            </XStepper>
        </div>
        <BbDialog v-model="confirmSubmitModal" size="md" :title="t('Disclaimer Legale e Consenso')">
            <div class="create-wizard__disclaimer-dialog">
                <p class="create-wizard__disclaimer-text">{{ t('Disclaimer legale lavorazione') }}</p>
                <BbCheckbox v-model="legalConsentChecked" :label="t('Dichiaro di aver letto e accettato i termini e le condizioni.')" />
                <div class="create-wizard__disclaimer-actions">
                    <BbButton type="button" variant="outline" @click="closeSubmitConfirmation">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="button" :disabled="!legalConsentChecked || form.processing" @click="confirmAndSubmit">
                        {{ t('Conferma e invia') }}
                    </BbButton>
                </div>
            </div>
        </BbDialog>
        <BbDialog v-model="leaveConfirmModalOpen" size="md" title="Attenzione! Modifiche non salvate">
            <div class="create-wizard__disclaimer-dialog">
                <p class="create-wizard__disclaimer-text">
                    Sono presenti delle modifiche che non sono state salvate. L'uscita dalla procedura comporterà la perdita di tutti i dati inseriti
                    durante l'ultima sessione.
                </p>
                <div class="create-wizard__disclaimer-actions">
                    <BbButton type="button" variant="outline" @click="cancelLeave">
                        {{ t('Annulla') }}
                    </BbButton>
                    <BbButton type="button" @click="confirmLeave">
                        {{ t('Conferma') }}
                    </BbButton>
                </div>
            </div>
        </BbDialog>
    </div>
</template>

<script setup lang="ts">
import XStep from '@/components/common/XStep.vue';
import XStepper from '@/components/common/XStepper.vue';
import AttachmentsStep from '@/components/operations/wizard/prescription-attachments/AttachmentsStep.vue';
import GuidedSurgeryStep from '@/components/operations/wizard/prescription-details/GuidedSurgeryStep.vue';
import LybraAlignerStep from '@/components/operations/wizard/prescription-details/LybraAlignerStep.vue';
import ProsthesisStep from '@/components/operations/wizard/prescription-details/ProsthesisStep.vue';
import ProtrusorStep from '@/components/operations/wizard/prescription-details/ProtrusorStep.vue';
import SemiFinishedProsthesesStep from '@/components/operations/wizard/prescription-details/SemiFinishedProsthesesStep.vue';
import ThreeDMeshStep from '@/components/operations/wizard/prescription-details/ThreeDMeshStep.vue';
import PrescriptionGenderBadge from '@/components/prescriptions/PrescriptionGenderBadge.vue';
import { useAsyncFn } from '@/composables/useAsyncFn';
import { useSelect } from '@/composables/useSelect';
import AppLayout from '@/layouts/AppLayout.vue';
import type { OperationCreateWizardForm } from '@/types/Operation';
import { router, useForm } from '@inertiajs/vue3';
import { BbButton, BbCheckbox, BbDialog, BbSelect, BbTextInput, BbTextarea } from 'bitboss-ui';
import { computed, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => {
        const isEdit = page?.props?.wizard?.mode === 'edit';
        const title = isEdit ? 'Modifica lavorazione con prescrizione' : 'Nuova lavorazione con prescrizione';
        return h(AppLayout, { title }, () => [page]);
    },
});

type Props = {
    wizard: {
        mode: 'create' | 'edit';
        operationId: number | null;
        prescriptionId: number | null;
        prescriptionStatus?: string | null;
        hasActiveRevision?: boolean;
        initialForm: OperationCreateWizardForm | null;
        buildings: WizardBuilding[];
    };
};

type WizardAddress = {
    id: number;
    street: string | null;
    cap: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    is_default: boolean;
};

type WizardBuilding = {
    id: number;
    name: string | null;
    vat: string | null;
    legal_address: string | null;
    addresses: WizardAddress[];
};

const props = defineProps<Props>();
const currentStep = ref(1);
const confirmSubmitModal = ref(false);
const legalConsentChecked = ref(false);
const leaveConfirmModalOpen = ref(false);
const allowNavigation = ref(false);
const pendingNavigation = ref<null | (() => void)>(null);
const isEditMode = computed(() => props.wizard.mode === 'edit' && props.wizard.operationId !== null);
const isRevisionMode = computed(() => isEditMode.value && !!props.wizard.hasActiveRevision);
const pageTitle = computed(() => {
    if (isRevisionMode.value) return t('Revisiona prescrizione');
    return isEditMode.value ? t('Modifica lavorazione') : t('Nuova lavorazione');
});
const submitButtonLabel = computed(() => {
    if (isRevisionMode.value) return t('Invia modifiche');
    return isEditMode.value ? t('Salva lavorazione e prescrizione') : t('Crea lavorazione e prescrizione');
});

const { select: selectBuildings } = useSelect('buildings');
const { select: selectUsers } = useSelect('users');
const { select: selectTypologies } = useSelect('prescription-typologies');
const { select: selectGenders } = useSelect('prescription-genders');

const loadBuildings = async (search?: string) => selectBuildings(search ?? null, false, null);
const loadUsers = async (search?: string) => selectUsers(search ?? null, false, null, form.building_id ? { building_id: form.building_id } : null);
const loadTypologies = async () => selectTypologies(null, false, null);
const loadGenders = async () => selectGenders(null, false, null);

const buildingLabels = shallowRef<Record<number, string>>({});
const typologyLabels = shallowRef<Record<string, string>>({});
const userLabels = shallowRef<Record<number, string>>({});
const selectedShippingAddressId = ref<number | null>(null);
const shippingAddressMode = ref<'existing' | 'new'>('existing');
const shippingAddressError = ref<string | null>(null);

const defaultForm = (): OperationCreateWizardForm => ({
    draft: false,
    submit_revision: false,
    building_id: null,
    user_id: null,
    typology: null,
    ref: null,
    manual: true,
    name: null,
    surname: null,
    age: null,
    gender: null,
    company_name: null,
    address: null,
    city: null,
    province: null,
    cap: null,
    notes: null,
    protrusor_details: {
        protrusor_typology: null,
        odontogram: [],
        jig: null,
        remaining_upper_teeth: null,
        remaining_lower_teeth: null,
        transpalatal_arch: null,
        mandibular_advancement: null,
        mandibular_advancement_2: null,
        note: null,
    },
    lybra_aligner_details: {
        cut_line: null,
        note: null,
    },
    guided_surgery_details: {
        surgery_typology: null,
        odontogram: [],
        desired_implant_line: null,
        additional_info: null,
        note: null,
    },
    three_d_mesh_details: {
        dimension: null,
        odontogram: [],
        outer_finish: null,
        inner_finish: null,
        pattern: null,
        stress_breakers: null,
        '3d_model': null,
        screw_diameter: null,
        shared_project_note: null,
        note: null,
    },
    prosthesis_details: {
        typology: null,
        crowns_and_bridges_details: null,
        full_bridge_details: null,
        odontogram: [],
        '3d_normal_model': null,
        '3d_excellent_model': null,
        note: null,
    },
    semi_finished_prostheses_details: {
        typology: null,
        crowns_and_bridges_details: null,
        full_bridge_details: null,
        odontogram: [],
        note: null,
    },
    protrusor_attachments: {
        scansione_intraorale: null,
        rilevazione_dell_avanzamento_mandibolare_con_occlusione: null,
    },
    lybra_aligner_attachments: {
        scansione_intraorale: null,
        foto_del_sorriso_e_morso_del_paziente: null,
        ortopantomografia: null,
        rx_anteroposteriore_delle_ossa_mascellari: null,
    },
    guided_surgery_attachments: {
        scansione_intraorale: null,
        cbct_allineabile_con_la_scansione_rilevata: null,
        ceratura_diagnostica: null,
    },
    three_d_mesh_attachments: {
        scansione_intraorale: null,
        cbct_allineabile_con_la_scansione_rilevata: null,
        ceratura_diagnostica: null,
        scansione_facciale_o_foto_del_sorriso: null,
    },
    prosthesis_attachments: {
        scansione_intraorale: null,
        articolazione: null,
        foto_con_campione_colore: null,
        foto_del_sorriso: null,
        scansione_e_foto_del_provvisorio: null,
    },
    semi_finished_prostheses_attachments: {
        progetto_in_stl_da_fresare_oppure_scansione_digitale_completa: null,
        scansione_del_provvisorio: null,
    },
});

const resolveInitialForm = (): OperationCreateWizardForm => {
    const initial = props.wizard.initialForm;

    if (!initial) {
        const base = defaultForm();
        base.manual = false;
        return base;
    }

    return {
        ...defaultForm(),
        ...initial,
        draft: false,
        manual: initial.manual ?? false,
    };
};

const form = useForm<OperationCreateWizardForm>(resolveInitialForm());

const buildingLabel = (id: number | null) => (id ? (buildingLabels.value[id] ?? String(id)) : '--');
const typologyLabel = (value: string | null) => (value ? (typologyLabels.value[value] ?? value) : '--');
const userLabel = (id: number | null) => (id ? (userLabels.value[id] ?? String(id)) : '--');
const selectedBuilding = computed<WizardBuilding | null>(() => {
    if (!form.building_id) {
        return null;
    }

    return props.wizard.buildings.find((building) => building.id === form.building_id) ?? null;
});
const selectedShippingAddress = computed<WizardAddress | null>(() => {
    if (!selectedBuilding.value || !selectedShippingAddressId.value) {
        return null;
    }

    return selectedBuilding.value.addresses.find((item) => item.id === selectedShippingAddressId.value) ?? null;
});
const shippingAddressOptions = computed(() => {
    return (selectedBuilding.value?.addresses ?? []).map((address) => ({
        value: address.id,
        label: [address.street, [address.cap, address.city].filter(Boolean).join(' '), address.province].filter(Boolean).join(' - '),
    }));
});

const ageModel = computed<string | null>({
    get: () => (form.age === null ? null : String(form.age)),
    set: (value) => {
        form.age = value;
    },
});

const isNonEmptyString = (v: unknown): v is string => typeof v === 'string' && v.trim().length > 0;
const isNonNull = <T,>(v: T | null | undefined): v is T => v !== null && v !== undefined;
const hasAtLeastOne = <T,>(v: T[] | null | undefined): boolean => v !== null && v !== undefined && v.length > 0;
const setError = (field: keyof OperationCreateWizardForm, subfield?: string) => {
    if (subfield) {
        form.setError(`${field}.${subfield}`, t('Questo campo è richiesto'));
    } else {
        form.setError(field, t('Questo campo è richiesto'));
    }
};

const isGeneralStepValid = (): boolean => {
    let hasErrors = false;

    if (!isNonNull(form.typology) || !isNonEmptyString(form.typology)) {
        setError('typology');
        hasErrors = true;
    }

    if (!isNonNull(form.ref) || !isNonEmptyString(form.ref)) {
        setError('ref');
        hasErrors = true;
    }

    if (!isNonNull(form.name) || !isNonEmptyString(form.name)) {
        setError('name');
        hasErrors = true;
    }

    if (!isNonNull(form.surname) || !isNonEmptyString(form.surname)) {
        setError('surname');
        hasErrors = true;
    }

    if (!isNonNull(form.age) || !isNonEmptyString(form.age)) {
        setError('age');
        hasErrors = true;
    }

    if (!isNonNull(form.gender) || !isNonEmptyString(form.gender)) {
        setError('gender');
        hasErrors = true;
    }

    if (!isNonNull(form.building_id)) {
        setError('building_id');
        hasErrors = true;
    }

    if (!isNonNull(form.user_id)) {
        setError('user_id');
        hasErrors = true;
    }

    return !hasErrors;
};
const isDetailsStepValid = (): boolean => {
    let hasErrors = false;

    if (form.typology === 'protrusor') {
        if (!isNonNull(form.protrusor_details?.protrusor_typology)) {
            setError('protrusor_details', 'protrusor_typology');
            hasErrors = true;
        }
        if (!isNonNull(form.protrusor_details?.jig)) {
            setError('protrusor_details', 'jig');
            hasErrors = true;
        }
        if (!isNonNull(form.protrusor_details?.mandibular_advancement)) {
            setError('protrusor_details', 'mandibular_advancement');
            hasErrors = true;
        }
        if (!isNonNull(form.protrusor_details?.mandibular_advancement_2)) {
            setError('protrusor_details', 'mandibular_advancement_2');
            hasErrors = true;
        }
    }

    if (form.typology === 'lybra_aligner') {
        if (!isNonNull(form.lybra_aligner_details?.cut_line)) {
            setError('lybra_aligner_details', 'cut_line');
            hasErrors = true;
        }
    }

    if (form.typology === 'guided_surgery') {
        if (!isNonNull(form.guided_surgery_details?.surgery_typology)) {
            setError('guided_surgery_details', 'surgery_typology');
            hasErrors = true;
        }
        if (!isNonNull(form.guided_surgery_details?.odontogram) || !hasAtLeastOne(form.guided_surgery_details?.odontogram)) {
            setError('guided_surgery_details', 'odontogram');
            hasErrors = true;
        }
        if (!isNonNull(form.guided_surgery_details?.desired_implant_line)) {
            setError('guided_surgery_details', 'desired_implant_line');
            hasErrors = true;
        }
    }

    if (form.typology === '3d_mesh') {
        if (!isNonNull(form.three_d_mesh_details?.dimension)) {
            setError('three_d_mesh_details', 'dimension');
            hasErrors = true;
        }
        if (!isNonNull(form.three_d_mesh_details?.odontogram) || !hasAtLeastOne(form.three_d_mesh_details?.odontogram)) {
            setError('three_d_mesh_details', 'odontogram');
            hasErrors = true;
        }
        if (!isNonNull(form.three_d_mesh_details?.['3d_model'])) {
            setError('three_d_mesh_details', '3d_model');
            hasErrors = true;
        }
    }

    if (form.typology === 'prosthesis') {
        if (!isNonNull(form.prosthesis_details?.typology)) {
            setError('prosthesis_details', 'typology');
            hasErrors = true;
        }
        if (form.prosthesis_details!.typology === 'Corone e ponti') {
            if (!isNonNull(form.prosthesis_details?.crowns_and_bridges_details)) {
                setError('prosthesis_details', 'crowns_and_bridges_details');
                hasErrors = true;
            }
        }
        if (form.prosthesis_details!.typology === 'Full-arch') {
            if (!isNonNull(form.prosthesis_details?.full_bridge_details)) {
                setError('prosthesis_details', 'full_bridge_details');
                hasErrors = true;
            }
        }
        if (!isNonNull(form.prosthesis_details?.odontogram) || !hasAtLeastOne(form.prosthesis_details?.odontogram)) {
            setError('prosthesis_details', 'odontogram');
            hasErrors = true;
        }
        if (!isNonNull(form.prosthesis_details?.['3d_normal_model'])) {
            setError('prosthesis_details', '3d_normal_model');
            hasErrors = true;
        }
        if (!isNonNull(form.prosthesis_details?.['3d_excellent_model'])) {
            setError('prosthesis_details', '3d_excellent_model');
            hasErrors = true;
        }
    }

    if (form.typology === 'semi_finished_prostheses') {
        if (!isNonNull(form.semi_finished_prostheses_details?.typology)) {
            setError('semi_finished_prostheses_details', 'typology');
            hasErrors = true;
        }
        if (form.semi_finished_prostheses_details!.typology === 'Corone e ponti') {
            if (!isNonNull(form.semi_finished_prostheses_details?.crowns_and_bridges_details)) {
                setError('semi_finished_prostheses_details', 'crowns_and_bridges_details');
                hasErrors = true;
            }
        }
        if (form.semi_finished_prostheses_details!.typology === 'Full-arch') {
            if (!isNonNull(form.semi_finished_prostheses_details?.full_bridge_details)) {
                setError('semi_finished_prostheses_details', 'full_bridge_details');
                hasErrors = true;
            }
        }
        if (!isNonNull(form.semi_finished_prostheses_details?.odontogram) || !hasAtLeastOne(form.semi_finished_prostheses_details?.odontogram)) {
            setError('semi_finished_prostheses_details', 'odontogram');
            hasErrors = true;
        }
    }

    return !hasErrors;
};
const isUploadStepValid = (): boolean => {
    let hasErrors = false;

    if (isNonNull(form.manual) && form.manual) {
        return true;
    }

    if (form.typology === 'protrusor') {
        if (!isNonNull(form.protrusor_attachments?.scansione_intraorale)) {
            setError('protrusor_attachments', 'scansione_intraorale');
            hasErrors = true;
        }
        if (!isNonNull(form.protrusor_attachments?.rilevazione_dell_avanzamento_mandibolare_con_occlusione)) {
            setError('protrusor_attachments', 'rilevazione_dell_avanzamento_mandibolare_con_occlusione');
            hasErrors = true;
        }
    }

    if (form.typology === 'lybra_aligner') {
        if (!isNonNull(form.lybra_aligner_attachments?.scansione_intraorale)) {
            setError('lybra_aligner_attachments', 'scansione_intraorale');
            hasErrors = true;
        }
        if (!isNonNull(form.lybra_aligner_attachments?.foto_del_sorriso_e_morso_del_paziente)) {
            setError('lybra_aligner_attachments', 'foto_del_sorriso_e_morso_del_paziente');
            hasErrors = true;
        }
    }

    if (form.typology === 'guided_surgery') {
        if (!isNonNull(form.guided_surgery_attachments?.scansione_intraorale)) {
            setError('guided_surgery_attachments', 'scansione_intraorale');
            hasErrors = true;
        }
        if (!isNonNull(form.guided_surgery_attachments?.cbct_allineabile_con_la_scansione_rilevata)) {
            setError('guided_surgery_attachments', 'cbct_allineabile_con_la_scansione_rilevata');
            hasErrors = true;
        }
    }

    if (form.typology === '3d_mesh') {
        if (!isNonNull(form.three_d_mesh_attachments?.scansione_intraorale)) {
            setError('three_d_mesh_attachments', 'scansione_intraorale');
            hasErrors = true;
        }
        if (!isNonNull(form.three_d_mesh_attachments?.cbct_allineabile_con_la_scansione_rilevata)) {
            setError('three_d_mesh_attachments', 'cbct_allineabile_con_la_scansione_rilevata');
            hasErrors = true;
        }
    }

    if (form.typology === 'prosthesis') {
        if (!isNonNull(form.prosthesis_attachments?.scansione_intraorale)) {
            setError('prosthesis_attachments', 'scansione_intraorale');
            hasErrors = true;
        }
        if (!isNonNull(form.prosthesis_attachments?.articolazione)) {
            setError('prosthesis_attachments', 'articolazione');
            hasErrors = true;
        }
    }

    if (form.typology === 'semi_finished_prostheses') {
        if (!isNonNull(form.semi_finished_prostheses_attachments?.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa)) {
            setError('semi_finished_prostheses_attachments', 'progetto_in_stl_da_fresare_oppure_scansione_digitale_completa');
            hasErrors = true;
        }
    }

    return !hasErrors;
};
const isShippingBillingStepValid = (): boolean => {
    let hasErrors = false;

    if (shippingAddressMode.value === 'existing') {
        if (!isNonNull(selectedShippingAddressId.value)) {
            shippingAddressError.value = t('Questo campo è richiesto');
            hasErrors = true;
        }
    } else {
        if (!isNonEmptyString(form.address)) {
            setError('address');
            hasErrors = true;
        }
        if (!isNonEmptyString(form.city)) {
            setError('city');
            hasErrors = true;
        }
        if (!isNonEmptyString(form.province)) {
            setError('province');
            hasErrors = true;
        }
        if (!isNonEmptyString(form.cap)) {
            setError('cap');
            hasErrors = true;
        }
    }

    return !hasErrors;
};
const isReviewStepValid = (): boolean => true;

const onNext = (next: () => void) => {
    form.clearErrors();
    shippingAddressError.value = null;
    if (currentStep.value === 1 && !isGeneralStepValid()) return;
    if (currentStep.value === 2 && !isDetailsStepValid()) return;
    if (currentStep.value === 3 && !isUploadStepValid()) return;
    if (currentStep.value === 4 && !isShippingBillingStepValid()) return;
    if (currentStep.value === 5 && !isReviewStepValid()) return;
    next();
};

const shouldWarnOnLeave = computed(() => !allowNavigation.value && form.isDirty && !form.processing);

const beforeUnloadHandler = (event: BeforeUnloadEvent) => {
    if (!shouldWarnOnLeave.value) return;

    event.preventDefault();
    event.returnValue = '';
};

const removeInertiaBeforeListener = router.on('before', (event: any) => {
    if (!shouldWarnOnLeave.value) return;

    event.preventDefault();

    const visit = event?.detail?.visit;
    if (!visit) return;

    pendingNavigation.value = () => {
        router.visit(visit.url, visit);
    };
    leaveConfirmModalOpen.value = true;
});

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
    removeInertiaBeforeListener();
});

const openSubmitConfirmation = () => {
    if (form.processing) return;

    confirmSubmitModal.value = true;
};

const closeSubmitConfirmation = () => {
    confirmSubmitModal.value = false;
};

const cancelLeave = () => {
    leaveConfirmModalOpen.value = false;
};

const confirmLeave = () => {
    const navigate = pendingNavigation.value;

    leaveConfirmModalOpen.value = false;
    pendingNavigation.value = null;

    if (!navigate) return;

    allowNavigation.value = true;
    navigate();
};

const clearShippingFields = () => {
    form.address = null;
    form.city = null;
    form.province = null;
    form.cap = null;
};

const setShippingAddressMode = (mode: 'existing' | 'new') => {
    if (shippingAddressMode.value === mode) {
        return;
    }

    shippingAddressMode.value = mode;
    selectedShippingAddressId.value = null;
    shippingAddressError.value = null;
    clearShippingFields();
};

const applyShippingAddress = (address: WizardAddress) => {
    form.address = address.street;
    form.city = address.city;
    form.province = address.province;
    form.cap = address.cap;
};

Promise.all([
    loadBuildings().then((items) => {
        buildingLabels.value = Object.fromEntries((items ?? []).map((i: { value: number; label: string }) => [i.value, i.label]));
    }),
    loadTypologies().then((items) => {
        typologyLabels.value = Object.fromEntries((items ?? []).map((i: { value: string; label: string }) => [i.value, i.label]));
    }),
]).catch(() => {});

const { execute } = useAsyncFn(
    async () => {
        if (form.processing) return;

        allowNavigation.value = true;
        form.draft = false;
        form.submit_revision = isRevisionMode.value;
        const method = isEditMode.value ? 'put' : 'post';
        const targetRoute = isEditMode.value
            ? route('operations.update-wizard', { operation: props.wizard.operationId })
            : route('operations.store-wizard');
        await form.submit(method, targetRoute, {
            onError: () => {
                allowNavigation.value = false;
            },
            onCancel: () => {
                allowNavigation.value = false;
            },
        });
    },
    { immediate: false },
);

const confirmAndSubmit = () => {
    if (!legalConsentChecked.value || form.processing) return;

    closeSubmitConfirmation();
    execute();
};

watch(confirmSubmitModal, (isOpen) => {
    if (!isOpen) {
        legalConsentChecked.value = false;
    }
});

watch(leaveConfirmModalOpen, (isOpen) => {
    if (!isOpen) {
        pendingNavigation.value = null;
    }
});

watch(
    () => form.building_id,
    async (buildingId, previousBuildingId) => {
        if (previousBuildingId !== undefined && buildingId !== previousBuildingId) {
            form.user_id = null;
        }

        if (!buildingId) {
            userLabels.value = {};
            form.company_name = null;
            selectedShippingAddressId.value = null;
            clearShippingFields();
            return;
        }

        const building = props.wizard.buildings.find((item) => item.id === buildingId) ?? null;
        form.company_name = building?.name ?? null;

        if (buildingId !== previousBuildingId) {
            const defaultAddress = building?.addresses.find((address) => address.is_default) ?? null;
            selectedShippingAddressId.value = defaultAddress?.id ?? null;

            if (defaultAddress) {
                applyShippingAddress(defaultAddress);
            } else if (!isEditMode.value || previousBuildingId !== undefined) {
                clearShippingFields();
            }
        }

        try {
            const items = await loadUsers();
            userLabels.value = Object.fromEntries((items ?? []).map((i: { value: number; label: string }) => [i.value, i.label]));
        } catch {
            userLabels.value = {};
        }
    },
    { immediate: true },
);

watch(selectedShippingAddressId, (addressId) => {
    if (!addressId) {
        return;
    }

    const address = selectedBuilding.value?.addresses.find((item) => item.id === addressId);
    if (!address) {
        return;
    }

    applyShippingAddress(address);
});

const { execute: saveDraft } = useAsyncFn(
    async () => {
        if (form.processing) return;

        allowNavigation.value = true;
        form.draft = true;
        form.submit_revision = false;
        const method = isEditMode.value ? 'put' : 'post';
        const targetRoute = isEditMode.value
            ? route('operations.update-wizard', { operation: props.wizard.operationId })
            : route('operations.store-wizard');
        await form.submit(method, targetRoute, {
            onError: () => {
                allowNavigation.value = false;
            },
            onCancel: () => {
                allowNavigation.value = false;
            },
        });
    },
    { immediate: false },
);
</script>

<style>
@reference '@/../css/base.css';

.create-wizard__step {
    @apply py-6;
}

.create-wizard__step-title {
    @apply mb-4 text-lg font-semibold;
}

.create-wizard__review {
    @apply space-y-6;
}

.create-wizard__review-section {
    @apply rounded-lg;
}

.create-wizard__review-section-title {
    @apply mb-2 text-lg font-bold;
}

.create-wizard__review-grid {
    @apply grid gap-x-4 gap-y-1 sm:grid-cols-[auto_1fr];
}

.create-wizard__review-grid dt {
    @apply text-gray-500;
}

.create-wizard__review-grid dd {
    @apply text-gray-900;
}

.create-wizard__nav {
    @apply mt-6 flex items-center gap-2;
}

.admin-form__actions {
    @apply mt-6;
}

.create-wizard__empty-step {
    @apply rounded-lg border border-dashed p-6 text-center text-gray-500;
}

.create-wizard__disclaimer-dialog {
    @apply space-y-4;
}

.create-wizard__disclaimer-text {
    @apply text-sm whitespace-pre-line;
}

.create-wizard__disclaimer-actions {
    @apply flex items-center justify-end gap-2;
}

.create-wizard__shipping-billing {
    @apply space-y-12;
}

.create-wizard__address-mode {
    @apply mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2;
}

.create-wizard__address-mode-option {
    @apply w-full cursor-pointer rounded-lg border border-gray-200 bg-white p-2 text-center font-semibold text-gray-700 transition;
}

.create-wizard__address-mode-option:hover {
    @apply border-gray-300 bg-gray-50;
}

.create-wizard__address-mode-option.selected {
    @apply border-gray-900 bg-gray-900 text-white;
}

.create-wizard__shipping-address-preview {
    @apply mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4;
}
</style>
