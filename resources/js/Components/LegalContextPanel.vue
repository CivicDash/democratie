<template>
    <div class="legal-context-panel">
        <!-- Header -->
        <div class="panel-header">
            <div class="header-content">
                <h3 class="panel-title">
                    <span class="title-icon">📖</span>
                    Contexte Juridique
                </h3>
                <p class="panel-subtitle">
                    Articles de loi modifiés et jurisprudence associée
                </p>
            </div>
            
            <div v-if="!loading && legalContext" class="header-stats">
                <div class="stat-badge">
                    <span class="stat-number">{{ legalContext.references_count }}</span>
                    <span class="stat-label">Article{{ legalContext.references_count > 1 ? 's' : '' }}</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-number">{{ totalJurisprudence }}</span>
                    <span class="stat-label">Décision{{ totalJurisprudence > 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Chargement du contexte juridique...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!legalContext || !legalContext.has_legal_context" class="empty-state">
            <span class="empty-icon">📄</span>
            <p class="empty-text">Aucune référence juridique détectée dans ce texte</p>
            <p class="empty-hint">Les références sont automatiquement extraites lors de la synchronisation</p>
        </div>

        <!-- References List -->
        <div v-else class="references-list">
            <div 
                v-for="reference in legalContext.references" 
                :key="reference.id"
                class="reference-card"
                :class="{ 'expanded': expandedReference === reference.id }"
            >
                <!-- Card Header (clickable) -->
                <div 
                    class="reference-header"
                    @click="toggleReference(reference.id)"
                >
                    <div class="reference-info">
                        <div class="reference-badge" :class="`type-${reference.article_type}`">
                            {{ reference.article_type === 'legislative' ? 'L' : 'R' }}
                        </div>
                        
                        <div class="reference-details">
                            <h4 class="reference-title">
                                Article {{ reference.reference }} du {{ reference.code_name }}
                            </h4>
                            <p class="reference-type">{{ reference.type_label }}</p>
                        </div>
                    </div>
                    
                    <div class="reference-actions">
                        <span v-if="reference.jurisprudence_count > 0" class="juri-count">
                            ⚖️ {{ reference.jurisprudence_count }}
                        </span>
                        <button class="expand-btn">
                            <svg 
                                class="expand-icon"
                                :class="{ 'rotated': expandedReference === reference.id }"
                                xmlns="http://www.w3.org/2000/svg" 
                                viewBox="0 0 20 20" 
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Card Body (expandable) -->
                <Transition name="expand">
                    <div v-if="expandedReference === reference.id" class="reference-body">
                        <!-- Article Text -->
                        <div v-if="reference.article_current_text" class="article-section">
                            <h5 class="section-title">📄 Texte actuel de l'article</h5>
                            <div class="article-text">
                                {{ reference.context_description || 'Texte non disponible' }}
                            </div>
                        </div>

                        <!-- Proposed Modifications (if any) -->
                        <div v-if="reference.article_proposed_text" class="article-section">
                            <h5 class="section-title">✏️ Modifications proposées</h5>
                            <div class="article-text modified">
                                {{ reference.article_proposed_text }}
                            </div>
                        </div>

                        <!-- Jurisprudence -->
                        <div v-if="reference.jurisprudences && reference.jurisprudences.length > 0" class="jurisprudence-section">
                            <h5 class="section-title">⚖️ Jurisprudence pertinente ({{ reference.jurisprudences.length }})</h5>
                            <div class="jurisprudence-list">
                                <JurisprudenceCard
                                    v-for="juri in reference.jurisprudences"
                                    :key="juri.id"
                                    :jurisprudence="juri"
                                />
                            </div>
                        </div>

                        <!-- External Link -->
                        <div class="reference-footer">
                            <a 
                                v-if="reference.legifrance_url" 
                                :href="reference.legifrance_url" 
                                target="_blank" 
                                rel="noopener" 
                                class="legifrance-link"
                            >
                                🔗 Voir sur Légifrance
                                <svg class="external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import JurisprudenceCard from './JurisprudenceCard.vue';

const props = defineProps({
    propositionId: {
        type: Number,
        required: true,
    },
});

const legalContext = ref(null);
const loading = ref(true);
const expandedReference = ref(null);

const totalJurisprudence = computed(() => {
    if (!legalContext.value?.references) return 0;
    return legalContext.value.references.reduce((sum, ref) => sum + ref.jurisprudence_count, 0);
});

const loadLegalContext = async () => {
    try {
        const response = await axios.get(`/api/legal-context/propositions/${props.propositionId}`);
        legalContext.value = response.data.data;
    } catch (error) {
        console.error('Error loading legal context:', error);
    } finally {
        loading.value = false;
    }
};

const toggleReference = (referenceId) => {
    expandedReference.value = expandedReference.value === referenceId ? null : referenceId;
};

onMounted(() => {
    loadLegalContext();
});
</script>

<style scoped>
.legal-context-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.panel-header {
    background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
    padding: 24px;
    color: white;
}

.header-content {
    margin-bottom: 16px;
}

.panel-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-icon {
    font-size: 28px;
}

.panel-subtitle {
    font-size: 14px;
    margin: 0;
    opacity: 0.9;
}

.header-stats {
    display: flex;
    gap: 16px;
}

.stat-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
}

.stat-number {
    font-size: 28px;
    font-weight: 800;
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 24px;
    color: #6B7280;
}

.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #E5E7EB;
    border-top-color: #4F46E5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-text {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 8px 0;
}

