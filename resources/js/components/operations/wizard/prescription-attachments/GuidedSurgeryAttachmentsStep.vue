<script setup lang="ts">
import type { OperationCreateWizardForm } from '@/types/Operation';
import { BbDropzone } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    form: OperationCreateWizardForm;
    manual: boolean | null;
};

defineProps<Props>();
</script>

<template>
    <div class="admin-form__grid">
        <template v-if="manual">
            <ul class="attachments-list">
                <li>{{ t('Scansione intraorale') }}</li>
                <li>{{ t('CBCT allineabile con la scansione rilevata') }}</li>
                <li>{{ t('Ceratura diagnostica') }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Scansione intraorale') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.guided_surgery_attachments!.scansione_intraorale"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['guided_surgery_attachments.scansione_intraorale']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.guided_surgery_attachments!.scansione_intraorale
                            ? t('File selezionato', { name: form.guided_surgery_attachments!.scansione_intraorale.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('CBCT allineabile con la scansione rilevata') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.guided_surgery_attachments!.cbct_allineabile_con_la_scansione_rilevata"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['guided_surgery_attachments.cbct_allineabile_con_la_scansione_rilevata']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.guided_surgery_attachments!.cbct_allineabile_con_la_scansione_rilevata
                            ? t('File selezionato', { name: form.guided_surgery_attachments!.cbct_allineabile_con_la_scansione_rilevata.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Ceratura diagnostica') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.guided_surgery_attachments!.ceratura_diagnostica"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['guided_surgery_attachments.ceratura_diagnostica']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.guided_surgery_attachments!.ceratura_diagnostica
                            ? t('File selezionato', { name: form.guided_surgery_attachments!.ceratura_diagnostica.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
