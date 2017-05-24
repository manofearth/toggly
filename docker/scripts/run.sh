#!/usr/bin/env bash
cd `dirname $0`/../..
docker-compose run --rm php php index.php
