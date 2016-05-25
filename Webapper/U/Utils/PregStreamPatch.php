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

namespace Webapper\U\Utils;

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
     *
     * Arguments $from and $length can also be passed as arrays. Both could contain 'patch' and 'char' options. On $from
     * the 'patch'-option means the index of first patch (from {@link $offsets}) and 'char'-option means offset in
     * characters from patch index (or beginning if 'patch' is NULL or left) - both could be left. On argument $length
     * the 'patch'-option means length in indices of patches and 'char' means offset in characters on top of the given
     * 'patch'-option - both could be left or NULL, but works somehow differently: when 'patch' left or is NULL it's
     * equals with $length=NULL, when 'char' left it will not counts but counted up to the next patch (or end of stream)
     * if it was declared as NULL.
     *
     * @param int|array $from Beginning position on the separated stream
     * @param int|array $skip Skip-rules by SKIP_* constants
     * @param int|null $length Positive length of portion within the separated stream, or leave it NULL (up to the end)
     * @return string
     * @throws \InvalidArgumentException When $length have a negative value, or $from or $length passed as array but contains illegal options
     * @throws \OutOfRangeException When $from is array and given 'patch'-option points out of the range of patches
     * @see SKIP_NONE
     * @see SKIP_LEFT
     * @see SKIP_RIGHT
     * @see SKIP_BOTH
     */
    public function build($from=0, $skip=self::SKIP_BOTH, $length=null) {
        if (is_array($from)) {
            $allowed = array_flip(['patch', 'char']);
            $fromArray = array_intersect_key($from, $allowed);
            if (count($from) > 0 and count($fromArray) == 0) throw new \InvalidArgumentException(sprintf('Argument $from is array and looks invalid. Allowed keys are: patch, char. It contains: %s', join(', ', array_keys($from))));
            if (!isset($fromArray['patch'])) {
                $from = 0;
            } else {
                if (!isset($this->offsets[$fromArray['patch']])) throw new \OutOfRangeException(sprintf('Given index #%s points out of the range of patches: %s.', $fromArray['patch'], count($this->offsets) == 0 ? 'there are no offsets' : count($this->offsets) - 1));
                $from = $this->offsets[$fromArray['patch']]['position'];
            }
            if (isset($fromArray['char'])) $from += $fromArray['char'];
        }

        if (is_array($length)) {
            $allowed = array_flip(['patch', 'char']);
            $lengthArray = array_intersect_key($length, $allowed);
            if (count($length) > 0 and count($lengthArray) == 0) throw new \InvalidArgumentException(sprintf('Argument $length is array and looks invalid. Allowed keys are: patch, char. It contains: %s', join(', ', array_keys($length))));
            if (!isset($lengthArray['patch'])) {
                $length = null;
            } else {
                $fromPatch = null;
                if (isset($fromArray) and isset($fromArray['patch'])) {
                    $fromPatch = $fromArray['patch'];
                } else {
                    foreach ($this->offsets as $idx=>$offset) {
                        if ($skip & self::SKIP_LEFT) {
                            if ($offset['position'] > $from) break;
                        } else {
                            if ($offset['position'] >= $from) break;
                        }
                        $fromPatch = $idx;
                    }
                }
                if (!isset($fromPatch)) {
                    $length = null;
                } else {
                    if (!isset($this->offsets[$fromPatch + $lengthArray['patch']])) {
                        $length = null;
                    } else {
                        $length = $this->offsets[$fromPatch + $lengthArray['patch']]['position'] - $this->offsets[$fromPatch]['position'];
                    }
                }
                if (isset($length) and array_key_exists('char', $lengthArray)) {
                    if (isset($lengthArray['char'])) {
                        $length += $lengthArray['char'];
                    } else {
                        if (isset($this->offsets[$fromPatch + $lengthArray['patch'] + 1])) {
                            $length += $this->offsets[$fromPatch + $lengthArray['patch'] + 1]['position'] - $this->offsets[$fromPatch + $lengthArray['patch']]['position'];
                        } else {
                            $length = null;
                        }
                    }
                }
            }
        }

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
     *
     * Arguments $from can also be passed as array. Please, have a look at {@link build} for more information.
     *
     * @param int|array $from Beginning position on the separated stream
     * @param int $skip Skip-rules by SKIP_* constants
     * @return int
     * @throws \InvalidArgumentException When $from passed as array but contains illegal options
     * @throws \OutOfRangeException When $from is array and given 'patch'-option points out of the range of patches
     * @see SKIP_NONE
     * @see SKIP_LEFT
     * @see SKIP_RIGHT
     * @see SKIP_BOTH
     */
    public function getLength($from=0, $skip=self::SKIP_BOTH) {
        if (is_array($from)) {
            $allowed = array_flip(['patch', 'char']);
            $fromArray = array_intersect_key($from, $allowed);
            if (count($from) > 0 and count($fromArray) == 0) throw new \InvalidArgumentException(sprintf('Argument $from is array and looks invalid. Allowed keys are: patch, char. It contains: %s', join(', ', array_keys($from))));
            if (!isset($fromArray['patch'])) {
                $from = 0;
            } else {
                if (!isset($this->offsets[$fromArray['patch']])) throw new \OutOfRangeException(sprintf('Given index #%s points out of the range of patches: %s.', $fromArray['patch'], count($this->offsets) == 0 ? 'there are no offsets' : count($this->offsets) - 1));
                $from = $this->offsets[$fromArray['patch']]['position'];
            }
            if (isset($fromArray['char'])) $from += $fromArray['char'];
        }

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