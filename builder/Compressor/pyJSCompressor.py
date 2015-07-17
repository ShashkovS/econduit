from urllib.parse import urlencode
from urllib.request import Request, urlopen
import os
import tempfile

def compress(js_data):
    """Sends passed js code to Google Closure Compiler Service, returns the compressed code"""

    URL = 'http://closure-compiler.appspot.com/compile'
    # some magic (from https://developers.google.com/closure/compiler/docs/api-tutorial2)
    HEADERS = { "Content-type": "application/x-www-form-urlencoded" }
    print('using closure-compiler.appspot.com to compress')

    # encoding some params and given js code
    params = urlencode([
        ('js_code', js_data),
        ('compilation_level', 'SIMPLE_OPTIMIZATIONS'),
        ('output_format', 'text'),
        ('output_info', 'compiled_code'),
      ]).encode('utf-8')
    req = Request(URL, params, HEADERS)

    # let's go!
    with urlopen(req) as response:
        res_js = response.read().decode('utf-8')

    # may be there is an error in js
    if len(js_data) > 1 and len(res_js) <= 1:
        params = urlencode([
            ('js_code', js_data),
            ('compilation_level', 'SIMPLE_OPTIMIZATIONS'),
            ('output_format', 'text'),
            ('output_info', 'errors'),
          ]).encode('utf-8')
        req = Request(URL, params, HEADERS)
        with urlopen(req) as response:
            error_txt = response.read().decode('utf-8')
        raise Exception(error_txt)


    # well-done, google!
    return res_js


