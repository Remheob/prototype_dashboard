#!/bin/bash
cd ../classifier/production_version
#echo $(pwd);
python3 main.py $1 2>/dev/null | tail -n 1
