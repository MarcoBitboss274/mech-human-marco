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
                <li>{{ t('Articolazione') }}</li>
                <li>{{ t('Foto con campione colore') }}</li>
                <li>{{ t('Foto del sorriso') }}</li>
                <li>{{ t('Scansione e foto del provvisorio') }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Scansione intraorale') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.prosthesis_attachments!.scansione_intraorale"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['prosthesis_attachments.scansione_intraorale']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.prosthesis_attachments!.scansione_intraorale
                            ? t('File selezionato', { name: form.prosthesis_attachments!.scansione_intraorale.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Articolazione') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.prosthesis_attachments!.articolazione"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['prosthesis_attachments.articolazione']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.prosthesis_attachments!.articolazione
                            ? t('File selezionato', { name: form.prosthesis_attachments!.articolazione.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Foto con campione colore') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.prosthesis_attachments!.foto_con_campione_colore"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['prosthesis_attachments.foto_con_campione_colore']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.prosthesis_attachments!.foto_con_campione_colore
                            ? t('File selezionato', { name: form.prosthesis_attachments!.foto_con_campione_colore.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Foto del sorriso') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.prosthesis_attachments!.foto_del_sorriso"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['prosthesis_attachments.foto_del_sorriso']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.prosthesis_attachments!.foto_del_sorriso
                            ? t('File selezionato', { name: form.prosthesis_attachments!.foto_del_sorriso.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Scansione e foto del provvisorio') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.prosthesis_attachments!.scansione_e_foto_del_provvisorio"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['prosthesis_attachments.scansione_e_foto_del_provvisorio']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.prosthesis_attachments!.scansione_e_foto_del_provvisorio
                            ? t('File selezionato', { name: form.prosthesis_attachments!.scansione_e_foto_del_provvisorio.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
