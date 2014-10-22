## This is a Python port of the CSS minification tool
## distributed with YUICompressor, itself a port
## of the cssmin utility by Isaac Schlueter - http://foohack.com/
## Permission is hereby granted to use the Python version under the same
## conditions as the YUICompressor (original YUICompressor note below).
##
## YUI Compressor
## http://developer.yahoo.com/yui/compressor/
## Author: Julien Lecomte -  http://www.julienlecomte.net/
## Author: Isaac Schlueter - http://foohack.com/
## Author: Stoyan Stefanov - http://phpied.com/
## Contributor: Dan Beam - http://danbeam.org/
## Copyright (c) 2013 Yahoo! Inc.  All rights reserved.
## The copyrights embodied in the content of this file are licensed
## by Yahoo! Inc. under the BSD (revised) open source license.
##
import re

def __reg_replace(pattern, rep_func, css):
    """Make a replacement of a pattern in string css using rep_func on match object"""
    appendIndex = 0
    sb = []
    for m in re.finditer(pattern, css):
        sb.append(css[appendIndex: m.start()])
        sb.append(rep_func(m))
        appendIndex = m.end()
    sb.append(css[appendIndex:])
    return ''.join(sb)

def __extractDataUrls(css, preservedTokens):
    """Leave data urls alone to increase parse performance."""
    maxIndex = len(css) - 1
    appendIndex = 0
    sb = ''
    # Since we need to account for non-base64 data urls, we need to handle
    # ' and ) being part of the data string.
    for m in re.finditer("(?i)url\\(\\s*([\"']?)data\\:", css):
        startIndex = m.start() + 4      # "url(".length()
        terminator = m.group(1)         # ', " or empty (not quoted)
        if len(terminator) == 0:
            terminator = ")"
        foundTerminator = False
        endIndex = m.end() - 1
        while (not foundTerminator and endIndex + 1 <= maxIndex):
            endIndex = css.find(terminator, endIndex + 1)
            if endIndex > 0 and css[endIndex-1] != '\\':
                foundTerminator = True
                if terminator != ')':
                    endIndex = css.find(')', endIndex)
        # Enough searching, start moving stuff over to the buffer
        sb += css[appendIndex: m.start()]
        if foundTerminator:
            token = css[startIndex: endIndex]
            token = re.sub("\\s+", "", token)
            preservedTokens.append(token)
            preserver = "url(___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___)"
            sb += preserver
            appendIndex = endIndex + 1
        else:
            sb += css.substring[m.start(): m.end()]
            appendIndex = m.end()
    sb += css[appendIndex:]
    return sb


def __preserveOldIESpecificMatrixDefinition(css, preservedTokens):
    appendIndex = 0
    sb = ''
    for m in re.finditer("\\s*filter:\\s*progid:DXImageTransform.Microsoft.Matrix\\(([^\\)]+)\\);", css):
        startIndex = m.start()
        endIndex = m.end() - 1
        sb += css[appendIndex: m.start()]
        token = m.group(1)
        preservedTokens.append(token)
        preserver = "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___"
        sb += "filter:progid:DXImageTransform.Microsoft.Matrix(" + preserver + ");"
        appendIndex = endIndex + 1
    sb += css[appendIndex:]
    return sb


