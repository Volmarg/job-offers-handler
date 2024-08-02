#!/bin/bash

# Required rights: sudo
# What this does: check if the searcher project created shutdown (emergency) file and brings the container down
# This is the last resort way of preventing some paid API going insane and generating large costs

FILE=/etc/offers-handler/server-life/shutdown-server.life;
NOW=$(date +"%Y-%m-%d %H:%m:%S");
CONTAINER_NAME=docker-job_offers_handler-1;

if test -f "$FILE"; then
    printf "\n [$NOW] shutting the container down - the shutdown file was found \n";
    rm "$FILE";
    docker container stop "$CONTAINER_NAME";
fi