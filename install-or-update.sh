#!/bin/bash
if [ ! -f "./.is-installed" ]; then
  sh install.sh;
  exit 0;
fi;

sh update.sh;