#! /usr/bin/env bash

_run_completions() {
    # The list of available commands
    local commands="help tests browser-sync status"

    # Get the current word being typed
    local cur="${COMP_WORDS[COMP_CWORD]}"

    # Generate the matching suggestions
    COMPREPLY=( $(compgen -W "${commands}" -- ${cur}) )
}

# Register the function for your script and alias
complete -F _run_completions ./run
complete -F _run_completions run