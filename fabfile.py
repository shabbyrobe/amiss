from __future__ import with_statement
from fabric.api import *
import os, sys

env.base_path = os.path.dirname(__file__)

@task
def doc(clean=False):
    with lcd(os.path.join(env.base_path, 'doc')):
        if clean:
            local('make clean')
        local('make html')

@task
def pdf():
    with lcd(os.path.join(env.base_path, 'doc')):
        local('make latexpdf')

@task
def test(filter=None):
    with lcd(os.path.join(env.base_path, 'test')):
        cmd = 'phpunit --exclude-group faulty'
        if filter:
            cmd += ' --filter ' + filter
        local(cmd)

@task
def testgrp(group):
    with lcd(os.path.join(env.base_path, 'test')):
        cmd = 'phpunit --group %s' % group

@task
def testall():
    with lcd(os.path.join(env.base_path, 'test')):
        cmd = 'phpunit'
