#!/bin/bash
HOUR=$(date +"%H");
make proxy;
if [ "$HOUR" -eq "0" ]; then
    PHP_ARGV="0 dowolny ${SYMBOL}" make run;
elif [ "$HOUR" -eq "8" ]; then
    PHP_ARGV="1 dowolny ${SYMBOL}" make run;
elif [ "$HOUR" -eq "16" ]; then
    PHP_ARGV="0 Naczelny+S%C4%85d+Administracyjny ${SYMBOL}" make run;
else
    PHP_ARGV="0 dowolny ${SYMBOL}" make run;
fi;