import re
import sys
import time
import codecs
import tempfile
import base64
from os import path

from sphinx.builders import Builder
from docutils import nodes, writers
from docutils.io import StringOutput
from docutils.parsers.rst import directives
from sphinx.util.compat import Directive
from sphinx.util.console import bold
from sphinx.util.nodes import set_source_info

from subprocess import Popen

blankline_re = re.compile(r'^\s*<BLANKLINE>', re.MULTILINE)
doctestopt_re = re.compile(r'#\s*doctest:.+$', re.MULTILINE)


def setup(app):
    app.add_directive('phptestsetup', PhptestsetupDirective)
    app.add_directive('phptestcleanup', PhptestcleanupDirective)
    # app.add_directive('phpdoctest', PhpdoctestDirective)
    app.add_directive('phptestcode', PhptestcodeDirective)
    app.add_directive('phptestoutput', PhptestoutputDirective)
    app.add_builder(PhpDocTestBuilder)
    
    app.add_config_value('phpdoctest_test_doctest_blocks', 'default', False)
    app.add_config_value('phpdoctest_global_setup', '', False)
    app.add_config_value('phpdoctest_global_cleanup', '', False)
    
    # app.add_builder(CodeExtractingBuilder)


class TestDirective(Directive):
    """
    Base class for doctest-related directives.
    """

    has_content = True
    required_arguments = 0
    optional_arguments = 1
    final_argument_whitespace = True

    def run(self):
        # use ordinary docutils nodes for test code: they get special attributes
        # so that our builder recognizes them, and the other builders are happy.
        code = '\n'.join(self.content)
        test = None
        
        if self.name == 'phpdoctest':
            if '<BLANKLINE>' in code:
                # convert <BLANKLINE>s to ordinary blank lines for presentation
                test = code
                code = blankline_re.sub('', code)
            if doctestopt_re.search(code):
                if not test:
                    test = code
                code = doctestopt_re.sub('', code)
        nodetype = nodes.literal_block
        if self.name in ('phptestsetup', 'phptestcleanup') or 'hide' in self.options:
            nodetype = nodes.comment
        if self.arguments:
            groups = [x.strip() for x in self.arguments[0].split(',')]
        else:
            groups = ['default']
        node = nodetype(code, code, testnodetype=self.name, groups=groups)
        set_source_info(self, node)
        if test is not None:
            # only save if it differs from code
            node['test'] = test
        if self.name == 'phptestoutput':
            # don't try to highlight output
            node['language'] = 'none'
        else:
            node['language'] = 'php'
        node['options'] = {}
        if self.name in ('phpdoctest', 'phptestoutput') and 'options' in self.options:
            # parse doctest-like output comparison flags
            option_strings = self.options['options'].replace(',', ' ').split()
            for option in option_strings:
                if (option[0] not in '+-' or option[1:] not in
                    doctest.OPTIONFLAGS_BY_NAME):
                    # XXX warn?
                    continue
                flag = doctest.OPTIONFLAGS_BY_NAME[option[1:]]
                node['options'][flag] = (option[0] == '+')
        
        return [node]

class PhptestsetupDirective(TestDirective):
    option_spec = {
        'hide': directives.flag,
    }

class PhptestcleanupDirective(TestDirective):
    option_spec = {
        'hide': directives.flag,
    }

class PhpdoctestDirective(TestDirective):
    option_spec = {
        'hide': directives.flag,
        'options': directives.unchanged,
    }

class PhptestcodeDirective(TestDirective):
    option_spec = {
        'hide': directives.flag,
    }

class PhptestoutputDirective(TestDirective):
    option_spec = {
        'hide': directives.flag,
        'options': directives.unchanged,
    }


# helper classes

class TestGroup(object):
    def __init__(self, name):
        self.name = name
        self.setup = []
        self.tests = []
        self.cleanup = []

    def add_code(self, code, prepend=False):
        if code.type == 'phptestsetup':
            if prepend:
                self.setup.insert(0, code)
            else:
                self.setup.append(code)
        elif code.type == 'phptestcleanup':
            self.cleanup.append(code)
        elif code.type == 'phpdoctest':
            self.tests.append([code])
        elif code.type == 'phptestcode':
            self.tests.append([code, None])
        elif code.type == 'phptestoutput':
            if self.tests and len(self.tests[-1]) == 2:
                self.tests[-1][1] = code
        else:
            raise RuntimeError('invalid TestCode type')

    def __repr__(self):
        return 'TestGroup(name=%r, setup=%r, cleanup=%r, tests=%r)' % (
            self.name, self.setup, self.cleanup, self.tests)


