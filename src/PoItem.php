<?php

declare(strict_types = 1);

namespace Sweetchuck\PoParser;

class PoItem implements \Stringable, \JsonSerializable
{
    public static function __set_state($values)
    {
        $self = new static();
        $propertyNames = [
            'comments' => 'comments',
            'msgctxt' => 'msgctxt',
            'msgid' => 'msgid',
            'msgid_plural' => 'msgidPlural',
            'msgidPlural' => 'msgidPlural',
            'msgstr' => 'msgstr',
        ];
        foreach ($propertyNames as $keyword => $propertyName) {
            if (array_key_exists($keyword, $values)) {
                $self->$propertyName = $values[$keyword];
            }
        }

        return $self;
    }

    /**
     * @return static
     */
    public static function createFromHeader(PoHeader $header)
    {
        $self = new static();
        $self->msgid[] = '';
        $self->msgstr[''] = Utils::explode((string) $header);

        return $self;
    }

    /**
     * Lines without double quotes and trailing new line characters.
     *
     * @var string[]
     */
    public array $comments = [];

    /**
     * Lines without double quotes and trailing new line characters.
     *
     * @var string[]
     */
    public array $msgctxt = [];

    /**
     * Lines without double quotes and trailing new line characters.
     *
     * @var string[]
     */
    public array $msgid = [];

    /**
     * Lines without double quotes and trailing new line characters.
     *
     * @var string[]
     */
    public array $msgidPlural = [];

    /**
     * Array of arrays.
     *
     * Key is an empty string if the item is singular.
     * keys are integers if the item is plural.
     *
     * @var string[][]
     */
    public array $msgstr = [];

    public function __toString(): string
    {
        $result = '';
        if ($this->comments) {
            $result .= implode(Utils::$eol, $this->comments) . Utils::$eol;
        }

        if ($this->msgctxt) {
            $result .= 'msgctxt ' . Utils::linesToPo($this->msgctxt);
        }

        if ($this->msgid) {
            $result .= 'msgid ' . Utils::linesToPo($this->msgid);
        }

        if ($this->msgidPlural) {
            $result .= 'msgid_plural ' . Utils::linesToPo($this->msgidPlural);
        }

        foreach ($this->msgstr as $key => $msgstr) {
            $key = $key === '' ? '' : "[$key]";
            $result .= "msgstr$key " . Utils::linesToPo($msgstr);
        }

        return $result;
    }

    public function jsonSerialize()
    {
        return [
            'comments' => $this->comments,
            'msgctxt' => $this->msgctxt,
            'msgid' => $this->msgid,
            'msgidPlural' => $this->msgidPlural,
            'msgstr' => $this->msgstr,
        ];
    }
}
