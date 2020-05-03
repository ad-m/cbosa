#!/bin/bash
HOUR=$(date +"%H");
make proxy;
if [ "$HOUR" -eq "0" ]; then
CMD="php scrap.php 0 dowolny 648\*" make run;
elif [ "$HOUR" -eq "8" ]; then
CMD="php scrap.php 1 dowolny 648\*" make run;
elif [ "$HOUR" -eq "16" ]; then
CMD="php scrap.php 0 Naczelny+S%C4%85d+Administracyjny 648\*" make run;
fi;