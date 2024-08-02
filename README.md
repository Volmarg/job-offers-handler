<div align="center"><h1>Job searcher</h1></div>

<div align="center">
<img src="https://img.shields.io/badge/php%208.1-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white"/>
<img src="https://img.shields.io/badge/Rabbitmq-FF6600?style=for-the-badge&logo=rabbitmq&logoColor=white"/>
<img src="https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white"/>
<img src="https://img.shields.io/badge/supervisor-%23777BB4.svg?style=for-the-badge&logoColor=white"/>
<img src="https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white"/>
<img src="https://img.shields.io/badge/composer-%2366595C.svg?style=for-the-badge&logo=composer&Color=white"/>
<img src="https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white"/>
</div>

<div align="center">
This project is a part of: <b><a href="https://github.com/Volmarg/voltigo-frontend">Voltigo</a></b>
</div>


## Description

This project (as the name suggest) searches for a job offers based on provided criteria such as:
- target country,
- target location (city),
- distance from location,
- keywords,

Searching gets handled by:
- `AllJobOffersExtractorCommand`,
- `SingleConfigurationJobOffersExtractorCommand`

Job searching is not based on scrapping / crawling. The more job-services configurations
are set, the more countries and job-offers are getting returned.

Job-service configurations are defined in:
- `config/packages/jobServices`,

Underlying resolvers are set in:
- `src/Service/JobService/Resolver`

## Running the project

- make sure that **mailer** is running, You can use already prepared mailer project from <a href="https://github.com/Volmarg/voltigo-mailpit">here</a>
- make sure that **rabbitmq** is running, You can use already prepared rabbitmq project from <a href="https://github.com/Volmarg/voltigo-rabbit-mq">here</a>
- make sure that **database container** is running, You need to either provide Your own one or create `docker-compose` with this content

```yaml
# This should work for all backend projects
services:

  db:
    container_name: voltigo-projects-databases  
    image: mysql:latest
    restart: always
    tty: true    
    environment:
      MYSQL_ROOT_PASSWORD: root
    extra_hosts:
      - "host.docker.internal:host-gateway"      
    volumes:
      - db:/var/lib/mysql   
    ports:
      - 3661:3306

volumes:
  db:
```

- stay in `root` directory and call: 
  - `docker compose -f vendor/volmarg/keywords-finder-bundle/docker/docker-compose.yml up -d`
    - this one takes long to finish,
- go inside the `docker` directory,
- call `docker-compose up -d`,
- wait for installation to finish (You can do `docker logs follow` to see what's happening)
- the project is now reachable:
  - locally under: `127.0.0.1:8007`
  - within other voltigo-related containers under: `host.docker.internal:8007`

## Other

- the usage of **luminati-proxy** also named as **Bright Data ProxyManager** has been explained in **ProxyProvider** project
- this project has no gui