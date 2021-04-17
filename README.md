# Symfony with ELK and Elastic FileBeats Stack

**Prerequisites**: Make sure that docker and docker-compose are installed in your machine and available for your console  

Download the repository code to a folder in your machine and setup all the needed docker containers running the command

    docker-compose up --build

All containers should be up and running now.

The folder "var" was automatically created because it's a shared volume with the fileBeats container.
We need to take ownership of this folder so later our php container does not have problems writing on it.

Open a new console and type (change 'ourUser' with your own user).

    sudo chown -R ourUser var
	
Open a bash in the recently created php container with 

    docker exec -it php_cont bash

Run composer install within this bash (composer was installed during the docker php container setup)

    composer install

Update the database schema with

    php bin/console d:s:u --force

Load the data fixtures with

    php bin/console doctrine:fixtures:load

Create the database for the test environment and populate it with fixture data

    php bin/console doctrine:database:create --env=test
    php bin/console d:s:u --force --env=test
    php bin/console doctrine:fixtures:load --env=test

You can launch the provided tests with (It will install php unit the first time) 

    php bin/phpunit
    
A postman collection is available in the root directory to test all api methods. 
Please, notice that uuids are randomly generated, and the ones present in some postman urls are just placeholders.
In order to test this urls replace the placeholder uuid with a real one you get from a get collection call.

Also, after generating the fixture data and hitting some endpoints using postman you can check elastic logs through kibana and your web browser 'localhost:5602'.

Check the "observability/logs" section by clicking in the hamburguer icon, then click on the "stream live" button and start using the postman to see the log data grow in real time!

If you want a deeper explanation check the "README extended.pdf" file in the root folder.














