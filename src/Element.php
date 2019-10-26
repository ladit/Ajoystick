<?php


namespace Ajoystick;


use DOMNode;
use Exception;

/**
 * Class Element
 * @package Ajoystick
 */
class Element
{
    /**
     * the device of this element
     *
     * @var Device
     */
    public $onDevice;

    /**
     * the attributes of this element
     *
     * @var array
     */
    public $attributes;

    /**
     * @var int
     */
    public $index;

    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $resourceId;
    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $package;

    /**
     * @var string
     */
    public $contentDesc;

    /**
     * @var bool
     */
    public $checkable;

    /**
     * @var bool
     */
    public $checked;

    /**
     * @var bool
     */
    public $clickable;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var bool
     */
    public $focusable;

    /**
     * @var bool
     */
    public $focused;

    /**
     * @var bool
     */
    public $scrollable;

    /**
     * @var bool
     */
    public $longClickable;

    /**
     * @var bool
     */
    public $password;

    /**
     * @var bool
     */
    public $selected;

    /**
     * [
     *   'startX' => 32,
     *   'startY' => 77,
     *   'endX' => 112,
     *   'endY' => 131
     * ]
     *
     * @var array
     */
    public $bounds;

    /**
     * [
     *   'x' => 72,
     *   'y' => 122
     * ]
     *
     * @var array
     */
    public $centerPoint;

    /**
     * Element constructor.
     *
     * @param DOMNode $node
     * @param Device $device
     */
    public function __construct(DOMNode &$node, Device &$device)
    {
        $this->onDevice = $device;
        $this->attributes['index'] = $this->index = intval($node->getAttribute('index'));
        $this->attributes['text'] = $this->text = $node->getAttribute('text');
        $this->attributes['resource-id'] = $this->resourceId = $node->getAttribute('resource-id');
        $this->attributes['class'] = $this->class = $node->getAttribute('class');
        $this->attributes['package'] = $this->package = $node->getAttribute('package');
        $this->attributes['content-desc'] = $this->contentDesc = $node->getAttribute('content-desc');
        $this->attributes['checkable'] = $this->checkable = $node->getAttribute('checkable') === 'true';
        $this->attributes['checked'] = $this->checked = $node->getAttribute('checked') === 'true';
        $this->attributes['clickable'] = $this->clickable = $node->getAttribute('clickable') === 'true';
        $this->attributes['enabled'] = $this->enabled = $node->getAttribute('enabled') === 'true';
        $this->attributes['focusable'] = $this->focusable = $node->getAttribute('focusable') === 'true';
        $this->attributes['focused'] = $this->focused = $node->getAttribute('focused') === 'true';
        $this->attributes['scrollable'] = $this->scrollable = $node->getAttribute('scrollable') === 'true';
        $this->attributes['long-clickable'] = $this->longClickable = $node->getAttribute('long-clickable') === 'true';
        $this->attributes['password'] = $this->password = $node->getAttribute('password') === 'true';
        $this->attributes['selected'] = $this->selected = $node->getAttribute('selected') === 'true';
        preg_match('/^\[(\d+)\,(\d+)\]\[(\d+)\,(\d+)\]$/', $node->getAttribute('bounds'), $match);
        $this->attributes['bounds'] = $this->bounds = [
            'startX' => intval($match[1]),
            'startY' => intval($match[2]),
            'endX' => intval($match[3]),
            'endY' => intval($match[4])
        ];
        $this->centerPoint = [
            'x' => ($this->bounds['startX'] + $this->bounds['endX']) / 2,
            'y' => ($this->bounds['startY'] + $this->bounds['endY']) / 2
        ];
    }

    /**
     * tap on the element
     */
    public function tap()
    {
        $this->onDevice->tap($this->centerPoint['x'], $this->centerPoint['y']);
    }

    /**
     * click on the element, alias of tap()
     */
    public function click()
    {
        $this->tap();
    }

    /**
     * long press the element
     */
    public function longPress()
    {
        $this->onDevice->longPress($this->centerPoint['x'], $this->centerPoint['y']);
    }

    /**
     * swipe from this element to a point
     *
     * @param float $x
     * @param float $y
     */
    public function swipeTo(float $x, float $y)
    {
        $this->onDevice->swipe($this->centerPoint['x'], $this->centerPoint['y'], $x, $y);
    }

    /**
     * swipe from this element to another element
     *
     * @param Element $element
     */
    public function swipeToElement(Element $element)
    {
        $this->swipeTo($element->centerPoint['x'], $element->centerPoint['y']);
    }

    /**
     * todo: clear the input of this element
     *
     * @throws Exception
     */
    public function clear()
    {
        throw new Exception('method clear has not been implemented');
    }

    /**
     * todo: check if equals to another element
     *
     * @param Element $element
     * @return bool
     * @throws Exception
     */
    public function equals(Element $element): bool
    {
        throw new Exception('method equals has not been implemented');
    }

}
