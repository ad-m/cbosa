#!/bin/bash
HOUR=$(date +"%H");
make proxy;
if [ "$HOUR" -eq "0" ]; then
    PHP_ARGV="0 dowolny 648\*" make run;
elif [ "$HOUR" -eq "8" ]; then
    PHP_ARGV="1 dowolny 648\*" make run;
elif [ "$HOUR" -eq "16" ]; then
    PHP_ARGV="0 Naczelny+S%C4%85d+Administracyjny 648\*" make run;
else
    PHP_ARGV="0 dowolny 648\*" make run;
fi;