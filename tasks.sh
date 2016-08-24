#!/bin/bash
set -o nounset -o errexit -o pipefail

task_examples() {
    php -S 127.0.0.1:8555 -t example/   
}

task_cloc() {
    cloc src
    cloc test
}

"task_$1" "${@:2}"

