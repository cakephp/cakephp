# C
omment contribuer 
CakePHP aimes vos contribution. Il y a plusieurs facons de nous aider:
* Creéz un [issue](https://github.com/cakephp/cakephp/issues) sur GitHub, si vous trouvez sujet ouvert concernant des  bugs .Ecrivez des corrections pour les problèmes de bug deja ouvertset de préfencere avec des cas test inclus. 
* Contribuez à la documentation [documentation](https://github.com/cakephp/docs)Il y a certaines directives que nous demandons au contributeurs de suivre afin d'avoir une chance de garer le controle des choses

.

## Getting Started

* Assurez vous d'avoir [GitHub account](https://github.com/signup/free).
* Soummettez une [issue](https://github.com/cakephp/cakephp/issues), en assumant qu'aucune n'existe pour le moment..
  * Décrivé clairement le problème en incluant les etapes a fairequand il s'agit d'un bug.
  * Assurez-vous de remplir la version la plus recente ou vous avez détecter le problème.
* Fork le referentiel sur GitHub.

## Faire des changement

* Créez un sujet de l'endroit ou vous voulez baser votre travail  * C'est generalement la branche maitre.
  *Choisissez uniquement les branches si bous etes certain que votre correction est sur cette branche .
  * Pour creer rapidement un sujet branche basé sur maitre ; `git branch
    master/my_contribution master`ensuite récupérer la nouvelle branche.`git
    checkout master/my_contribution`. Evitez de travailler directement la  `master` branche, afin d'eviter les conflits si vous poussez des mis a jour des le debut .
* Make commits of logical units.
* Verifier s'il existe des espaces inutiles avec `git diff --check` avant d'engager.
* Utiliser des messages commit et réferencer le numero de série.
*Les cas de test de base devraient continuer à passer . Vous pouvez tester en local ou permettre à la fourche [travis-ci](https://travis-ci.org/) ,alors tout les tests et codesniffs seront exécuté.
* Votre travail doit s'appliquer à  [CakePHP coding standards](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html).

## Which branch to base the work

* Bugfix branches will be based on master.
* Les nouvelels fonctionnalités qui seront r"tro-compatibles seront basés sur la prochaine version de la branche.New features that are backwards compatible will be based on next minor release
  branch.
* 

## Faire des changement

*Imposer vos modifications à un sujet dans votre referentiel .
* Soumettez une demande de tirage au referentiel dans l'organisation de cakephp, avec la branche visé.

## Test cases and codesniffer

CakePHP tests requires [PHPUnit](http://www.phpunit.de/manual/current/en/installation.html)
3.7, version 4 is not compatible. Pour lancer les cas test lcoalement utilisez la commande suivante:

    ./lib/Cake/Console/cake test core AllTests --stderr

To run the sniffs for CakePHP coding standards:

    phpcs -p --extensions=php --standard=CakePHP ./lib/Cake

Check the [cakephp-codesniffer](https://github.com/cakephp/cakephp-codesniffer)
referentiel pour installer  CakePHP standard. The [README](https://github.com/cakephp/cakephp-codesniffer/blob/master/README.mdown) contains installation info
for the sniff and phpcs.

# Additional Resources

* [CakePHP coding standards](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html)
* [Existing issues](https://github.com/cakephp/cakephp/issues)
* [Development Roadmaps](https://github.com/cakephp/cakephp/wiki#roadmaps)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests/)
* #cakephp IRC channel on freenode.org