class TestCode(object):
    def __init__(self, code, type, lineno, options=None):
        self.code = code
        self.type = type
        self.lineno = lineno
        self.options = options or {}

    def __repr__(self):
        return 'TestCode(%r, %r, %r, options=%r)' % (
            self.code, self.type, self.lineno, self.options)


class PhpDocTestRunner(object):
    tries = 0
    
    def run(self, setups, cleanups, code, output, logger):
        all_code = []
        for i in setups:
            all_code.append(i.code)
        all_code.append(code.code)
        for i in cleanups:
            all_code.append(i.code)
        
        names = []
        out = ["<?php"]
        
        ini = {}
        for k, v in ini.items():
            action, value = v
            if action == 'set':
                out.append("ini_set(base64_decode('%s'), base64_decode('%s'));" % (base64.b64encode(k), base64.b64encode(value)))
            elif action == 'append':
                out.append("$key = base64_decode('%s'); ini_set($key, ini_get($key).base64_decode('%s'));" % (base64.b64encode(k), base64.b64encode(value)))
            else:
                raise RuntimeError("Unknown action %s" % action)
        
        for c in all_code:
            out.append("eval(base64_decode('%s'));" % base64.b64encode("?>" + c))
        
        p = Popen(['php', '-n'])
        print "\n".join(out)
        
        pass


# the new builder -- use sphinx-build.py -b phpdoctest to run

class PhpDocTestBuilder(Builder):
    """
    Runs test snippets in the documentation.
    """
    name = 'phpdoctest'

    def init(self):
        self.total_failures = 0
        self.total_tries = 0
        
        date = time.strftime('%Y-%m-%d %H:%M:%S')
        self.outfile = codecs.open(path.join(self.outdir, 'output.txt'),
                                   'w', encoding='utf-8')
        self.outfile.write('''\
Results of PHP doctest builder run on %s
======================================%s
''' % (date, '='*len(date)))


    def _out(self, text):
        self.info(text, nonl=True)
        self.outfile.write(text)

    def _warn_out(self, text):
        self.info(text, nonl=True)
        if self.app.quiet:
            self.warn(text)
        if isinstance(text, bytes):
            text = force_decode(text, None)
        self.outfile.write(text)

    def get_target_uri(self, docname, typ=None):
        return ''

    def get_outdated_docs(self):
        return self.env.found_docs

    def finish(self):
        # write executive summary
        def s(v):
            return v != 1 and 's' or ''
        self._out('''
Doctest summary
===============
%5d test%s
%5d failure%s
''' % (self.total_tries, s(self.total_tries),
       self.total_failures, s(self.total_failures)))
        self.outfile.close()

        if self.total_failures:
            self.app.statuscode = 1

    def write(self, build_docnames, updated_docnames, method='update'):
        if build_docnames is None:
            build_docnames = sorted(self.env.all_docs)

        self.info(bold('running tests...'))
        for docname in build_docnames:
            # no need to resolve the doctree
            doctree = self.env.get_doctree(docname)
            self.test_doc(docname, doctree)

    def test_doc(self, docname, doctree):
        groups = {}
        add_to_all_groups = []
        
        self.test_runner = PhpDocTestRunner()  
        
        if self.config.phpdoctest_test_doctest_blocks:
            def condition(node):
                return (isinstance(node, (nodes.literal_block, nodes.comment))
                        and node.has_key('testnodetype')) or \
                       isinstance(node, nodes.doctest_block)
        else:
            def condition(node):
                return isinstance(node, (nodes.literal_block, nodes.comment)) \
                        and node.has_key('testnodetype')
        for node in doctree.traverse(condition):
            source = node.has_key('test') and node['test'] or node.astext()
            if not source:
                self.warn('no code/output in %s block at %s:%s' %
                          (node.get('testnodetype', 'doctest'),
                           self.env.doc2path(docname), node.line))
            code = TestCode(source, type=node.get('testnodetype', 'doctest'),
                            lineno=node.line, options=node.get('options'))
            node_groups = node.get('groups', ['default'])
            if '*' in node_groups:
                add_to_all_groups.append(code)
                continue
            for groupname in node_groups:
                if groupname not in groups:
                    groups[groupname] = TestGroup(groupname)
                groups[groupname].add_code(code)
        for code in add_to_all_groups:
            for group in groups.itervalues():
                group.add_code(code)
        if self.config.phpdoctest_global_setup:
            code = TestCode(self.config.phpdoctest_global_setup,
                            'phptestsetup', lineno=0)
            for group in groups.itervalues():
                group.add_code(code, prepend=True)
        if self.config.phpdoctest_global_cleanup:
            code = TestCode(self.config.phpdoctest_global_cleanup,
                            'phptestcleanup', lineno=0)
            for group in groups.itervalues():
                group.add_code(code)
        if not groups:
            return

        self._out('\nDocument: %s\n----------%s\n' %
                  (docname, '-'*len(docname)))
        for group in groups.itervalues():
            self.test_group(group, self.env.doc2path(docname, base=None))
        # Separately count results from setup code
        
        if self.test_runner.tries:
            res_f, res_t = self.test_runner.summarize(self._out, verbose=True)
            self.total_failures += res_f
            self.total_tries += res_t
    
    def compile(self, code, name, type, flags, dont_inherit):
        return compile(code, name, self.type, flags, dont_inherit)

    def test_group(self, group, filename):
        # if not run_setup_cleanup(self.setup_runner, group.setup, 'setup'):
        #     return

        for code in group.tests:
            if len(code) == 1:
                raise RuntimeError("Regular doctests not yet supported")
            else:
                self.test_runner.run(setups=group.setup, cleanups=group.cleanup, code=code[0], output=code[1], logger=self._warn_out)
            
        
        # run the tests
        for code in group.tests:
            if len(code) == 1:
                continue
            else:
                
                continue
                
        # run_setup_cleanup(self.cleanup_runner, group.cleanup, 'cleanup')


