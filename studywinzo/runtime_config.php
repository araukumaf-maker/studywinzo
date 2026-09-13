<?php
/**
 * Shared runtime configuration helpers.
 *
 * Sensitive values are intentionally read from the process environment so
 * they are not part of the application source or published artifact.
 */

function studywinzo_required_env(string $name): string {
    $value = getenv($name);

    if ($value === false || trim($value) === '') {
        throw new RuntimeException(
            "StudyWinzo configuration error: required environment variable {$name} is missing. " .
            'Add it to Replit Secrets before starting the app.'
        );
    }

    return $value;
}