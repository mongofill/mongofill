#!/bin/bash
case "$1" in
setup)  echo "Creating mongo-php-driver tests environment ..."
    git clone git@github.com:mongodb/mongo-php-driver.git
    cd mongo-php-driver
    phpize
    ./configure --quiet
    mv tests/utils/server.inc tests/utils/server.original.inc
    ;;

clean)  echo  "Cleaning ..."
    rm -rf mongo-php-driver
    ;;

run)  echo  "Running tests ..."
    cd mongo-php-driver
    echo "<?php" > tests/utils/server.inc
    echo "require_once __DIR__ . '/../../../../../vendor/autoload.php';" >> tests/utils/server.inc
    echo "require_once 'server.original.inc';" >> tests/utils/server.inc
    PHP=`make findphp`
    SHOW_ONLY_GROUPS="FAIL,XFAIL,BORK,WARN,LEAK,SKIP" REPORT_EXIT_STATUS=1 TEST_PHP_EXECUTABLE=$PHP $PHP run-tests.php -n -q -x --show-diff
    ;;

boot) echo  "Boot tests server ..."
    cd mongo-php-driver
    cp ../cfg.inc tests/utils/cfg.inc
    echo "<?php" > tests/utils/server.inc
    echo "require_once 'server.original.inc';" >> tests/utils/server.inc
    MONGO_SERVER_STANDALONE=yes MONGO_SERVER_STANDALONE_AUTH=yes MONGO_SERVER_REPLICASET=yes MONGO_SERVER_REPLICASET_AUTH=yes make servers
    ;;
    
*)  echo "Native mongo-php-driver tests helper"
    echo ""
    echo "Usage: $0 [setup|clean|run|boot]"
    echo "   - setup: create the mongo-php-driver tests environment"
    echo "   - boot: starts the mongodb servers, required by the tests"
    echo "   - run: run the tests"
    echo "   - clean: remove all the tests environment"
   ;;
esac
