#-------------------------------------------------------------------------------

import os.path

import Compressor.pyJSCompressor as pyJSCompressor
import Compressor.pyCssCompressor as pyCssCompressor

#-------------------------------------------------------------------------------

# Compress functions for different extensions
COMPRESS_FUNC = {
    'css': pyCssCompressor.compress,
    'js' : pyJSCompressor.compress,
}

#-------------------------------------------------------------------------------

def compress(from_file, to_file, encoding = 'utf-8'):
    """
    Compress file from_file, put result to to_file.
    Returns False if there is no need to compress,
    returns True if compression was successful,
    sends exception in the case of error.
    Example:
        compress('my_file.js', 'my_file.min.js')
    """

    ext = os.path.splitext(from_file)[1].lower()
    if ext:
        ext = ext[1:]

    # unknown extension => nothing to do
    if ext not in COMPRESS_FUNC:
        return False

    # trying to open input file
    if hasattr(from_file, 'read'):
        raw_data = from_file.read()
    else:
        with open(from_file, encoding = encoding) as input_file:
            raw_data = input_file.read()

    # trying to compress
    compressed_data = COMPRESS_FUNC[ext](raw_data).encode(encoding)

    # trying to write the result
    if hasattr(to_file, 'write'):
        to_file.write(compressed_data)
    else:
        with open(to_file, 'wb') as output_file:
            output_file.write(compressed_data)

    # Success
    return True

#-------------------------------------------------------------------------------

if __name__ == '__main__':
    print(compress.__doc__)

#-------------------------------------------------------------------------------
