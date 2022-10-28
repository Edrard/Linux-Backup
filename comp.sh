#!/bin/bash

where=`which composer`

if [ -n "$1" ]; then
    $1 $where update --no-interaction
else
    $where update --no-interaction
fi