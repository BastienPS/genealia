# Déploiement de Généalia sur genealia.fr (VPS IONOS + FrankenPHP)

Mise en production de l'app Symfony 8.1 sur un Cloud Server IONOS, via Docker + FrankenPHP
(Caddy délivre automatiquement le certificat TLS Let's Encrypt). Le domaine `genealia.fr`
étant déjà chez IONOS, la configuration DNS se fait dans le même tableau de bord.

## 0. Pré-requis

- Un Cloud Server / VPS IONOS sous Linux (Debian/Ubuntu) accessible en SSH.
- Le domaine `genealia.fr` dans le même compte IONOS.
- Docker + Docker Compose v2 installés sur le VPS.
- Les ports **80** et **443** ouverts dans le pare-feu IONOS (Cloud Panel → Pare-feu).
- En local : un terminal avec accès `git` au dépôt.

## 1. Configurer le DNS (tableau de bord IONOS)

Dans la zone DNS de `genealia.fr` :

- Enregistrement **A** `@` → adresse IPv4 publique du VPS.
- Enregistrement **A** `www` → même IP (ou CNAME vers `genealia.fr`).

Vérifier la propagation :

```sh
dig +short genealia.fr
dig +short www.genealia.fr
```

> Caddy demande le certificat TLS au premier démarrage. Le DNS doit pointer vers le VPS
> **avant** le premier `up`, sinon l'émission du certificat échouera (réessayera ensuite).

## 2. Préparer les secrets sur le VPS

```sh
git clone <repo-url> /opt/genealia
cd /opt/genealia
cp .env.prod.local.example .env.prod.local
nano .env.prod.local   # remplir les valeurs réelles
```

Récupérer les valeurs depuis votre `.env.local` local (les mêmes secrets fonctionnent) :

- `APP_SECRET` — générer un nouveau 64 hex : `php -r 'echo bin2hex(random_bytes(32));'`
- `ADMIN_PASSWORD_HASH` — hash bcrypt du mot de passe admin (votre `.env.local` local).
- `OAUTH_GOOGLE_CLIENT_ID/SECRET`, `OAUTH_FACEBOOK_CLIENT_ID/SECRET` — mêmes que local.
- `MAILER_DSN` — votre SMTP IONOS (`admin@genealia.fr` ou un `no-reply@genealia.fr`).
- `MAILER_FROM=no-reply@genealia.fr` (adresse autorisée chez IONOS).

## 3. Construire et lancer

```sh
docker compose -f compose.prod.yaml up -d --build
```

Au premier démarrage, Caddy obtient le certificat pour `genealia.fr` (et `www`) auprès de
Let's Encrypt. Voir les logs en cas de souci TLS :

```sh
docker compose -f compose.prod.yaml logs -f app
```

## 4. Migrations de base de données

```sh
docker compose -f compose.prod.yaml exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f compose.prod.yaml exec app php bin/console cache:warmup --env=prod
```

À relancer après chaque mise à jour du code (redéploiement).

## 5. Redéploiement (mise à jour du site)

```sh
cd /opt/genealia
git pull
docker compose -f compose.prod.yaml up -d --build
docker compose -f compose.prod.yaml exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f compose.prod.yaml exec app php bin/console cache:warmup --env=prod
```

Le volume `var_data` préserve la base SQLite et les documents uploadés entre les rebuilds.

## 6. OAuth — configurer les redirect URIs (manuel)

### Google (Google Cloud Console → APIs & Services → Credentials)

1. Ouvrir le client OAuth existant (Web app).
2. Ajouter dans **URI de redirection autorisés** :
   `https://genealia.fr/connect/google/check`
   (conserver l'URI localhost pour les tests locaux).
3. **Écran de consentement** : passer de *Testing* à *Production*. Renseigner :
   - URL de la politique de confidentialité : `https://genealia.fr/confidentialite`
   - URL des conditions d'utilisation (optionnel).
4. Ajouter votre adresse e-mail comme contact développeur si demandé.

### Facebook (Meta for Developers → Mon app → Facebook Login)

1. **Paramètres → URI de redirection OAuth valides** : ajouter
   `https://genealia.fr/connect/facebook/check` (Facebook impose HTTPS — OK, Caddy fournit le TLS).
2. Renseigner l'**URL de la politique de confidentialité** dans App Settings.
3. Passer l'app du mode **Development → Live** (interrupteur en haut).

> La page `https://genealia.fr/confidentialite` doit être publique et accessible pour que
> Google et Facebook valident la publication de l'écran de consentement.

## 7. Vérifications post-déploiement (depuis le VPS)

```sh
curl -sI https://genealia.fr/                      # → 200, certificat TLS valide
curl -sI https://genealia.fr/mentions-legales      # → 200
curl -sI https://genealia.fr/confidentialite       # → 200
curl -sI https://genealia.fr/login                 # → 200
curl -sI https://genealia.fr/request/new           # → 302 vers /login (non authentifié)
curl -sI https://genealia.fr/connect/google        # → 302 accounts.google.com
curl -sI https://genealia.fr/connect/facebook      # → 302 facebook.com
curl -sI https://genealia.fr/var/data.db           # → 404 (la base n'est pas servie)
```

Puis tester en navigateur : connexion Google et Facebook, dépôt d'une demande,
réponse admin dans la messagerie, réception de l'e-mail de notification.

## 8. Sauvegardes

Le volume `var_data` contient la base SQLite (`var/data.db`) et les documents clients
(`var/uploads/`). Sauvegarder régulièrement :

```sh
docker run --rm -v genealia_var_data:/data -v $(pwd):/backup alpine \
  tar czf /backup/genealia-var-$(date +%F).tgz -C /data .
```

Restaurer un backup implique d'arrêter le service, extraire l'archive dans le volume,
puis relancer.

## Checklist récapitulative

- [ ] DNS `genealia.fr` (+ `www`) → IP du VPS chez IONOS
- [ ] Ports 80/443 ouverts dans le pare-feu IONOS
- [ ] `.env.prod.local` créé sur le VPS avec les secrets
- [ ] `docker compose -f compose.prod.yaml up -d --build`
- [ ] `doctrine:migrations:migrate` exécuté
- [ ] Pages mentions légales + confidentialité complétées (contenu réel)
- [ ] Redirect URIs Google + Facebook ajoutés (`https://genealia.fr/connect/{provider}/check`)
- [ ] Écran de consentement Google publié (URL confidentialité renseignée)
- [ ] App Facebook passée en mode Live
- [ ] Vérifications curl OK
- [ ] Sauvegarde planifiée pour `var_data`