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
                <li>{{ t('Foto del sorriso e morso del paziente') }}</li>
                <li>{{ t('Ortopantomografia') }}</li>
                <li>{{ t('Rx anteroposteriore delle ossa mascellari') }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Scansione intraorale') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.lybra_aligner_attachments!.scansione_intraorale"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['lybra_aligner_attachments.scansione_intraorale']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.lybra_aligner_attachments!.scansione_intraorale
                            ? t('File selezionato', { name: form.lybra_aligner_attachments!.scansione_intraorale.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Foto del sorriso e morso del paziente') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.lybra_aligner_attachments!.foto_del_sorriso_e_morso_del_paziente"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['lybra_aligner_attachments.foto_del_sorriso_e_morso_del_paziente']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.lybra_aligner_attachments!.foto_del_sorriso_e_morso_del_paziente
                            ? t('File selezionato', { name: form.lybra_aligner_attachments!.foto_del_sorriso_e_morso_del_paziente.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Ortopantomografia') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.lybra_aligner_attachments!.ortopantomografia"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['lybra_aligner_attachments.ortopantomografia']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.lybra_aligner_attachments!.ortopantomografia
                            ? t('File selezionato', { name: form.lybra_aligner_attachments!.ortopantomografia.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Rx anteroposteriore delle ossa mascellari') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.lybra_aligner_attachments!.rx_anteroposteriore_delle_ossa_mascellari"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['lybra_aligner_attachments.rx_anteroposteriore_delle_ossa_mascellari']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.lybra_aligner_attachments!.rx_anteroposteriore_delle_ossa_mascellari
                            ? t('File selezionato', { name: form.lybra_aligner_attachments!.rx_anteroposteriore_delle_ossa_mascellari.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
