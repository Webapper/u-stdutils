<?php
/**
 * Copyright (c) 2016. by Csaba Dobai (aka Assarte), all rights reserved due to European Laws of Intellectual Properties OR licence attached
 */

/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2016.05.24.
 * Time: 17:46
 */

namespace U\Utils;

use Webapper\U\Utils\Strings;

/**
 * Class PregStreamPatch
 *
 * Supporting some of the moderately difficult meta-data separation on streams by regex.
 *
 * @package U\Utils
 */
class PregStreamPatch
{
    /**
     * Indicates that {@link build} must skip none of the patches on both sides of stream portion
     */
    const SKIP_NONE = 0;

    /**
     * Indicates that {@link build} must skip the patch on left side of stream portion
     */
    const SKIP_LEFT = 1;

    /**
     * Indicates that {@link build} must skip the patch on right side of stream portion
     */
    const SKIP_RIGHT = 2;

    /**
     * Technically it's {@link SKIP_LEFT} + {@link SKIP_RIGHT}
     */
    const SKIP_BOTH = 3;

    protected $re = '';

    protected $offsets = [];

    protected $stream = '';

    /**
     * PregStreamPatch constructor.
     * @param string $re
     * @param string $stream
     */
    public function __construct($re, $stream) {
        $matches = null;
        if (preg_match_all($re, $stream, $matches, PREG_OFFSET_CAPTURE)) {
            $matchOffset = 0;
            foreach ($matches[0] as $match) {
                $offset = [
                    'position'  => $match[1] - $matchOffset,
                    'sequence'  => $match[0],
                    'length'    => strlen($match[0])
                ];
                $this->offsets[] = $offset;
                $stream = substr_replace($stream, '', $offset['position'], $offset['length']);
                $matchOffset += $offset['length'];
            }
        }
        $this->stream = $stream;
    }

    /**
     * @return array
     */
    public function getOffsets()
    {
        return $this->offsets;
    }

    /**
     * @return string
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Builds and returns given portion of stream patched, regarding the skip-rule
     * @param int $from Beginning position on the separated stream
     * @param int $skip Skip-rules by SKIP_* constants
     * @param int|null $length Positive length of portion within the separated stream, or leave it NULL (up to the end)
     * @return string
     * @throws \InvalidArgumentException When $length have a negative value
     * @see SKIP_NONE
     * @see SKIP_LEFT
     * @see SKIP_RIGHT
     * @see SKIP_BOTH
     */
    public function build($from=0, $skip=self::SKIP_BOTH, $length=null) {
        if (isset($length)) {
            if ($length < 0) throw new \InvalidArgumentException('Argument $length could be NULL, 0, or positive number only.');
            $s = (string)substr($this->stream, $from, (int)$length);
        } else {
            $s = (string)substr($this->stream, $from);
        }

        $insOffset = 0;
        foreach ($this->offsets as $offset) {
            if ($skip & self::SKIP_LEFT) {
                if ($offset['position'] <= $from) continue;
            } else {
                if ($offset['position'] < $from) continue;
            }
            if ($skip & self::SKIP_RIGHT) {
                if ($offset['position'] - $from + $insOffset >= strlen($s)) break;
            } else {
                if ($offset['position'] - $from + $insOffset > strlen($s)) break;
            }
            $s = Strings::insertInto($s, $offset['sequence'], $offset['position'] - $from + $insOffset);
            $insOffset += $offset['length'];
        }

        return $s;
    }

    /**
     * Returns the length calculated from given start, including possible patches, regarding the skip-rule
     * @param int $from Beginning position on the separated stream
     * @param int $skip Skip-rules by SKIP_* constants
     * @return int
     * @see SKIP_NONE
     * @see SKIP_LEFT
     * @see SKIP_RIGHT
     * @see SKIP_BOTH
     */
    public function getLength($from=0, $skip=self::SKIP_BOTH) {
        $len = strlen($this->stream);

        $lenOffset = 0;
        foreach ($this->offsets as $offset) {
            if ($skip & self::SKIP_LEFT) {
                if ($offset['position'] <= $from) continue;
            } else {
                if ($offset['position'] < $from) continue;
            }
            if ($skip & self::SKIP_RIGHT) {
                if ($offset['position'] >= $len) break;
            } else {
                if ($offset['position'] > $len) break;
            }
            $lenOffset += $offset['length'];
        }

        return $len - $from + $lenOffset;
    }
}