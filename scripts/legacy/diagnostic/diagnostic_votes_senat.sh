#!/bin/bash
# Diagnostic des votes Sénat

echo "=========================================="
echo "DIAGNOSTIC VOTES SÉNAT"
echo "=========================================="

echo ""
echo "1. Structure de senat_senateurs_votes:"
docker compose exec postgres psql -U civicdash -d civicdash -c "\d senat_senateurs_votes"

echo ""
echo "2. Exemples de senmat dans senat_senateurs_votes:"
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT DISTINCT senmat, LENGTH(senmat) as len FROM senat_senateurs_votes LIMIT 10;"

echo ""
echo "3. Exemples de senmat dans senat_senateurs_sen (sénateurs):"
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT DISTINCT senmat, LENGTH(senmat) as len FROM senat_senateurs_sen LIMIT 10;"

echo ""
echo "4. Positions de vote distinctes:"
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT DISTINCT posvotcod, COUNT(*) FROM senat_senateurs_votes GROUP BY posvotcod;"

echo ""
echo "5. Nombre total de votes dans senat_senateurs_votes:"
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) FROM senat_senateurs_votes;"

echo ""
echo "6. Nombre de votes dans la vue votes_senat:"
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) FROM votes_senat;"

echo ""
echo "7. Vérification jointure senmat - exemple sénateur 20032T:"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    s.senmat as senateur_matricule,
    COUNT(v.votesid) as nb_votes
FROM senat_senateurs_sen s
LEFT JOIN senat_senateurs_votes v ON TRIM(s.senmat) = TRIM(v.senmat)
WHERE TRIM(s.senmat) = '20032T'
GROUP BY s.senmat;
"

echo ""
echo "8. Votes pour le sénateur 20032T dans la vue:"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT senateur_matricule, position, COUNT(*) 
FROM votes_senat 
WHERE TRIM(senateur_matricule) = '20032T'
GROUP BY senateur_matricule, position;
"

echo ""
echo "9. Comparaison format senmat (avec/sans espaces):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    v.senmat as vote_senmat,
    s.senmat as sen_senmat,
    LENGTH(v.senmat) as vote_len,
    LENGTH(s.senmat) as sen_len,
    v.senmat = s.senmat as exact_match,
    TRIM(v.senmat) = TRIM(s.senmat) as trim_match
FROM senat_senateurs_votes v
LEFT JOIN senat_senateurs_sen s ON v.senmat = s.senmat
LIMIT 5;
"

echo ""
echo "=========================================="
echo "FIN DIAGNOSTIC"
echo "=========================================="