class CodeExtractingBuilder(Builder):
    name = 'code'
    format = 'json'
    translator_class = None
        
    def init(self):
        self.init_translator_class()
    
    def init_translator_class(self):
        self.translator_class = CodeExtractingTranslator

    def get_outdated_docs(self):
        for docname in self.env.found_docs:
            yield docname

    def prepare_writing(self, docnames):
        self.docwriter = CodeExtractingWriter(self)
    
    def get_target_uri(self, docname, typ=None):
        return docname + '.json'

    def write_doc(self, docname, doctree):
        destination = StringOutput(encoding='utf-8')
        
        self.current_docname = docname
        self.docwriter.write(doctree, destination)
        self.docwriter.assemble_parts()

        # self.handle_page(docname, event_arg=doctree)


class CodeExtractingWriter(writers.Writer):
    def __init__(self, builder):
        writers.Writer.__init__(self)
        self.builder = builder

    def translate(self):
        # sadly, this is mostly copied from parent class
        self.visitor = visitor = self.builder.translator_class(self.builder,
                                                               self.document)
        self.document.walkabout(visitor)


class CodeExtractingTranslator(nodes.NodeVisitor):
    def __init__(self, builder, *args, **kwds):
        nodes.NodeVisitor.__init__(self, *args, **kwds)
        self.highlightlang = builder.config.highlight_language
        self.highlightlinenothreshold = sys.maxint

    def visit_highlightlang(self, node):
        self.highlightlang = node['lang']
        self.highlightlinenothreshold = node['linenothreshold']
    def depart_highlightlang(self, node):
        pass

    def unknown_visit(self, node):
        pass
    
    def unknown_departure(self, node):
        pass
    
    # overwritten
    def visit_literal_block(self, node):
        if node.rawsource != node.astext():
            # most probably a parsed-literal block -- don't highlight
            return
        
        
        if node.has_key('language'):
            if node['language'] == 'php':
                print "---"
                print node.rawsource
        
        return
        
        lang = self.highlightlang
        linenos = node.rawsource.count('\n') >= \
                  self.highlightlinenothreshold - 1
        highlight_args = node.get('highlight_args', {})
        if node.has_key('language'):
            # code-block directives
            lang = node['language']
            highlight_args['force'] = True
        if node.has_key('linenos'):
            linenos = node['linenos']
        
        print "---"
        print node.rawsource
        
        raise nodes.SkipNode

