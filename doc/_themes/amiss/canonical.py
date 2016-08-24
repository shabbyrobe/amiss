import os
import sphinx

def create_canonical(app, pagename, templatename, context, doctree):
    context["canonical_url"] = app.config.canonical_url + '/latest/' + pagename + '.html'


def setup(app):
    app.add_config_value('canonical_url', None, 'html')
    app.connect('html-page-context', create_canonical)
    return {'version': '1.0', 'parallel_read_safe': True}

