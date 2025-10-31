<template>
    <div class="jurisprudence-card">
        <div class="card-header">
            <div class="jurisdiction-badge" :class="`jurisdiction-${getJurisdictionType(jurisprudence.jurisdiction)}`">
                {{ jurisprudence.jurisdiction }}
            </div>
            <span class="decision-date">{{ formatDate(jurisprudence.date_decision) }}</span>
        </div>

        <h5 class="decision-title">{{ jurisprudence.title }}</h5>

        <p v-if="jurisprudence.summary" class="decision-summary">
            {{ truncateSummary(jurisprudence.summary) }}
        </p>

        <div class="card-footer">
            <div class="card-meta">
                <span class="decision-type-badge">
                    {{ jurisprudence.decision_type_label }}
                </span>
                <span v-if="jurisprudence.relevance_score" class="relevance-badge" :class="getRelevanceClass(jurisprudence.relevance_score)">
                    {{ jurisprudence.relevance_score }}% pertinent
                </span>
            </div>

            <a 
                v-if="jurisprudence.legifrance_url" 
                :href="jurisprudence.legifrance_url" 
                target="_blank" 
                rel="noopener" 
                class="view-link"
            >
                Voir la décision
                <svg class="external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                </svg>
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    jurisprudence: {
        type: Object,
        required: true,
    },
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

const truncateSummary = (summary) => {
    if (!summary) return '';
    return summary.length > 200 ? summary.substring(0, 200) + '...' : summary;
};

const getJurisdictionType = (jurisdiction) => {
    const highJurisdictions = ['CE', 'CC', 'Cass.Civ', 'Cass.Crim', 'Cass.Soc', 'Cass.Com'];
    return highJurisdictions.includes(jurisdiction) ? 'high' : 'regular';
};

const getRelevanceClass = (score) => {
    if (score >= 80) return 'high';
    if (score >= 60) return 'medium';
    return 'low';
};
</script>

<style scoped>
.jurisprudence-card {
    background: white;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}

.jurisprudence-card:hover {
    border-color: #C7D2FE;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.jurisdiction-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    color: white;
}

.jurisdiction-badge.jurisdiction-high {
    background: linear-gradient(135deg, #DC2626, #B91C1C);
}

.jurisdiction-badge.jurisdiction-regular {
    background: linear-gradient(135deg, #6366F1, #4F46E5);
}

.decision-date {
    font-size: 13px;
    color: #6B7280;
    font-weight: 500;
}

.decision-title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.decision-summary {
    font-size: 14px;
    color: #4B5563;
    line-height: 1.6;
    margin: 0 0 16px 0;
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 12px;
    border-top: 1px solid #F3F4F6;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.decision-type-badge {
    padding: 4px 10px;
    background: #F3F4F6;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
}

.relevance-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: white;
}

.relevance-badge.high {
    background: #10B981;
}

.relevance-badge.medium {
    background: #F59E0B;
}

.relevance-badge.low {
    background: #6B7280;
}

.view-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #4F46E5;
    text-decoration: none;
    transition: color 0.2s;
}

.view-link:hover {
    color: #4338CA;
}

.external-icon {
    width: 14px;
    height: 14px;
}

/* Responsive */
@media (max-width: 640px) {
    .card-footer {
        flex-direction: column;
        align-items: flex-start;
    }

    .view-link {
        align-self: flex-end;
    }
}
</style>

