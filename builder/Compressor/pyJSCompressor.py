from urllib.parse import urlencode
from urllib.request import Request, urlopen

def compress(js_data):
    """Sends passed js code to Google Closure Compiler Service, returns the compressed code"""
    # encoding some params and given js code
    params = urlencode([
        ('js_code', js_data),
        ('compilation_level', 'SIMPLE_OPTIMIZATIONS'),
        ('output_format', 'text'),
        ('output_info', 'compiled_code'),
      ]).encode('utf-8')
    url = 'http://closure-compiler.appspot.com/compile'
    # some magic (from https://developers.google.com/closure/compiler/docs/api-tutorial2)
    headers = { "Content-type": "application/x-www-form-urlencoded" }
    req = Request(url, params, headers)
    # let's go!
    with urlopen(req) as response:
        res_js = response.read().decode('utf-8')
    # well-done, google!
    return res_js