def compress(css, linebreakpos=0):
    startIndex = 0
    endIndex = 0
    i = 0
    max = 0
    preservedTokens = []
    comments = []
    token = ''
    totallen = len(css)
    placeholder = ''

    css = __extractDataUrls(css, preservedTokens)

    # collect all comment blocks...
    sb = css
    startIndex = sb.find("/*")
    while startIndex >= 0:
        endIndex = sb.find("*/", startIndex + 2)
        if endIndex < 0:
            endIndex = totallen
        token = sb[startIndex + 2: endIndex]
        comments.append(token)
        placeholder = "___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_" + str(len(comments) - 1) + "___"
        sb = sb[:startIndex + 2] + placeholder + sb[endIndex:]
        startIndex = sb.find("/*", startIndex + len(placeholder))
    css = sb


    # preserve strings so their content doesn't get accidentally minified
    appendIndex = 0
    sb = ''
    for m in re.finditer("(\"([^\\\\\"]|\\\\.|\\\\)*\")|(\'([^\\\\\']|\\\\.|\\\\)*\')", css):
        startIndex = m.start()
        endIndex = m.end() - 1
        sb += css[appendIndex: m.start()]
        token = m.group()
        quote = token[0]
        token = token[1:-1]
        # maybe the string contains a comment-like substring?
        # one, maybe more? put'em back then
        if token.find("___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_") >= 0:
            for i in range(len(comments)):
                placeholder = "___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_" + str(i) + "___"
                token = token.replace(placeholder, comments[i])
        # minify alpha opacity in filter strings
        token = re.sub("(?i)progid:DXImageTransform.Microsoft.Alpha\\(Opacity=", "alpha(opacity=", token)
        preservedTokens.append(token)
        preserver = quote + "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___" + quote
        sb += preserver
        appendIndex = endIndex + 1
    sb += css[appendIndex:]
    css = sb

    # strings are safe, now wrestle the comments
    for i in range(len(comments)):
        token = comments[i]
        placeholder = "___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_" + str(i) + "___"

        # ! in the first position of the comment means preserve
        # so push to the preserved tokens while stripping the !
        if token.startswith('!'):
            preservedTokens.append(token)
            css = css.replace(placeholder, "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___")
            continue

        # \ in the last position looks like hack for Mac/IE5
        # shorten that to /*\*/ and the next one to /**/
        if token.endswith('\\'):
            preservedTokens.append('\\')
            css = css.replace(placeholder,  "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___")
            i += 1 # attn: advancing the loop       !!!!!!!!!!!!!!!!!!!!!!!!
            preservedTokens.append('')
            css = css.replace("___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_" + str(i) + "___",  "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___")
            continue

        # keep empty comments after child selectors (IE7 hack)
        # e.g. html >/**/ body
        if len(token) == 0:
            startIndex = css.find(placeholder)
            if startIndex > 2:
                if css[startIndex - 3] == '>':
                    preservedTokens.append("")
                    css = css.replace(placeholder,  "___YUICSSMIN_PRESERVED_TOKEN_" + str(len(preservedTokens) - 1) + "___")

        # in all other cases kill the comment
        css = css.replace("/*" + placeholder + "*/", "")


    # Normalize all whitespace strings to single spaces. Easier to work with that way.
    css = re.sub("\\s+", " ", css)

    css = __preserveOldIESpecificMatrixDefinition(css, preservedTokens)

    # Remove the spaces before the things that should not have spaces before them.
    # But, be careful not to turn "p :link {...}" into "p:link{...}"
    # Swap out any pseudo-class colons with the token, and then swap back.
    css = __reg_replace("(^|\\})(([^\\{:])+:)+([^\\{]*\\{)",
                        lambda m: m.group(0).replace(":", "___YUICSSMIN_PSEUDOCLASSCOLON___").replace("\\\\", "\\\\\\\\").replace("\\$", "\\\\\\$"),
                        css)

    # Remove spaces before the things that should not have spaces before them.
    css = re.sub("\\s+([!{};:>+\\(\\)\\],])", "\\1", css)
    # Restore spaces for !important
    css = re.sub("!important", " !important", css)
    # bring back the colon
    css = re.sub("___YUICSSMIN_PSEUDOCLASSCOLON___", ":", css)

    # retain space for special IE6 cases
    css = __reg_replace("(?i):first\\-(line|letter)(\\{|,)",
                        lambda m: ":first-" + m.group(1).lower() + " " + m.group(2),
                        css)

    # no space after the end of a preserved comment
    css = re.sub("\\*/ ", "*/", css)

    # If there are multiple @charset directives, push them to the top of the file.
    css = __reg_replace("(?i)^(.*)(@charset)( \"[^\"]*\";)",
                        lambda m: m.group(2).lower() + m.group(3) + m.group(1),
                        css)

    # When all @charset are at the top, remove the second and after (as they are completely ignored).
    css = __reg_replace("(?i)^((\\s*)(@charset)( [^;]+;\\s*))+",
                        lambda m: m.group(2) + m.group(3).lower() + m.group(4),
                        css)

    # lowercase some popular @directives (@charset is done right above)
    css = __reg_replace("(?i)@(font-face|import|(?:-(?:atsc|khtml|moz|ms|o|wap|webkit)-)?keyframe|media|page|namespace)",
                        lambda m: '@' + m.group(1).lower(),
                        css)

    # lowercase some more common pseudo-elements
    css = __reg_replace("(?i):(active|after|before|checked|disabled|empty|enabled|first-(?:child|of-type)|focus|hover|last-(?:child|of-type)|link|only-(?:child|of-type)|root|:selection|target|visited)",
                        lambda m: ':' + m.group(1).lower(),
                        css)

    # lowercase some more common functions
    css = __reg_replace("(?i):(lang|not|nth-child|nth-last-child|nth-last-of-type|nth-of-type|(?:-(?:moz|webkit)-)?any)\\(",
                        lambda m: ':' + m.group(1).lower() + '(',
                        css)

    # lower case some common function that can be values
    # NOTE: rgb() isn't useful as we replace with #hex later, as well as and() is already done for us right after this
    css = __reg_replace("(?i)([:,\\( ]\\s*)(attr|color-stop|from|rgba|to|url|(?:-(?:atsc|khtml|moz|ms|o|wap|webkit)-)?(?:calc|max|min|(?:repeating-)?(?:linear|radial)-gradient)|-webkit-gradient)",
                        lambda m: m.group(1) + m.group(2).lower(),
                        css)

    # Put the space back in some cases, to support stuff like
    # @media screen and (-webkit-min-device-pixel-ratio:0){
    css = css.replace("\\*/ ", "*/")

    # Put the space back in some cases, to support stuff like
    # @media screen and (-webkit-min-device-pixel-ratio:0){
    css = re.sub("(?i)\\band\\(", "and (", css)

    # Remove the spaces after the things that should not have spaces after them.
    css = re.sub("([!{}:;>+\\(\\[,])\\s+", "\\1", css)

    # remove unnecessary semicolons
    css = re.sub(";+}", "}", css)

    # Replace 0(px,em,%) with 0.
    css = re.sub("(?i)(^|[^.0-9])(?:0?\\.)?0(?:px|em|%|in|cm|mm|pc|pt|ex|deg|g?rad|m?s|k?hz)", "\g<1>0", css)

    # Replace x.0(px,em,%) with x(px,em,%).
    css = re.sub("([0-9])\\.0(px|em|%|in|cm|mm|pc|pt|ex|deg|g?rad|m?s|k?hz| |;)", "\\1\\2", css)

    # Replace 0 0 0 0; with 0.
    css = re.sub(":0 0 0 0(;|})", ":0\\1", css)
    css = re.sub(":0 0 0(;|})", ":0\\1", css)
    css = re.sub(":0 0(;|})", ":0\\1", css)

    # Replace background-position:0; with background-position:0 0;
    # same for transform-origin
    css = __reg_replace("(?i)(background-position|webkit-mask-position|transform-origin|webkit-transform-origin|moz-transform-origin|o-transform-origin|ms-transform-origin):0(;|})",
                        lambda m: m.group(1).lower() + ":0 0" + m.group(2),
                        css)

    # Replace 0.6 to .6, but only when preceded by : or a white-space
    css = re.sub("(:|\\s)0+\\.(\\d+)", "\\1.\\2", css)

    # Shorten colors from rgb(51,102,153) to #336699
    # This makes it more likely that it'll get further compressed in the next step.
    appendIndex = 0
    sb = ''
    for m in re.finditer("rgb\\s*\\(\\s*([0-9,\\s]+)\\s*\\)", css):
        startIndex = m.start()
        endIndex = m.end() - 1
        sb += css[appendIndex: m.start()]
        rgbcolors = m.group(1).split(",")
        hexcolor = '#'
        for i in range(len(rgbcolors)):
            val = int(rgbcolors[i])
            # If someone passes an RGB value that's too big to express in two characters, round down.
            # Probably should throw out a warning here, but generating valid CSS is a bigger concern.
            if val > 255:
                val = 255
            hexcolor += "%0.2X" % val
        sb += hexcolor
        appendIndex = endIndex + 1
    sb += css[appendIndex:]
    css = sb

    # Shorten colors from #AABBCC to #ABC. Note that we want to make sure
    # the color is not preceded by either ", " or =. Indeed, the property
    #     filter: chroma(color="#FFFFFF");
    # would become
    #     filter: chroma(color="#FFF");
    # which makes the filter break in IE.
    # We also want to make sure we're only compressing #AABBCC patterns inside { }, not id selectors ( #FAABAC {} )
    # We also want to avoid compressing invalid values (e.g. #AABBCCD to #ABCD)
    appendIndex = 0
    sb = ''
    index = 0
    p = re.compile("(\\=\\s*?[\"']?)?" + "#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])" + "(:?\\}|[^0-9a-fA-F{][^{]*?\\})")
    m = p.search(css, index)
    while m:
        sb += css[index: m.start()]
        if m.group(1) is not None and m.group(1) != '':
            sb += m.group(1) + "#" + m.group(2) + m.group(3) + m.group(4) + m.group(5) + m.group(6) + m.group(7)
        else:
            if m.group(2).upper() == m.group(3).upper() and m.group(4).upper() == m.group(5).upper() and m.group(6).upper() == m.group(7).upper():
                sb += "#" + (m.group(3) + m.group(5) + m.group(7)).lower()
            else:
                sb += "#" + (m.group(2) + m.group(3) + m.group(4) + m.group(5) + m.group(6) + m.group(7)).lower()
        index = m.end(7)
        m = p.search(css, index)
    sb += css[index:]
    css = sb

    # Replace #f00 -> red
    css = re.sub("(:|\\s)(#f00)(;|})", r"\1red\3", css)
    # Replace other short color keywords
    css = re.sub("(:|\\s)(#000080)(;|})", r"\1navy\3", css)
    css = re.sub("(:|\\s)(#808080)(;|})", r"\1gray\3", css)
    css = re.sub("(:|\\s)(#808000)(;|})", r"\1olive\3", css)
    css = re.sub("(:|\\s)(#800080)(;|})", r"\1purple\3", css)
    css = re.sub("(:|\\s)(#c0c0c0)(;|})", r"\1silver\3", css)
    css = re.sub("(:|\\s)(#008080)(;|})", r"\1teal\3", css)
    css = re.sub("(:|\\s)(#ffa500)(;|})", r"\1orange\3", css)
    css = re.sub("(:|\\s)(#800000)(;|})", r"\1maroon\3", css)

    # border: none -> border:0
    css = __reg_replace("(?i)(border|border-top|border-right|border-bottom|border-left|outline|background):none(;|})",
                        lambda m: m.group(1).lower() + ":0" + m.group(2),
                        css)

    # shorter opacity IE filter
    css = re.sub("(?i)progid:DXImageTransform.Microsoft.Alpha\\(Opacity=", "alpha(opacity=", css)

    # Find a fraction that is used for Opera's -o-device-pixel-ratio query
    # Add token to add the "\" back in later
    css = re.sub("\\(([\\-A-Za-z]+):([0-9]+)\\/([0-9]+)\\)", "(\\1:\\2___YUI_QUERY_FRACTION___\\3)", css)

    # Remove empty rules.
    css = re.sub("[^\\}\\{/;]+\\{\\}", "", css)

    # Add "\" back to fix Opera -o-device-pixel-ratio query
    css = re.sub("___YUI_QUERY_FRACTION___", "/", css)

    # TODO: Should this be after we re-insert tokens. These could alter the break points. However then
    # we'd need to make sure we don't break in the middle of a string etc.
    if linebreakpos > 0:
        sb = css
        break_pos = linebreakpos
        next_break = sb.find('}', break_pos)
        while next_break > 0:
            sb = sb[:next_break + 1] + '\n' + sb[next_break + 1:]
            break_pos += linebreakpos + 2
            if break_pos < next_break:
                break_pos = next_break + 2
            next_break = sb.find('}', break_pos)
        css = sb

    # Replace multiple semi-colons in a row by a single one
    # See SF bug #1980989
    css = re.sub(";;+", ";", css)

    # restore preserved comments and strings
    for i in range(len(preservedTokens) - 1, -1, -1):
        css = css.replace("___YUICSSMIN_PRESERVED_TOKEN_" + str(i) + "___", preservedTokens[i])

    # Trim the final string (for any leading or trailing white spaces)
    css = css.strip()

    # Write the output...
    return css


