from __future__ import with_statement
from fabric.api import *
import os, sys
import re

env.base_path = os.path.dirname(__file__)
env.test_path = env.base_path

@task
def doc(clean=False):
    with lcd(os.path.join(env.base_path, 'doc')):
        if clean:
            local('make clean')
        local('make html')

@task
def cloc():
    local('cloc src')
    local('cloc test')

@task
def pdf():
    with lcd(os.path.join(env.base_path, 'doc')):
        local('make latexpdf >> /dev/null')
        print "PDF available at:"
        print "%s/doc/_build/latex/AmissPHPDataMapper.pdf" % env.base_path

@task
def test(filter=None):
    with lcd(env.test_path):
        cmd = 'phpunit --exclude-group faulty'
        if filter:
            cmd += ' --filter ' + filter
        local(cmd)

@task
def testgrp(group):
    with lcd(env.test_path):
        local('phpunit --group %s' % group)

@task
def testall():
    with lcd(env.test_path):
        local('phpunit')

@task
def testcvg(coverage_path='/tmp/cvg'):
    with lcd(env.test_path):
        local('phpunit --exclude-group faulty --coverage-html=%s' % coverage_path)
        print "Coverage available at:"
        print "%s/index.html" % coverage_path

@task
def archive(outpath):
    with lcd(env.base_path):
        version = read_version()
        local("git archive --prefix=amiss/ HEAD | bzip2 >%s/amiss-%s.tar.bz2" % (outpath, version))

@task
def version(version):
    with lcd(env.base_path):
        local(r"""echo "%s" > VERSION""" % version)
        local(r"""sed -i 's/"version": ".*"/"version": "%s"/g' composer.json""" % version)
        composer = open("composer.json", 'r').read()
        if not re.search(r'"%s"' % version, composer):
            raise RuntimeError("Could not replace version")

        local("cat VERSION")
        local("cat composer.json")

        print "---"
        print "Make sure you commit"


def read_version():
    version = open(env.base_path+'/VERSION', 'r').read().rstrip()

