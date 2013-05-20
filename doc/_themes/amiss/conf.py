from sphinx.highlighting import PygmentsBridge
from pygments.formatters.latex import LatexFormatter

class CustomLatexFormatter(LatexFormatter):
    def __init__(self, **options):
        super(CustomLatexFormatter, self).__init__(**options)
        self.verboptions = r"formatcom=\footnotesize"

PygmentsBridge.latex_formatter = CustomLatexFormatter

# Custom sidebar templates, maps document names to template names.
#html_sidebars = {}
html_sidebars = {
    'index': ['sidebarlogo.html', 'sidebarindex.html', 'sourcelink.html', 'searchbox.html'],
    '**': ['sidebarlogo.html', 'localtoc.html', 'relations.html', 'sourcelink.html', 'searchbox.html'],
}

html_theme = 'amiss'

latex_paper_size = 'a4'

latex_preamble = r"""
\usepackage{upquote}
"""

# \renewcommand{\code}[1]{\texttt{\tiny{#1}}}

# \usepackage{amissstyle}
# \newcommand{\code}[1]{\texttt{\tiny{#1}}

latex_elements = {
    'classoptions': ',oneside,openany',
    'babel': '\\usepackage[english]{babel}',
    'fontpkg': '\\usepackage{palatino}',
}
