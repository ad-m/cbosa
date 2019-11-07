#!/bin/bash
# HOUR=$(date +"%H");
HOUR="0"
if [ "$HOUR" -eq "0" ]; then
php scrap.php 0 'dowolny' '648*';
elif [ "$HOUR" -eq "8" ]; then
php scrap.php 1 'dowolny' '648*';
elif [ "$HOUR" -eq "16" ]; then
php scrap.php 0 'Naczelny+S%C4%85d+Administracyjny' '648*';
fi;