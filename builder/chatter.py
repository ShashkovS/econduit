#-------------------------------------------------------------------------------

def tell_user(text):
    print(text)

#-------------------------------------------------------------------------------

def ask_user(question, variants = None, descriptions = None, case_sensitive = False):
    if variants:
        if descriptions:
            descriptions = ['=' + i for i in descriptions]
        else:
            descriptions = ['' for i in variants]

        if case_sensitive:
            variants_for_check = variants
            variants_for_prompt = [variant + description for variant, description in zip(variants, descriptions)]
        else:
            variants_for_check  = [variant.lower() for variant in variants]
            variants_for_prompt = [variant.lower() + description for variant, description in zip(variants, descriptions)]
            variants_for_prompt[0] = variants_for_prompt[0].capitalize()

        prompt = '{question} [{variants}]\n>>> '.format(
            question = question,
            variants = ' / '.join(variants_for_prompt),
        )

    else:
        prompt = '{question}\n>>> '.format(
            question = question,
        )

    while True:
        result = input(prompt)

        if not variants:
            return result

        if not case_sensitive:
            result = result.lower()

        if result == '':
            result = variants_for_check[0]

        try:
            pos = variants_for_check.index(result)
            return variants[pos]
        except ValueError:
            pass    # variant not found

#-------------------------------------------------------------------------------

if __name__ == '__main__':

    answer = ask_user(
        'What do you want to do with listed files?',
        variants = ['d','i','g','?'],
        descriptions = ['delete', 'ignore', 'add to ignore-list', 'decide individually'],
    )

    tell_user('You answered "{}"'.format(answer))
