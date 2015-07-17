import os
import tempfile
import subprocess

def jarWrapper(*args):
    """ Runs jar-file with args, return output"""
    process = subprocess.Popen(['java', '-jar']+list(args), stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    ret = []
    while process.poll() is None:
        line = process.stdout.readline()
        if line != b'' and line.endswith(b'\n'):
            ret.append(line[:-1])
    stdout, stderr = process.communicate()
    if stderr != b'':
        return stderr.decode()


def compress(js_data):
    f_in, f_in_path = tempfile.mkstemp()
    f_out, f_out_path = tempfile.mkstemp()
    error, result = None, None
    try:
        with open(f_in_path, 'w', encoding='utf-8') as f:
            f.write(js_data)
        error = jarWrapper(
                    os.path.join(os.path.dirname(__file__), 'compiler.jar'),
                    '--compilation_level', 'SIMPLE',
                    '--js', f_in_path,
                    '--js_output_file', f_out_path,
                    '--language_in', 'ECMASCRIPT5'
                    )
        if error is None:
            with open(f_out_path, 'r', encoding='utf-8') as f:
                result = f.read()
    finally:
        os.close(f_in)
        os.close(f_out)
        os.remove(f_in_path)
        os.remove(f_out_path)
    if error:
        raise Exception(error)
    else:
        return result
