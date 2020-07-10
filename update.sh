#!/bin/bash
HOUR=$(date +"%H");
make proxy;
if [ "$HOUR" -eq "0" ]; then
    RANGE="0" COURT="dowolny" SYMBOL="${SYMBOL}" make run;
elif [ "$HOUR" -eq "8" ]; then
    RANGE="1" COURT="dowolny" SYMBOL="${SYMBOL}" make run;
elif [ "$HOUR" -eq "16" ]; then
    RANGE="0" COURT="Naczelny+S%C4%85d+Administracyjny" SYMBOL="${SYMBOL}" make run;
else
    RANGE="0" COURT="dowolny" SYMBOL="${SYMBOL}" make run;
fi;