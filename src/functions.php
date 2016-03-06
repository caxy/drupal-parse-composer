<?php

namespace Drupal\ParseComposer;

/**
 * Parses data in Drupal's .info format.
 *
 * Data should be in an .ini-like format to specify values. White-space
 * generally doesn't matter, except inside values:
 *
 * @code
 *   key = value
 *   key = "value"
 *   key = 'value'
 *   key = "multi-line
 *   value"
 *   key = 'multi-line
 *   value'
 *   key
 *   =
 *   'value'
 * @endcode
 *
 * Arrays are created using a HTTP GET alike syntax:
 * @code
 *   key[] = "numeric array"
 *   key[index] = "associative array"
 *   key[index][] = "nested numeric array"
 *   key[index][index] = "nested associative array"
 * @endcode
 *
 * PHP constants are substituted in, but only when used as the entire value.
 * Comments should start with a semi-colon at the beginning of a line.
 *
 * @param string $data A string to parse.
 *
 * @return array The info array.
 *
 * @see drupal_parse_info_file()
 */
function drupal_parse_info_format($data)
{
    $info = array();

    if (preg_match_all('
    @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
    ((?:
      [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
      \[[^\[\]]*\]                  # unless they are balanced and not nested
    )+?)
    \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
    (?:
      ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
      (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
      ([^\r\n]*?)                   # Non-quoted string
    )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
    @msx', $data, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // Fetch the key and value string.
            $i = 0;
            foreach (array('key', 'value1', 'value2', 'value3') as $var) {
                $$var = isset($match[++$i]) ? $match[$i] : '';
            }
            $value = stripslashes(substr($value1, 1, -1)).stripslashes(substr($value2, 1, -1)).$value3;

            // Parse array syntax.
            $keys = preg_split('/\]?\[/', rtrim($key, ']'));
            $last = array_pop($keys);
            $parent = &$info;

            // Create nested arrays.
            foreach ($keys as $key) {
                if ($key == '') {
                    $key = count($parent);
                }
                if (!isset($parent[$key]) || !is_array($parent[$key])) {
                    $parent[$key] = array();
                }
                $parent = &$parent[$key];
            }

            // Handle PHP constants.
            if (preg_match('/^\w+$/i', $value) && defined($value)) {
                $value = constant($value);
            }

            // Insert actual value.
            if ($last == '') {
                $last = count($parent);
            }
            $parent[$last] = $value;
        }
    }

    return $info;
}
