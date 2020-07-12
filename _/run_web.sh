#!/usr/bin/env bash

docker container run -dti --rm --name web 3/web /bin/sh
docker container exec -it web /bin/sh
