Ce dépôt contient le code et le Dockerfile qui permettent de générer l'image dynamicnet/laravel-ci utilisée sur le CI Gitlab qui déploie sur notre serveur d'intégration.

A chaque modification il faut rebuilder l'image et la pousser sur le Docker Hub :

# PHP 7.3
- docker build -f ./PHP7.3.Dockerfile -t dynamicnet/laravel-ci:7.3 .
- docker push dynamicnet/laravel-ci:7.3

# PHP 7.4
- docker build -f ./PHP7.4.Dockerfile -t dynamicnet/laravel-ci:7.4 .
- docker push dynamicnet/laravel-ci:7.4

# PHP 8.0
- docker build -f ./PHP8.0.Dockerfile -t dynamicnet/laravel-ci:8.0 .
- docker push dynamicnet/laravel-ci:8.0

# PHP 8.1
- docker build -f ./PHP8.1.Dockerfile -t dynamicnet/laravel-ci:8.1 .
- docker push dynamicnet/laravel-ci:8.1

# PHP 8.2
- docker build -f ./PHP8.2.Dockerfile -t dynamicnet/laravel-ci:8.2 .
- docker push dynamicnet/laravel-ci:8.2

# PHP 8.3
- docker build -f ./PHP8.3.Dockerfile -t dynamicnet/laravel-ci:8.3 .
- docker push dynamicnet/laravel-ci:8.3

# Fonctionnement

Si une variable DOTENV_APP_KEY est fournie dans le CI/CD, alors elle sera utilisée pour comme APP_KEY lors du déploiement
et la commande artisan key:generate ne sera pas jouée. Ceci permet de ne pas changer de clé à chaque déploiement, ce qui doit être le cas lorsque l'application utilise Crypt::encryptString() et Crypt::decryptString()
