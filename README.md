# Habeuk Static Page

Module Drupal 10 / 11 pour créer des **pages HTML statiques ultra-rapides** destinées aux promotions.

## Objectif

Permettre de coller rapidement du HTML généré par une IA (ou écrit à la main), d’ajuster header / body / footer, et de publier une page promo qui se charge **le plus vite possible**.

- **Aucun CSS/JS Drupal** n’est chargé.
- Le rendu public passe par un Controller qui retourne une `CacheableResponse` pure HTML.
- Pas de thème, pas de libraries, pas de preprocess.

## Structure de l’entité

Entity type : `hbk_static_page`

| Champ     | Type       | Description                                      |
|-----------|------------|--------------------------------------------------|
| title     | string     | Titre interne + fallback pour la balise `<title>` |
| header    | text_long  | Début de page (doctype, head, ouverture body…)   |
| body      | text_long  | Contenu principal                                |
| footer    | text_long  | Fin de page + scripts éventuels                  |
| status    | boolean    | Publié / non publié                              |
| path      | path       | Alias d’URL (optionnel)                          |

## Installation

```bash
# Copier le module dans modules/custom/
drush en habeuk_static_page -y
drush cr
```

## Utilisation

1. Aller dans **Contenu → Pages statiques (Promo)**
2. Cliquer sur **Ajouter une page statique**
3. Coller le HTML généré par l’IA :
   - **Entête HTML** : tout ce qui précède le contenu principal
   - **Body** : le contenu
   - **Pied de page HTML** : fermeture + scripts (tracking, etc.)
4. Publier
5. La page est accessible sur `/promo/{id}` (ou via l’alias que vous définissez)

## Performance

- Le Controller construit le HTML par simple concaténation de chaînes.
- Aucun système de thème Drupal n’est invoqué.
- Aucune bibliothèque CSS/JS n’est attachée.
- Cache tags + `CacheableResponse` → Page Cache et Dynamic Page Cache fonctionnent parfaitement.
- Pour les anonymes : `Cache-Control: public, max-age=3600` (ajustable).

## Permissions

- `administer hbk_static_page`
- `view hbk_static_page`
- `create hbk_static_page`
- `edit hbk_static_page`
- `delete hbk_static_page`

## Workflow recommandé avec IA

1. Demander à ChatGPT / Claude / Grok :  
   « Génère une landing page HTML complète, moderne, responsive, pour [offre]. Sépare clairement le header, le body et le footer. »
2. Coller chaque partie dans les champs correspondants.
3. Faire les ajustements (textes, liens, tracking pixels…).
4. Publier → la promo est en ligne.

## Auteur

Module développé pour **Habeuk** (https://habeuk.com)

Compatible Drupal 10 et Drupal 11.
