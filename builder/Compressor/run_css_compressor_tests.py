import os
import Compressor.pyCssCompressor as pyCssCompressor

top, dirs, files = os.walk(os.path.join(os.getcwd(), 'css_tests')).__next__()
css_tests = [filename for filename in files if filename.endswith('.css')]

for test_file in css_tests:
    any_error_found = False
    with open(os.path.join(top, test_file)) as full_css_file:
        full_css = full_css_file.read()
    with open(os.path.join(top, test_file + '.min')) as min_css_file:
        min_css = min_css_file.read()
    success = True
    try:
        test_min = pyCssCompressor.compress(full_css)
    except:
        success = False
    if success and min_css.rstrip() == test_min:
        res_message = 'OK'
    elif success:
        res_message = 'DIFF!'
        any_error_found = True
    else:
        res_message = 'Fault!'
        any_error_found = True

    print('Test file: {:>40};         Result: {}'.format(test_file, res_message))

print()
if any_error_found:
    print('Some errors found... :(')
else:
    print('Great! It works!')