.empty-hint {
    font-size: 14px;
    color: #9CA3AF;
    margin: 0;
}

.references-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.reference-card {
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.reference-card:hover {
    border-color: #6366F1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.reference-card.expanded {
    border-color: #4F46E5;
}

.reference-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    cursor: pointer;
    background: #F9FAFB;
    transition: background 0.2s;
}

.reference-header:hover {
    background: #F3F4F6;
}

.reference-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.reference-badge {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    color: white;
}

.reference-badge.type-legislative {
    background: linear-gradient(135deg, #4F46E5, #6366F1);
}

.reference-badge.type-regulatory {
    background: linear-gradient(135deg, #10B981, #059669);
}

.reference-details {
    flex: 1;
}

.reference-title {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 4px 0;
}

.reference-type {
    font-size: 13px;
    color: #6B7280;
    margin: 0;
}

.reference-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.juri-count {
    font-size: 13px;
    font-weight: 600;
    color: #6366F1;
    padding: 6px 12px;
    background: #EEF2FF;
    border-radius: 8px;
}

.expand-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.expand-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.3s;
}

.expand-icon.rotated {
    transform: rotate(180deg);
}

.reference-body {
    padding: 24px;
    background: white;
    border-top: 1px solid #E5E7EB;
}

.article-section,
.jurisprudence-section {
    margin-bottom: 24px;
}

.article-section:last-child,
.jurisprudence-section:last-child {
    margin-bottom: 0;
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.article-text {
    padding: 16px;
    background: #F9FAFB;
    border-left: 4px solid #6366F1;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
}

.article-text.modified {
    background: #FEF3C7;
    border-left-color: #F59E0B;
}

.jurisprudence-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reference-footer {
    padding-top: 16px;
    border-top: 1px solid #E5E7EB;
}

.legifrance-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #4F46E5;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.legifrance-link:hover {
    background: #4338CA;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.external-icon {
    width: 16px;
    height: 16px;
}

/* Expand transition */
.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    max-height: 0;
    opacity: 0;
}

.expand-enter-to,
.expand-leave-from {
    max-height: 2000px;
    opacity: 1;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .panel-header {
        padding: 20px 16px;
    }
    
    .panel-title {
        font-size: 20px;
    }
    
    .title-icon {
        font-size: 24px;
    }
    
    .panel-subtitle {
        font-size: 13px;
    }
    
    .header-stats {
        flex-direction: row;
        gap: 12px;
    }
    
    .stat-badge {
        padding: 8px 16px;
    }
    
    .stat-number {
        font-size: 24px;
    }
    
    .references-list {
        padding: 16px;
        gap: 12px;
    }
    
    .reference-header {
        padding: 12px 16px;
    }
    
    .reference-info {
        gap: 12px;
    }
    
    .reference-badge {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .reference-title {
        font-size: 14px;
    }
    
    .reference-type {
        font-size: 12px;
    }
    
    .reference-actions {
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
    }
    
    .juri-count {
        font-size: 12px;
        padding: 4px 8px;
    }
    
    .reference-body {
        padding: 16px;
    }
    
    .section-title {
        font-size: 13px;
    }
    
    .article-text {
        font-size: 13px;
        padding: 12px;
    }
    
    .legifrance-link {
        width: 100%;
        justify-content: center;
        padding: 12px 16px;
    }
}

@media (max-width: 480px) {
    .reference-card {
        border-radius: 8px;
    }
    
    .reference-info {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .reference-badge {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }
    
    .panel-header {
        padding: 16px 12px;
    }
    
    .references-list {
        padding: 12px;
    }
}
</style>

