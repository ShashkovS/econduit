import os
import Compressor.pyJSCompressor as pyJSCompressor

top, dirs, files = os.walk(os.path.join(os.getcwd(), 'js_tests')).__next__()
js_tests = [filename for filename in files if filename.endswith('.js')]

for test_file in js_tests:
    any_error_found = False
    with open(os.path.join(top, test_file)) as full_js_file:
        full_js = full_js_file.read()
    with open(os.path.join(top, test_file + '.min')) as min_js_file:
        min_js = min_js_file.read()
    success = True
    try:
        test_min = pyJSCompressor.compress(full_js)
    except:
        success = False
    if success and min_js == test_min:
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
