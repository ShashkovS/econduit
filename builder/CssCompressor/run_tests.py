import os
import pyCssCompressor

top, dirs, files = os.walk(os.path.join(os.getcwd(), 'tests')).__next__()
css_tests = [filename for filename in files if filename.endswith('.css')]

for test_file in css_tests:
    any_error_found = False
    full_css = open(os.path.join(top, test_file)).read()
    min_css = open(os.path.join(top, test_file + '.min')).read()
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
