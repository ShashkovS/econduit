import pyJSCompressor
import pyCssCompressor

# Compress functions for different extensions
COMPRESS_FUNC = {'css': pyCssCompressor.compress,
                 'js': pyJSCompressor.compress,
                }

def compress(from_file, to_file, encoding='utf-8'):
    """
    Compress file from_file, put result to to_file.
    Returns False if there is no need to compress,
    returns True if compression was successful,
    sends exception in the case of error.
    Example:
        compress('my_file.js', 'my_file.min.js')
    """

    # No dot => no extension => nothing to do
    last_dot = from_file.rfind('.')
    if last_dot == -1:
        return False
    ext = from_file[last_dot + 1:].lower()

    # unknown extension => nothing to do
    if ext not in COMPRESS_FUNC:
        return False

    # trying to open input file
    with open(from_file, encoding=encoding) as input_file:
        raw_data = input_file.read()

    # trying to compress
    compressed_data = COMPRESS_FUNC[ext](raw_data)

    # trying to write the result
    with open(to_file, 'w', encoding=encoding) as output_file:
        output_file.write(compressed_data)

    # Success
    return True


if __name__ == '__main__':
    print(compress.__doc__)


