Ce dépôt contient le code et le Dockerfile qui permettent de générer l'image dynamicnet/laravel-ci utilisée sur le CI Gitlab qui déploie sur notre serveur d'intégration.

A chaque modification il faut rebuilder l'image et la pousser sur le Docker Hub :
- docker build . -t dynamicnet/laravel-ci:8.0
- docker push dynamicnet/laravel-ci