if __name__ == '__main__':
    from optparse import OptionParser
    usage = "usage: %prog filename.css [options] "
    parser = OptionParser(usage)
    parser.add_option("--charset", dest="charset", default='utf-8',
                  help="Read the input file using CHARSET")
    parser.add_option("--line-break", dest="linebreakpos", default='0',
                  help="Insert a line break after the specified column number", metavar="POS")
    parser.add_option("-o", dest="outfile", default='',
                  help="Place the output into FILE", metavar="FILE")

    (options, args) = parser.parse_args()
    if len(args) != 1:
        parser.error("Exactly one css file must be given")
    elif not options.linebreakpos.isnumeric():
        parser.error("Column number must be a number: " + options.linebreakpos)

    try:
        css_data = open(args[0], 'r', encoding=options.charset).read()
    except FileNotFoundError:
        parser.error("No such file: " + args[0])
    except LookupError:
        parser.error("Unknown encoding:" + options.charset)
    except Exception:
        parser.error("Could not open css file")

    if not options.outfile:
        import sys
        outfile = sys.stdout
    else:
        try:
            outfile = open(options.outfile, 'w', encoding=options.charset)
        except Exception:
            parser.error("Could not open outfile")

    try:
        min_css = compress(css_data, int(options.linebreakpos))
    except Exception:
        parser.error("Minification crashed... :(")

    outfile.write(min_css)
    if options.outfile:
        outfile.close()




