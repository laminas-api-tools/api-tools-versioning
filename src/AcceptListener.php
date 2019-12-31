<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Versioning;

class AcceptListener extends ContentTypeListener
{
    /**
     * Header to examine.
     * @var string
     */
    protected $headerName = 'accept';

    /**
     * Parse the header for matches against registered regexes
     *
     * @param  string $value
     * @return false|array
     */
    protected function parseHeaderForMatches($value)
    {
        // Accept header is made up of media ranges
        $mediaRanges = explode(',', $value);

        foreach ($mediaRanges as $mediaRange) {
            // Media range consists of mediatype and parameters
            $params    = explode(';', $mediaRange);
            $mediaType = array_shift($params);

            foreach (array_reverse($this->regexes) as $regex) {
                if (! preg_match($regex, $mediaType, $matches)) {
                    continue;
                }

                return $matches;
            }
        }

        return false;
    }
}
